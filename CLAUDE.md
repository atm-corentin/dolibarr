# CLAUDE.md — module clichaumeil

Module Dolibarr custom (ATM Consulting) pour le client Chaumeil. Vit dans
`htdocs/custom/clichaumeil/` d'une install Dolibarr ; ce dossier est lui-même
un dépôt git distinct.

## Stack

- PHP / Dolibarr (API natives : `getDolGlobalString`, `$user->hasRight`,
  `dol_syslog`, `$db->prefix()`…).
- Tests : PHPUnit (suite Dolibarr `test/phpunit/`).
- Pas de composer dédié : autoload via `require_once` explicites et le core Dolibarr.

## Structure

- `core/modules/modClichaumeil.class.php` — descripteur (version, droits, hooks,
  dictionnaires, `init()`/activation).
- `core/triggers/` — triggers métier (commissions, propagation, workflows).
- `class/` — classes métier, regroupées par domaine :
  `Service/`, `Subcontracting/`, `SupplierPriceSync/`, `Rfa/`, `Cron/`.
- `admin/` — pages de configuration (`commissions.php`, connexions API…).
- `sql/` — `llx_*.sql` (tables) + `llx_*.key.sql` (index/contraintes).
- `langs/fr_FR`, `langs/en_US` — fichiers de langue (clés `CliChaumeil...`).
- `test/phpunit/` — tests d'intégration (vraie BDD, transaction rollback-ée).
- `scripts/` — scripts CLI (seed de jeux de test, rebuilds RFA…).

## Build / test

Lancer un test (depuis la racine Dolibarr, pas celle du module) :

```bash
cd <dolibarr>/test/phpunit
phpunit -c phpunittest.xml <chemin-absolu-du-test>.php
```

Les tests étendent `CommonClassTest` : `setUpBeforeClass()` ouvre une transaction,
`tearDownAfterClass()` fait un `rollback` — les écritures de test ne survivent pas.
`createMock(DoliDB::class)` **ne marche pas** (constante auto-référencée
`self::VERSIONMIN`) : tester via le vrai `$db` dans la transaction de suite.

## Conventions

- Voir le `CLAUDE.md` global de l'utilisateur pour les règles transverses Dolibarr
  (sécurité SQL, `$db->prefix()`, logs, nommage, traductions, versioning).
- Versioning du descripteur : feature → chiffre du milieu, fix → dernier chiffre.
  `ChangeLog.md` mis à jour à chaque PR (section `## Unreleased`, format
  `- PRÉFIXE : description *jj/mm/aaaa* - x.y.z`).
- Gates qualité team-ai au commit (`lint → tests → claudemd → docs → local`).

## Pièges connus

- **Seeding de dictionnaire one-shot** : `CliChaumeilCommissionDictionarySeeder`
  (migration constantes → dictionnaire `c_clichaumeil_commission_coeff`) ne réinsère
  rien si l'entité a déjà des lignes. Ce dictionnaire est **éditable** par
  l'utilisateur ; ne jamais re-seeder par ligne (le `code` et le `customer_tag`
  peuvent avoir été personnalisés → collision sur la clé unique
  `(entity, role_code, customer_tag)`).
