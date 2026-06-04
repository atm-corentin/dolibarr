# Socle de synchronisation des prix d'achats fournisseurs

Socle générique, *supplier-agnostic*, pour synchroniser les prix d'achats Dolibarr
(`product_fournisseur_price`) à partir d'une API fournisseur. ANTALIS (SOAP) est le
premier connecteur. OVOL (REST), GEODIS… se branchent en réutilisant tout le socle.

## Architecture

```
Contract/         contrats génériques (à implémenter par chaque fournisseur)
  SupplierConfigInterface           getCode / getLabel / getSupplierThirdpartyId
  SupplierPriceConnectorInterface   getCode / getRecommendedBatchSize /
                                    supportsTierDiscovery / fetchPriceGrids(products)
ValueObject/      objets immuables partagés (ProductRequest, PriceTier, ProductPriceGrid,
                  GridFetchResult, Candidate=ligne existante, Issue, Report, CronRecipients)
Repository/       SupplierPriceRepository : chargement candidats, activation/clôture
                  (status SQL direct), ligne extrafields. AUCUNE dépendance fournisseur.
Service/          SupplierPriceSyncService : orchestration (compare/MAJ/clôture/réactive)
                  SupplierPriceSyncMailer  : envoi du rapport d'erreur
Cron/             AbstractSupplierPriceSyncCronJob : run() générique mutualisé
                  <Supplier>SupplierPriceSyncCronJob : 1 ligne par fournisseur
Antalis/          implémentation concrète ANTALIS (config, connecteur SOAP, mapper)
```

Le socle (`Contract`, `ValueObject`, `Repository`, `Service`, `Cron/Abstract…`) ne
contient **aucune** référence à un fournisseur précis et n'a pas à être modifié pour
en ajouter un.

## Règles métier (communes à tous les connecteurs)

- Unité d'interrogation = le **produit** (`SupplierProductRequest`, une réf fournisseur).
  Le connecteur renvoie la **grille complète des paliers** (`SupplierProductPriceGrid`).
- Un palier (`SupplierPriceTier`) porte `quantity`, `unitLabel` (= **unité de prix** Dolibarr) et
  `normalizedUnitPrice`. Un produit peut renvoyer **plusieurs paliers de même quantité dans des unités
  de prix différentes** (cas ANTALIS : prix par feuille ET par ramette). `unitLabel` = l'unité dans
  laquelle le prix est exprimé, c'est la **clé de réconciliation**.
- Le Service **réconcilie** par **unité** (`matchLine`) : le palier dont `unitLabel` = l'unité de
  conditionnement de la ligne → MAJ du prix dans cette même unité (jamais de changement d'unité).
  Repli par **quantité** (`QUANTITY_EPSILON`) pour les connecteurs sans unité.
- `supportsTierDiscovery()` : `true` ⇒ grille autoritative (création des paliers manquants + clôture des
  absents) ; `false` ⇒ **update-only** (on ne touche que les lignes connues, jamais de création/clôture
  sur palier manquant). Produit `ABSENT` → clôture des lignes actives **dans les deux cas**.
- Écriture du prix via `ProductFournisseur::update_buyprice()` (préserve l'historique
  `product_fournisseur_price_log`). Le prix passé est le **total HT pour la quantité**.
- `status` activé/clôturé en **SQL direct** (aucun setter core) via le Repository.
- Comparaison des prix avec une tolérance (`PRICE_EPSILON`).
- Une erreur produit n'arrête pas le run ; une API injoignable (`fatalError`) l'arrête.

### Cas ANTALIS (vérifié sur l'API réelle)
`customerPricesCheck` renvoie un palier **par unité de prix** (`personalPriceUnit`), pas par quantité :
un produit vendu à la ramette renvoie `{ZRM: 9,76/ramette}` **et** `{ZSH: 0,02/feuille}` (même `thresholdQty`).
Le connecteur émet **tous** les paliers étiquetés par `personalPriceUnit` ; le Service matche celui dont
l'unité = l'unité de la ligne (Ramette/Lot/M2…). ANTALIS est en `supportsTierDiscovery()=false` (**update-only**) :
les multi-paliers sont des variantes d'unité du même produit, pas des paliers à créer. La quantité stockée
(jusqu'à des millions de feuilles) ne correspond pas à `thresholdQty` → on ne matche **jamais** par quantité.

### Garde-fous (socle, génériques)
- **Fail-safe d'unité** : si aucun palier ne correspond à l'unité de la ligne, le Service **avertit**
  (`UNIT_MISMATCH`) et **n'écrit rien** — jamais de prix dans la mauvaise unité.
- **Garde-fou de clôture** : un run ne peut clôturer plus de `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_MAX_CLOSURE_RATIO`%
  des lignes scannées (défaut 50 ; ≥100 = désactivé). Au-delà → issue `CLOSURE_THRESHOLD`, clôtures suspendues.
  Protège d'une réponse API partielle/erronée.
- **Mode simulation** : `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_DRY_RUN=1` → le run calcule les compteurs et le rapport
  sans **aucune** écriture. Idéal pour valider sur données réelles avant activation.

## Ajouter un nouveau connecteur (recette)

Exemple : `OVOL`.

1. **Config** — `Ovol/OvolConnectorConfig.php implements SupplierConfigInterface`,
   construite depuis des constantes Dolibarr dédiées (`fromGlobals()` qui valide les
   champs requis et lève `RuntimeException` si incomplet).
2. **Connecteur** — `Ovol/OvolConnector.php implements SupplierPriceConnectorInterface`.
   `fetchPriceGrids(array $products)` appelle l'API (REST/SOAP), puis **normalise** chaque
   réponse en `SupplierProductPriceGrid::found(tiers[])|absent()|error()` et remplit
   `SupplierPriceGridFetchResult(grids, issues, fatalError)`. Renvoyer `supportsTierDiscovery()`
   selon la capacité de l'API à énumérer tous les paliers. Réutiliser les codes
   `SupplierPriceSyncConstants::ISSUE_*` pour les anomalies.
3. **Mapping d'unités** (si l'API renvoie ses propres codes d'unité) —
   `Ovol/OvolOrderUnitMapper.php`, qui renvoie `null` pour toute unité non mappable
   (ne jamais deviner ; le palier est alors créé avec un libellé vide + avertissement).
4. **Cron** — `Cron/OvolSupplierPriceSyncCronJob.php extends AbstractSupplierPriceSyncCronJob` :
   implémenter `buildConfig()` (= `OvolConnectorConfig::fromGlobals()`) et
   `buildConnector()` (= `new OvolConnector(...)`). Rien d'autre : le `run()` est hérité.
5. **Descripteur** — déclarer le cron dans `modClichaumeil.class.php` (`$this->cronjobs[]`)
   et ajouter la déduplication si nécessaire.
6. **Admin** — ajouter la configuration OVOL (constantes + champs FormSetup) dans
   `admin/api_connections.php`. Le **mot de passe** se gère hors FormSetup (formulaire
   dédié jamais pré-rempli — cf. ANTALIS) pour ne pas exposer le secret dans la source HTML,
   et il est stocké **chiffré** via `dolEncrypt()` (conf le déchiffre automatiquement au chargement,
   donc `getDolGlobalString()` le renvoie en clair).
7. **Langues** — clés `fr_FR` + `en_US` (libellés des constantes, label/commentaire du cron).
   Les messages d'anomalie `CliChaumeil_SupplierPriceSync_<CODE>` sont **partagés** et déjà
   traduits : un nouveau connecteur les réutilise automatiquement.
8. **Tests** — au minimum la normalisation des réponses du connecteur (fixtures = payloads
   réels) et le mapper d'unités. Le Service et le Repository sont déjà couverts.

## Tests

`test/phpunit/` : `extends CommonClassTest` (begin/rollback), vraie BDD, pas de mocks.
La normalisation des réponses se teste via `ReflectionMethod` sur `normalizeResponse`
(logique pure, sans appel réseau). Un `FakeConnector` (cf. `SupplierPriceSyncServiceTest`)
permet de tester le Service sans API réelle.

## Mise en route (go-live)

1. **Configurer le connecteur** — page *Configuration → Connexions API* : URL SOAP,
   login/mot de passe HTTP, identifiant client, code utilisateur, adresse de livraison,
   et tiers fournisseur ANTALIS. Survoler l'icône d'info de chaque champ pour le détail.
2. **Tester la connexion** — bouton *Tester la connexion* : doit répondre OK.
3. **Activer la simulation** — passer `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_DRY_RUN` à 1.
   En simulation, le run calcule tout mais n'écrit rien.
4. **Lancer la tâche manuellement** — page *Cron*, exécuter la tâche ANTALIS une fois.
   ⚠️ Le run complet dure ~2,6 h (catalogue entier, lots de 10). La progression est
   visible dans le syslog (heartbeat tous les 25 lots).
5. **Relire le rapport** — le résumé et les anomalies apparaissent dans la sortie du cron
   et sur la page de configuration (ligne *Dernière synchronisation*). Vérifier :
   unités correctement mappées (pas de `UNMAPPED_ORDER_UNIT` massif, ex. `PAK`),
   absence d'erreurs systémiques, volume d'updates cohérent.
6. **Désactiver la simulation** — remettre `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_DRY_RUN` à 0.
7. **Activer le cron** — planifier la tâche en horaire nocturne.

**Résilience** : un fault SOAP transitoire est rejoué une fois (backoff 3 s) ; un lot en échec
n'interrompt pas le run, sauf 5 lots consécutifs en échec (disjoncteur → arrêt propre, reprise
la nuit suivante). Le garde-fou de fermeture limite à 50 % des lignes fermées en un run.
