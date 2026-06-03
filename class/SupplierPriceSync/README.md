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
- Le Service **réconcilie** chaque grille avec les lignes Dolibarr existantes :
  palier présent+ligne → MAJ si prix diffère / inchangé ; palier présent sans ligne → **création** ;
  ligne sans palier → **clôture** ; produit `ABSENT` → clôture de toutes ses lignes actives.
- `supportsTierDiscovery()` : `true` ⇒ grille autoritative (création + clôture des absents) ;
  `false` ⇒ on ne touche que les lignes déjà connues (connecteur par-ligne).
- Écriture du prix via `ProductFournisseur::update_buyprice()` (préserve l'historique
  `product_fournisseur_price_log`). Le prix passé est le **total HT pour la quantité**.
  Création d'une ligne via `Product::add_fournisseur()` puis `update_buyprice()`.
- `status` activé/clôturé en **SQL direct** (aucun setter core) via le Repository.
- Comparaison des prix avec une tolérance (`PRICE_EPSILON`) ; matching palier↔ligne par quantité (`QUANTITY_EPSILON`).
- Une erreur produit n'arrête pas le run ; une API injoignable (`fatalError`) l'arrête.

### Garde-fous (socle, génériques)
- **Cohérence d'unité du prix** : le connecteur **refuse** un palier dont `personalPriceUnit ≠ thresholdQtyUnit`
  (le prix serait exprimé dans la mauvaise unité) → issue `UNIT_MISMATCH`, pas d'écriture.
- **Divergence d'unité de ligne** : le Service **avertit** (sans bloquer) si l'unité Dolibarr de la ligne
  diffère de l'unité du palier (comparaison de libellés Dolibarr, donc agnostique fournisseur).
- **Garde-fou de clôture** : un run ne peut clôturer plus de `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_MAX_CLOSURE_RATIO`%
  des lignes scannées (défaut 50 ; ≥100 = désactivé). Au-delà → issue `CLOSURE_THRESHOLD`, clôtures suspendues.
  Protège d'une réponse API partielle/erronée.
- **Mode simulation** : `CLICHAUMEIL_SUPPLIER_PRICE_SYNC_DRY_RUN=1` → le run calcule les compteurs et le rapport
  sans **aucune** écriture. Idéal pour valider sur données réelles avant activation.
- Hypothèse de matching : pour une réf fournisseur donnée, une quantité de palier ⇒ une seule unité côté
  Dolibarr (à valider en preprod ; la divergence est signalée par `UNIT_MISMATCH`).

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
