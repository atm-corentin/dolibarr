# CLAUDE.md — module clichaumeil

Module Dolibarr custom (ATM Consulting) pour le client Chaumeil (imprimerie /
print management). Vit dans `htdocs/custom/clichaumeil/` d'une install Dolibarr ;
ce dossier est lui-même un dépôt git distinct. Module n° **104321**.

Voir aussi le `CLAUDE.md` global de l'utilisateur pour les règles transverses
Dolibarr (sécurité SQL, logs, nommage, traductions, versioning, conventions git).

## Stack

- PHP / Dolibarr — API natives privilégiées (`getDolGlobalString`,
  `$user->hasRight`, `dol_syslog`, `$db->prefix()`, `dol_now`, `price2num`…).
- Tests : PHPUnit (suite Dolibarr `test/phpunit/`), tous en intégration sur vraie BDD.
- Pas de composer : autoload via `require_once` explicites + core Dolibarr.

## Structure

```
core/modules/modClichaumeil.class.php   Descripteur : version, droits, hooks, dictionnaires,
                                        crons, menus, extrafields, init()/upgrade()/remove().
core/triggers/                          interface_99_..._ClichaumeilTriggers (triggers métier)
                                        + ..._TestLineTriggerCapture (capture pour tests).
class/actions_clichaumeil.class.php     ActionsClichaumeil : TOUS les hooks (priority 40).
class/                                  Classes métier par domaine :
  Service/                                services transverses (ex. seeder dictionnaire commissions)
  Subcontracting/                         workflow sous-traitance (ST-8 / R2-ST-6)
  SupplierPriceSync/                      synchro prix fournisseur (ANTALIS SOAP)
    Antalis/ Contract/ Repository/ Service/ Cron/
  Rfa/                                    synthèses RFA fournisseur/client
  Cron/                                   jobs cron (RFA reminder/summary)
  chaumeilrfa.class.php                    objet RFA (CRUD)
admin/                                  Pages de config (onglets) — voir lib AdminPrepareHead.
lib/clichaumeil.lib.php                 clichaumeilAdminPrepareHead() + helpers UI.
sql/                                    llx_*.sql (tables) + llx_*.key.sql (index/contraintes).
langs/fr_FR · langs/en_US               Fichiers de langue (clés CliChaumeil... / CLICHAUMEIL_...).
test/phpunit/                           ~19 tests d'intégration.
scripts/ · script/                      CLI (seeds, rebuilds RFA) et endpoints AJAX.
```

## Build / test

Lancer un test (depuis la racine **Dolibarr**, pas celle du module) :

```bash
cd <dolibarr>/test/phpunit
phpunit -c phpunittest.xml <chemin-absolu-du-test>.php
```

- Les tests étendent `CommonClassTest` : `setUpBeforeClass()` ouvre une transaction,
  `tearDownAfterClass()` fait un `rollback` — **les écritures de test ne survivent pas**.
  On peut donc écrire/INSERT librement sur la vraie BDD dans un test.
- `createMock(DoliDB::class)` **ne marche pas** (constante auto-référencée
  `self::VERSIONMIN`). Pour isoler : utiliser le vrai `$db` dans la transaction de
  suite, et pour les dépendances externes (connecteurs, configs) **implémenter
  l'interface** (`SupplierPriceConnectorInterface`, `SupplierConfigInterface`) avec
  un double de test, pas un mock.
- Isolation multi-entité : penser à `entity` dans les jeux de test ; utiliser une
  entité sentinelle pour éviter de heurter les données réelles.

## Sous-systèmes

### 1. Commissions (`class/CliChaumeilCommissionConfig`, `class/Service/CliChaumeilCommissionDictionarySeeder`)
- Dictionnaire **éditable** `c_clichaumeil_commission_coeff`, indexé par
  `(entity, role_code, customer_tag)` ET `(entity, code)` (double clé unique).
- `CliChaumeilCommissionDictionarySeeder::migrate()` = migration **one-shot**
  constantes → dictionnaire : ne réinsère rien si l'entité a déjà des lignes
  (sinon collision sur la clé unique, et écrasement des `code`/tags personnalisés).
- `cronupdatecustomercategories` segmente les clients en `nouveau`/`ancien` selon
  l'historique de factures (catégories `CAT_*`).
- ⚠️ **Code mort** : `CliChaumeilCommissionConfig::getCoeff()` n'a **aucun appelant**
  dans le module, et la migration **supprime** les constantes legacy `COEFF_*`.
  Aucun calcul de commission n'est donc actuellement câblé sur ce dictionnaire :
  c'est de l'infrastructure prête mais non consommée. Ne pas supposer qu'éditer un
  coefficient change un calcul tant qu'un consommateur n'a pas été branché.

### 2. Sous-traitance — ST-8 / R2-ST-6 (`class/Subcontracting/`)
- **Deux points d'entrée** vers `CliChaumeilSubcontractorSelectionWorkflow::execute()` :
  le trigger `PROPOSAL_SUPPLIER_CLOSE_SIGNED` (via `CliChaumeilSupplierProposalSignHandler`)
  et le bouton AJAX `choose_subcontractor` (`script/interface.php` + `js/choose_subcontractor.js`).
- Workflow : valide la proposition fournisseur choisie → propage les prix d'achat →
  crée et valide une commande fournisseur (`CliChaumeilSupplierOrderFactory`) → refuse
  les autres propositions → envoie l'email (`CliChaumeilSupplierOrderMailService`).
- **Propagation du prix d'achat (R2-ST-6)** : le `subprice` de la ligne proposition
  fournisseur (= prix d'achat) est propagé vers `buy_price_ht` de la ligne client.
  Lien persistant via extrafields `clichaumeil_source_element` / `clichaumeil_source_line_id`
  sur `supplier_proposaldet`, avec fallback déterministe `fk_product + rang + special_code`
  (collision multiple = ambiguïté → propagation échoue).
- **Document brouillon vs validé** : sur brouillon, `updateline()` complet (le prix de
  vente est recalculé via le taux mini Discountrules) ; sur validé, **UPDATE SQL direct**
  de `buy_price_ht` (Dolibarr bloque `updateline()` hors brouillon) + trigger de ligne.
  Le taux mini n'est donc PAS réappliqué sur un document validé.
- **Taux de marge mini** (`CliChaumeilSubcontractingMinimumRateResolver`) : ordre
  tiers > produit > global (extrafield `options_discountrules_min_markup_margin_percent`,
  globales `DISCOUNTRULES_*`). R2-VT-12 affiche un avertissement de marge mini dans les
  modales de validation propal/commande.
- ⚠️ **Ré-entrance** : le bouton appelle `cloture()` qui refire le trigger
  `PROPOSAL_SUPPLIER_CLOSE_SIGNED` de façon synchrone ; un garde statique
  (`$isRunning`) empêche la double exécution. Tout changement du workflow doit
  préserver ce garde (cf. `...WorkflowReentrancyTest`).

### 3. RFA (`class/Rfa/`, `class/chaumeilrfa.class.php`)
- Objet `ChaumeilRfa` avec discriminant `rfa_type` (0 = fournisseur, 1 = client) :
  paliers (`palier`) + taux (`raterfa`), table `llx_clichaumeil_chaumeilrfa`.
- Synthèses annuelles dénormalisées dans `llx_clichaumeil_rfa_summary`
  (clé unique `(entity, year, fk_soc, rfa_type)`), construites par
  `RfaSummaryBuilder` (fournisseur) / `RfaClientSummaryBuilder` (client) à partir
  des **factures closes** (`STATUS_CLOSED` = 2, inclut clôtures manuelles).
- Agrégation **hiérarchique** sur `thirdparty.parent` (DFS avec détection de cycle +
  mémoïsation, `MAX_DEPTH_GUARD`). Rebuild = **TRUNCATE année/type puis INSERT**
  (pas d'upsert) — `scripts/rebuild_rfa_summary.php` et `Cron/RfaSummaryRebuildCronJob`
  (paramètre `rfa_type`).

### 4. Synchro prix fournisseur — ANTALIS (`class/SupplierPriceSync/`)
- Cron quotidien (`AntalisSupplierPriceSyncCronJob`) : interroge le SOAP ANTALIS
  `customerPricesCheck` et réconcilie les grilles de prix contre
  `product_fournisseur_price` via `update_buyprice` (préserve l'historique).
- **Réconciliation par unité de prix** (feuille, rame, lot, m²…), pas par quantité :
  appariement sur l'extrafield `conditionnement_unite_de_prix` (normalisé), fallback
  quantité seulement si l'unité n'est pas fournie.
- **Idempotence** : arrondi `price2num('MU')` à la précision stockée + epsilons de
  comparaison ; sans ça chaque run réécrirait les mêmes lignes.
- **Update-only** : `supportsTierDiscovery() = false` (pas de création de palier).
- **Garde-fous** : retry SOAP (1×, backoff 3 s), circuit-breaker (stop après 5 lots
  consécutifs en échec), heartbeat tous les 25 lots, plafond de clôtures
  (`CONST_MAX_CLOSURE_RATIO`, défaut 50 %), mode dry-run, limite produits (`CONST_PRODUCT_LIMIT`).
- Config via constantes Dolibarr ; **mot de passe HTTP chiffré** (`dolEncrypt`,
  auto-déchiffré par `getDolGlobalString`). Page `admin/api_connections.php`.
- Socle **supplier-agnostic** (Contract/ValueObject/Repository/Service/Cron abstrait) :
  un nouveau fournisseur = 1 config + 1 connecteur + 1 cron d'une ligne. Voir
  `class/SupplierPriceSync/README.md` (référence à jour ; OVOL/GEODIS prévus).

### 5. Coût produit & lignes de proposition par défaut
- Extrafields de décomposition de coût sur produit (`clichaumeil_pa_*`, frais de
  dossier, FG…), créés sans recréation destructive (`ensureProductExtrafield()`),
  protégés par le droit `product/read_cost_composition` (lecture seule = visibilité 5).
- Surface calculée automatiquement (`clichaumeil_height` × `clichaumeil_length`) dans
  les triggers de ligne propal/commande, conversion via `CUnits` (base CM2), avec
  `notriggers=1` pour éviter la récursion.
- Lignes de proposition « par défaut/protégées » (`clichaumeil_default_inserted`),
  injectées à `PROPAL_CREATE`, dont la suppression/édition est gardée (trigger + JS).

### 6. Révision / reconduction de contrat (`class/cronupdatecontractrevision.class.php`)
- Cron (`CronJobUpdateContractRevision`, désactivé par défaut) qui révise les contrats :
  ajoute `CLICHAUMEIL_REVIEW_YEAR_DELAY` années à la date de révision et applique un taux,
  via les extrafields `clichaumeil_reviewdate` (sur `contratdet`) et `clichaumeilreviewrate`
  (sur `contrat`), puis notifie les responsables/utilisateurs configurés (onglet Contrats,
  modèle d'email type `contract`).

## Hooks & triggers — points d'attention

- `ActionsClichaumeil` est en **priority 40** (avant multicompany en 50) pour capter
  `propal.creer` dans `restrictedArea()` avant que multicompany ne l'écrase. Ne pas
  changer cette priorité sans comprendre l'interaction.
- Tout objet à `fk_soc` : implémenter `replaceThirdparty` ET le déclarer dans
  `module_parts['hooks']`, sinon le hook ne se déclenche jamais.
- Double garde (DB via trigger renvoyant -1 + JS côté hook) sur les opérations
  risquées (validation propal, suppression de ligne protégée).
- Hook `createFrom` : le core le fire AUSSI sur `Commande::createFromProposal()`
  (transformation devis→commande, `objFrom`=Propal), pas seulement sur les clones.
  Discriminer le vrai clone via `objFrom instanceof <même type que $object>` avant tout
  post-traitement (cf. VT-25 `CliChaumeilCloneCostPriceService`), sinon la logique se
  déclenche à tort sur la transformation devis→commande. Le hook tourne DANS la
  transaction du clone core : un retour <0 (ou `$this->errors` non vide) rollback le clone.

## Conventions

- `declare(strict_types=1);`, PHPDoc anglaise complète, visibilité explicite, typage
  strict params/retours. Classes par domaine sous `class/<Domaine>/`.
- SQL : `$db->prefix()` (jamais `llx_`/`MAIN_DB_PREFIX`), `intval`/`(int)` + `escape`,
  `$db->free()` après SELECT, `entity IN (getEntity('...'))` pour le multi-entité,
  transactions pour le multi-étapes. `fetch()` : distinguer `1`/`0`/`-1`.
- Erreurs : `dol_syslog(..., LOG_ERR)` une seule fois, `throw` pour la trace, sortie
  tôt (error-first). Pas de `error_log`/`var_dump` en prod.
- Extrafields : `options_clichaumeil_<champ>` (namespace d'un autre module respecté,
  ex. `options_discountrules_*`).
- Traductions : clés `CLICHAUMEIL_<CONST>` (globales) / `CliChaumeil<Label>` (UI),
  tooltips suffixés `Tooltip`, parité `fr_FR` + `en_US` obligatoire.
- Versioning descripteur : feature → chiffre du milieu, fix → dernier chiffre.
  `ChangeLog.md` à chaque PR (section `## Unreleased`, format
  `- PRÉFIXE : description *jj/mm/aaaa* - x.y.z`).
- Gates qualité team-ai au commit (`lint → tests → claudemd → docs → local`) ;
  l'onglet de doc (`admin/clichaumeil_documentation.php` + `docs/configuration.md`)
  doit rester à jour dans le même commit qu'un changement de code.

## Pièges connus (récapitulatif)

- Seeding commissions = one-shot, dictionnaire éditable → ne jamais re-seeder par ligne.
- `getCoeff()` / dictionnaire commissions = **non consommés** au runtime (code mort).
- Propagation prix d'achat : taux mini Discountrules appliqué **uniquement** sur brouillon.
- Appariement de lignes ST-6 ambigu (`fk_product+rang+special_code` non unique) → échec.
- Workflow sous-traitance ré-entrant via `cloture()` → garde statique obligatoire.
- Synchro ANTALIS : appariement par **unité de prix**, idempotence dépend de l'arrondi `MU`.
- Rebuild RFA = TRUNCATE+INSERT (pas d'upsert) ; factures comptées = `STATUS_CLOSED`.
- `createMock(DoliDB::class)` casse → tests sur vrai `$db` + doubles d'interface.
