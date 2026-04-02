# Plan d'implémentation

## Sujet

Evolution de la composition du prix de revient dans le module `clichaumeil`.

Objectifs principaux :
- ajouter les taux `conditionnement` et `transport`
- réviser la formule métier R2
- afficher `Total coûts` comme champ virtuel non persisté
- recalculer `clichaumeil_pa_fg` et `product.cost_price` uniquement si `FG%` est renseigné
- conserver le comportement historique de masquage UI par permission
- ne faire aucune migration de masse

## Position d'architecture

La base existante du module est saine :
- calculateur dédié dans `CliChaumeilProductCost.class.php`
- orchestration UI dans `actions_clichaumeil.class.php`
- recalculs automatiques dans le trigger produit
- déclaration des extrafields dans `modClichaumeil.class.php`

La bonne stratégie est donc une extension disciplinée de l'existant, sans patch du core Dolibarr.

Je recommande de formaliser le contrat métier avec 2 DTO simples :
- `CostBreakdownInput`
- `CostBreakdownResult`

Raison :
- moins de tableaux associatifs fragiles
- moins de clés magiques disséminées
- séparation plus claire entre lecture, calcul, synchronisation et rendu

## Arborescence cible

### Fichiers à modifier

- `custom/clichaumeil/class/CliChaumeilProductCost.class.php`
- `custom/clichaumeil/class/actions_clichaumeil.class.php`
- `custom/clichaumeil/core/triggers/interface_99_modClichaumeil_ClichaumeilTriggers.class.php`
- `custom/clichaumeil/core/modules/modClichaumeil.class.php`
- `custom/clichaumeil/admin/setup.php`
- `custom/clichaumeil/langs/fr_FR/clichaumeil.lang`
- `custom/clichaumeil/langs/en_US/clichaumeil.lang`
- `custom/clichaumeil/import_samples/products_cost_breakdown_sample.csv`
- `custom/clichaumeil/import_samples/products_cost_breakdown_update_sample.csv`
- `custom/clichaumeil/ChangeLog.md`

### Fichiers à créer

- `custom/clichaumeil/class/CostBreakdownInput.class.php`
- `custom/clichaumeil/class/CostBreakdownResult.class.php`

Remarque :
si on voulait un footprint minimal absolu, ces structures pourraient rester en tableaux associatifs. Ce n'est pas ma recommandation.

## Base de données

Aucune nouvelle table métier.

### Tables concernées

- `extrafields`
  - déclaration des nouveaux extrafields produit

- `product_extrafields`
  - stockage des valeurs :
    - `clichaumeil_pa_support`
    - `clichaumeil_pa_sav`
    - `clichaumeil_pa_machine`
    - `clichaumeil_pa_encre`
    - `clichaumeil_pa_mo`
    - `clichaumeil_conditionnement_percent`
    - `clichaumeil_transport_percent`
    - `clichaumeil_fg_percent`
    - `clichaumeil_pa_fg`

- `product`
  - stockage du champ natif `cost_price`

### Clés et relations

- relation logique existante : `product_extrafields.fk_object -> product.rowid`
- aucune nouvelle clé étrangère à introduire dans cette évolution
- aucun `fk_soc` dans le périmètre, donc aucun impact `replaceThirdparty`

### Migration

- aucune migration de masse
- aucune correction historique
- première modification d'une fiche existante : la nouvelle formule s'applique sur les valeurs en base

## Hooks et triggers Dolibarr

### Hooks à utiliser

- `formObjectOptions`
  - préremplissage du `FG%` à la création manuelle si absent

- `doActions`
  - édition unitaire dans le contexte `pricesuppliercard`

- `afterImportInsert`
  - recalcul après import étape 6

- `llxFooter`
  - injection des lignes de composition sur l'onglet achat fournisseur
  - masquage des extrafields déplacés sur `productcard`

### Triggers à utiliser

- `PRODUCT_CREATE`
- `PRODUCT_MODIFY`
- `PRODUCT_PRICE_MODIFY`

### replaceThirdparty

Pas de modification requise pour cette feature.

Règle de gouvernance :
si un futur stockage spécifique introduit un `fk_soc`, il faudra alors prévoir l'implémentation adaptée.

## Constantes à centraliser

Constantes à poser en tête du calculateur :

```text
EXTRA_PREFIX = 'options_'
DEFAULT_RATE_VALUE = '0.25'
COST_FIELDS = [
  'clichaumeil_pa_support',
  'clichaumeil_pa_sav',
  'clichaumeil_pa_machine',
  'clichaumeil_pa_encre',
  'clichaumeil_pa_mo'
]
PACKAGING_PERCENT_FIELD = 'clichaumeil_conditionnement_percent'
TRANSPORT_PERCENT_FIELD = 'clichaumeil_transport_percent'
FG_PERCENT_FIELD = 'clichaumeil_fg_percent'
FG_AMOUNT_FIELD = 'clichaumeil_pa_fg'
VIRTUAL_TOTAL_COSTS_FIELD = 'clichaumeil_total_costs'
SUPPORTED_PRODUCT_TYPES = [Product::TYPE_PRODUCT, Product::TYPE_SERVICE]
PRECISION_AMOUNT = 4
PRECISION_PERCENT = 4
```

Dans `ActionsClichaumeil`, il faut réutiliser les constantes du calculateur autant que possible et éviter les doublons de chaîne.

## Contrat métier figé

### Périmètre

- produits simples
- services simples

### Convention de pourcentage

- `10 = 10%`

### Champs sources persistés

- `clichaumeil_pa_support`
- `clichaumeil_pa_sav`
- `clichaumeil_pa_machine`
- `clichaumeil_pa_encre`
- `clichaumeil_pa_mo`
- `clichaumeil_conditionnement_percent`
- `clichaumeil_transport_percent`
- `clichaumeil_fg_percent`

### Champs calculés persistés

- `clichaumeil_pa_fg`
- `product.cost_price`

### Champ calculé non persisté

- `clichaumeil_total_costs`

### Règles de normalisation

- montant vide ou absent sur les PA : `0`
- `% conditionnement` vide ou absent : `0`
- `% transport` vide ou absent : `0`
- `% frais généraux` vide ou absent : bloque uniquement le calcul final
- arrondi montants : `price2num(..., 4)`
- arrondi taux : stockage et comparaison à 4 décimales

### Règle critique

Si `FG%` est vide :
- calcul intermédiaire autorisé
- affichage de `Total coûts` autorisé
- calcul final interdit pour `clichaumeil_pa_fg`
- calcul final interdit pour `product.cost_price`
- ne jamais écraser les anciennes valeurs persistées de `clichaumeil_pa_fg` et `product.cost_price`

## Formule métier R2

```text
baseCost = pa_support + pa_sav + pa_machine + pa_encre + pa_mo
packagingAmount = baseCost * conditionnement_percent / 100
transportAmount = baseCost * transport_percent / 100
totalCosts = baseCost + packagingAmount + transportAmount
paFg = totalCosts * fg_percent / 100
costPrice = totalCosts + paFg
```

Doctrine d'implémentation :
- arrondir à 4 décimales après chaque étape métier utile
- ne pas dupliquer la formule hors calculateur

## Structures internes

### CostBreakdownInput

```text
paSupport: float
paSav: float
paMachine: float
paEncre: float
paMo: float
packagingPercent: float
transportPercent: float
fgPercent: ?float
```

### CostBreakdownResult

```text
baseCost: float
packagingAmount: float
transportAmount: float
totalCosts: float
paFg: ?float
costPrice: ?float
isFinalComputable: bool
blockingReason: ?string
```

## Plan d'exécution par fichier

### 1. `custom/clichaumeil/class/CliChaumeilProductCost.class.php`

Objectif :
- remplacer l'implémentation actuelle trop compacte par un pipeline strict

Méthodes cibles :
- `ensureExtrafieldsLoaded(Product): void`
- `buildInput(Product): CostBreakdownInput|array`
- `compute(CostBreakdownInput|array): CostBreakdownResult|array`
- `syncComputedFields(Product, CostBreakdownResult|array, User): int`
- `getComputedBreakdown(Product): CostBreakdownResult|array`

Pseudo-code :

```text
calculateAndUpdateProductCostPriceFromExtrafields(product, user):
  si produit non supporté ou sans id
    return 0

  si sémaphore actif pour ce produit
    return 0

  activer sémaphore
  try:
    input = buildInput(product)
    result = compute(input)
    return syncComputedFields(product, result, user)
  finally:
    désactiver sémaphore
```

```text
buildInput(product):
  ensureExtrafieldsLoaded(product)

  input.paSupport = normalizeAmount(extrafield pa_support)
  input.paSav = normalizeAmount(extrafield pa_sav)
  input.paMachine = normalizeAmount(extrafield pa_machine)
  input.paEncre = normalizeAmount(extrafield pa_encre)
  input.paMo = normalizeAmount(extrafield pa_mo)

  input.packagingPercent = normalizePercent(extrafield conditionnement_percent) ?? 0
  input.transportPercent = normalizePercent(extrafield transport_percent) ?? 0
  input.fgPercent = normalizePercent(extrafield fg_percent)

  return input
```

```text
compute(input):
  baseCost = round4(paSupport + paSav + paMachine + paEncre + paMo)
  packagingAmount = round4(baseCost * packagingPercent / 100)
  transportAmount = round4(baseCost * transportPercent / 100)
  totalCosts = round4(baseCost + packagingAmount + transportAmount)

  si fgPercent est null:
    paFg = null
    costPrice = null
    isFinalComputable = false
    blockingReason = 'missing_fg_percent'
  sinon:
    paFg = round4(totalCosts * fgPercent / 100)
    costPrice = round4(totalCosts + paFg)
    isFinalComputable = true
    blockingReason = null

  retourner result
```

```text
syncComputedFields(product, result, user):
  ensureExtrafieldsLoaded(product)

  si result.isFinalComputable = false:
    ne pas appeler updateExtraField() sur clichaumeil_pa_fg
    ne pas appeler setValueFrom() sur cost_price
    return 0

  changes = 0

  si pa_fg courant différent de result.paFg:
    persister clichaumeil_pa_fg
    si erreur:
      dol_syslog(LOG_ERR)
      return -1
    changes++

  si cost_price courant différent de result.costPrice:
    persister product.cost_price
    si erreur:
      dol_syslog(LOG_ERR)
      return -1
    changes++

  return changes
```

Helpers attendus :
- `normalizeAmount($value): float`
- `normalizePercent($value): ?float`
- `valueDiffers($current, $target): bool`

Exigence critique :
- le calcul intermédiaire UI ne doit jamais provoquer un effet de bord BD si `FG%` est vide

### 2. `custom/clichaumeil/class/CostBreakdownInput.class.php`

Objectif :
- encapsuler les valeurs d'entrée
- interdire les tableaux implicites sans contrat

Pseudo-code :

```text
classe simple de transport de données
pas de logique métier
constructeur ou propriétés publiques typées selon compatibilité du module
```

### 3. `custom/clichaumeil/class/CostBreakdownResult.class.php`

Objectif :
- encapsuler les sorties de calcul
- transporter clairement l'état `isFinalComputable`

Pseudo-code :

```text
classe simple de transport de données
inclut totalCosts, paFg, costPrice, blockingReason
pas de persistance
```

### 4. `custom/clichaumeil/core/modules/modClichaumeil.class.php`

Objectif :
- déclarer les 2 nouveaux extrafields
- conserver les permissions actuelles
- rendre l'initialisation idempotente

Extrafields à ajouter :
- `clichaumeil_conditionnement_percent`
- `clichaumeil_transport_percent`

Ordre cible :
- separator
- pa_support
- pa_sav
- pa_machine
- pa_encre
- pa_mo
- conditionnement_percent
- transport_percent
- fg_percent
- pa_fg

Pseudo-code :

```text
pour chaque extrafield attendu:
  si extrafield absent:
    addExtraField(...)
  sinon:
    ne pas supprimer
    ne pas recréer brutalement
    mettre à jour proprement si l'API le permet

ne jamais créer d'extrafield pour clichaumeil_total_costs

si constante CLICHAUMEIL_DEFAULT_OVERHEAD_RATE absente:
  initialiser la valeur actuelle par défaut
```

Point de vigilance :
- réactivation module sans doublon
- aucune perte de valeur existante

### 5. `custom/clichaumeil/class/actions_clichaumeil.class.php`

Objectif :
- étendre l'édition UI
- piloter le recalcul unifié
- sécuriser le flux import
- empêcher toute confusion entre vrai extrafield et champ virtuel

Constantes :

```text
COST_BREAKDOWN_FIELDS = [
  'clichaumeil_pa_support',
  'clichaumeil_pa_sav',
  'clichaumeil_pa_machine',
  'clichaumeil_pa_encre',
  'clichaumeil_pa_mo',
  'clichaumeil_conditionnement_percent',
  'clichaumeil_transport_percent',
  'clichaumeil_fg_percent',
  'clichaumeil_pa_fg'
]
```

Règle :
- ne jamais inclure `clichaumeil_total_costs`

#### `doActions()`

Pseudo-code :

```text
si contexte != pricesuppliercard:
  return 0

si action != update_extrafields:
  return 0

si flag clichaumeil_cost_breakdown != 1:
  return 0

si attr non présent dans COST_BREAKDOWN_FIELDS:
  return 0

vérifier token
vérifier droit read_cost_composition
charger produit + optionals
handleCostUpdate(...)
si erreur:
  return -1

message succès
return 0
```

#### `handleCostUpdate()`

Pseudo-code :

```text
persister uniquement le champ édité
si erreur de setOptionalsFromPost ou insertExtraFields:
  return -1

result = calculateur unifié
si result < 0:
  return -1

breakdown = calculateur.getComputedBreakdown(product)
si breakdown.isFinalComputable = false:
  afficher message fonctionnel informatif non bloquant

return 0
```

#### Rendu UI

Lignes à afficher :
- les 5 PA
- `% conditionnement`
- `% transport`
- `Total coûts` en lecture seule
- `% frais généraux`
- `PA frais généraux` en lecture seule

Règles pour `Total coûts` :
- jamais transmis en POST
- jamais enregistré en extrafield
- jamais passé à `showInputField()`
- rendu uniquement comme ligne HTML custom

#### `afterImportInsert()`

Position cible :
- conserver le hook existant étape 6

Contrat import :
- colonne absente sur un champ optionnel : valeur existante conservée
- colonne présente vide sur un champ optionnel : valeur vide importée
- normalisation des montants/taux optionnels vides : `0`
- colonne `extra.clichaumeil_fg_percent` absente ou vide : erreur métier, aucun calcul final

Recommandation retenue :
- garder l'import plus strict que la création manuelle
- ne pas injecter automatiquement le `FG%` par défaut en import

Pseudo-code :

```text
si step != 6:
  return 0

si flux != produit:
  return 0

values = extractImportValues(parameters)

si FG absent ou vide:
  message métier
  return -1

charger produit minimal
hydrater uniquement les colonnes officielles présentes
persister les champs réellement fournis
appeler calculateur unifié

si erreur technique:
  return -1

return 0
```

Point critique :
- ne jamais laisser un développeur traiter `clichaumeil_total_costs` comme un vrai extrafield

### 6. `custom/clichaumeil/core/triggers/interface_99_modClichaumeil_ClichaumeilTriggers.class.php`

Objectif :
- recalcul unifié sur création et modification produit/service

Evénements supportés :
- `PRODUCT_CREATE`
- `PRODUCT_MODIFY`
- `PRODUCT_PRICE_MODIFY`

Pseudo-code :

```text
si action non supportée:
  return 0

si objet non Product ou type non supporté:
  return 0

si PRODUCT_CREATE:
  appliquer FG par défaut si absent
  laisser conditionnement/transport vides
  calculateur unifié

si PRODUCT_MODIFY ou PRODUCT_PRICE_MODIFY:
  fetch_optionals
  calculateur unifié

si erreur:
  alimenter this.errors
  return -1

return 0
```

Règle critique :
- une modification quelconque d'un ancien produit avec `FG%` vide ne doit jamais devenir bloquante

### 7. `custom/clichaumeil/admin/setup.php`

Objectif :
- conserver la constante existante
- expliciter la convention de pourcentage

Pseudo-code :

```text
sur le champ CLICHAUMEIL_DEFAULT_OVERHEAD_RATE:
  conserver type number, min 0, step 0.0001
  ajouter un tooltip ou libellé explicatif:
    "Convention : 10 = 10%"
```

Règle :
- pas de nouvelle constante de config

### 8. `custom/clichaumeil/langs/fr_FR/clichaumeil.lang`

Clés à ajouter :

```text
CliChaumeilConditionnementPercent=Taux conditionnement (%)
CliChaumeilTransportPercent=Taux transport (%)
CliChaumeilTotalCosts=Total coûts
CliChaumeilInfoMissingFgPercent=Calcul final non effectué : le champ Frais généraux (%) est vide.
```

Clés à corriger / harmoniser :
- libellé du droit : remplacer la notion de `coût` par `prix de revient`
- tooltip du taux par défaut : rappeler `10 = 10%`

### 9. `custom/clichaumeil/langs/en_US/clichaumeil.lang`

Ajouter les traductions EN des nouvelles clés FR.

Règle :
- pas de fallback FR implicite

### 10. `custom/clichaumeil/import_samples/products_cost_breakdown_sample.csv`

Objectif :
- réaligner les colonnes sur le parseur officiel

Colonnes attendues :
- `extra.clichaumeil_pa_support`
- `extra.clichaumeil_pa_sav`
- `extra.clichaumeil_pa_machine`
- `extra.clichaumeil_pa_encre`
- `extra.clichaumeil_pa_mo`
- `extra.clichaumeil_conditionnement_percent`
- `extra.clichaumeil_transport_percent`
- `extra.clichaumeil_fg_percent`

Règle :
- supprimer les alias historiques faux
- fournir des exemples métier avec `10 = 10%`

### 11. `custom/clichaumeil/import_samples/products_cost_breakdown_update_sample.csv`

Même logique que le sample create.

Règles documentées implicitement :
- champ absent = non modifié
- champ présent vide = vidé
- taux et montants optionnels vides = normalisation à `0`
- `FG%` vide ou absent = erreur métier

### 12. `custom/clichaumeil/ChangeLog.md`

Entrée à ajouter :
- ajout taux conditionnement / transport
- révision formule R2
- affichage `Total coûts` non persisté
- réalignement import
- absence de recalcul massif
- application à la prochaine modification

## Validation manuelle attendue

- activation / réactivation du module sans doublon d'extrafields
- aucun effacement des données existantes
- affichage sur produit simple avec droit
- affichage sur service simple avec droit
- aucune ligne visible sans droit
- saisie des seuls PA avec `FG%` vide :
  - `Total coûts` visible et correct
  - `PA FG` inchangé
  - `cost_price` inchangé
- saisie avec `conditionnement=10`, `transport=5`, `FG=20` :
  - vérifier tous les montants intermédiaires utiles
  - vérifier `cost_price`
- suppression ultérieure du `FG%` :
  - `Total coûts` reste visible
  - `PA FG` reste à sa valeur persistée
  - `cost_price` reste à sa valeur persistée
- modification d'un autre PA avec `FG%` toujours vide :
  - `Total coûts` se met à jour
  - `PA FG` inchangé
  - `cost_price` inchangé
- création manuelle produit/service avec `FG%` absent :
  - défaut injecté par trigger
  - calcul final effectué
- modification manuelle du `cost_price` natif seul :
  - valeur conservée
- modification ultérieure d'un champ composition :
  - la formule reprend la main
- import création avec colonnes officielles complètes :
  - calcul final correct
- import mise à jour sans colonne `FG%` :
  - erreur métier
- import mise à jour avec `FG%` vide :
  - erreur métier
- import sans colonnes `conditionnement/transport` :
  - valeurs existantes conservées
- import avec colonnes `conditionnement/transport` vides :
  - vidage logique puis normalisation à `0`
- vérification de l'alerte de marge négative sur les documents aval
- contrôle logs PHP / SQL sans erreur

## Analyse critique

La spec est exploitable, mais les risques ne sont pas dans la formule.

Les vrais risques sont :
- confusion entre champ virtuel et extrafield réel
- divergence de comportement entre UI, trigger et import
- règles d'arrondi appliquées de façon non homogène
- fragilité du rendu injecté par JS dans le DOM standard Dolibarr

La discipline d'implémentation doit donc être :
- une seule source de vérité pour la formule
- une seule source de vérité pour les noms de champs
- aucune persistance lors d'un calcul intermédiaire
- aucun patch du core

## Décisions prises

- création recommandée des DTO `CostBreakdownInput` et `CostBreakdownResult`
- import `create` et `update` : `FG%` obligatoire
- rendu UI : rester au plus proche du comportement actuel, sans exposer plus que nécessaire
- droit inchangé : masquage UI seulement
- correction du libellé FR du droit vers la notion de `prix de revient`

## Questions bloquantes restantes

Aucune question bloquante restante à ce stade.

Points à surveiller pendant l'implémentation, sans requalifier le périmètre :
- stratégie exacte d'idempotence si l'API extrafields ne permet pas une mise à jour propre de certains attributs
- vérification fine de la sémantique import si Dolibarr persiste déjà une partie de la ligne avant le hook étape 6
