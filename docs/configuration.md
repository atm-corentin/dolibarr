# clichaumeil — Documentation

> Fichier source de l'onglet de documentation intégré.
> Le titre h1 et tout contenu avant le premier h2 sont ignorés.
> h2 (`##`) = section principale, h3 (`###`) = sous-section.
> Inline supporté : **gras**, *italique*, `code`. Les citations (`>`) deviennent
> des encarts ("Attention"/"Warning" = rouge, "Note"/"Info" = bleu).

## Présentation

Module métier Chaumeil regroupant plusieurs fonctionnalités autour des devis,
commandes, achats et remises. Quatre grands domaines :

- **Commissions** — barème de coefficients et segmentation automatique des clients.
- **Sous-traitance** — sélection d'un sous-traitant depuis les propositions
  fournisseur, propagation du prix d'achat et création automatique de la commande.
- **RFA (remises de fin d'année)** — suivi fournisseur et client, synthèses annuelles.
- **Synchronisation des prix fournisseur** — mise à jour automatique des grilles
  tarifaires depuis l'API ANTALIS.

La configuration est répartie en onglets dans la page d'administration du module
(`Configuration > Modules > CliChaumeil`) : Généraux, Produits, Contrats,
Sous-traitance, Commissions, Connexions API, Documentation, À propos.

## Produits

L'onglet **Produits** paramètre le calcul du coût de revient et les lignes de devis
par défaut :

- **Taux de frais généraux par défaut (%)** (`CLICHAUMEIL_DEFAULT_OVERHEAD_RATE`) —
  valeur appliquée à la décomposition de coût produit quand aucun taux spécifique
  n'est saisi (convention : `10` = 10 %).
- **Produits/services de devis par défaut** (`CLICHAUMEIL_DEFAULT_PROPAL_PRODUCT_ID`) —
  liste des produits injectés automatiquement comme lignes « par défaut » à la
  création d'une proposition. Ces lignes sont protégées (suppression/édition gardées).
- **Catégorie produit cible** (`CLICHAUMEIL_PRODUCT_TARGET_CATEGORY`) — catégorie pour
  laquelle les champs hauteur/longueur (et le calcul de surface associé) sont activés.

> Note : la décomposition de coût (supports, SAV, machine, encre, main d'œuvre, frais
> de dossier, frais généraux) s'affiche et se saisit sur la fiche produit ; l'accès est
> contrôlé par le droit « lecture de la composition de coût produit ».

### Recalcul du prix de revient au clonage (VT-25)

Comportement **automatique**, sans paramétrage. Lors du **clonage** d'un devis client
(devis→devis) ou d'une commande client (commande→commande), chaque ligne clonée portant
un produit voit son **prix d'achat** (`buy_price_ht`) recalculé depuis le **prix de revient
du produit** (`cost_price`) : la valeur du produit si elle est strictement positive, sinon
`0`. Le prix fournisseur lié à la ligne (`fk_product_fournisseur_price`) est vidé, et
l'origine du recalcul est tracée dans un champ complémentaire caché de ligne
(`clichaumeil_cost_source`).

- Les lignes **sans produit** (lignes libres) ne sont pas modifiées.
- Aucun repli sur le PMP, le fournisseur le moins cher ou la nomenclature : seul
  `cost_price` est utilisé.
- La **transformation** d'un devis en commande n'est **pas** concernée (seul le clonage
  l'est).

## Contrats

L'onglet **Contrats** pilote la **révision/reconduction tarifaire** des contrats :

- **Responsables de la mise à jour des prix** (`CLICHAUMEIL_PRICING_UPDATE_MANAGERS`).
- **Délai de révision (années)** (`CLICHAUMEIL_REVIEW_YEAR_DELAY`, défaut 1) — nombre
  d'années ajouté à la date de révision d'un contrat à chaque passage.
- **Modèle d'email** (`CLICHAUMEIL_CRON_EMAIL_TEMPLATE`) — modèle (type « contrat »)
  utilisé pour les notifications.
- **Utilisateurs à notifier** (`CLICHAUMEIL_CRON_NOTIF_USERS`).

Un cron (désactivé par défaut) parcourt les contrats et applique la révision via les
champs complémentaires `clichaumeilreviewrate` (taux) et `clichaumeil_reviewdate`
(date de prochaine révision), puis notifie les responsables et utilisateurs configurés.

## Commissions

### Catégories et groupes

L'onglet **Commissions** permet d'associer :

- les quatre **catégories client** (Marché public, Sous-traitance, Nouveau, Ancien)
  à des catégories Dolibarr existantes ;
- les **groupes utilisateurs** correspondant aux profils (commercial, manager
  commercial, print manager, print management).

> Attention : si une catégorie ou un groupe n'est pas renseigné, un avertissement
> s'affiche sur la page. Renseignez-les avant d'activer la segmentation automatique.

### Coefficients

Les coefficients de commission sont gérés via un **dictionnaire éditable**
(`Accueil > Configuration > Dictionnaires > Coefficients de commission CliChaumeil`),
indexé par couple `(rôle, tag client)`. Chaque combinaison rôle + segment client ne
peut avoir qu'un seul coefficient.

> Attention : le dictionnaire est initialisé une seule fois à l'activation du module.
> Une réactivation ne réinsère rien si des lignes existent déjà — vos personnalisations
> de codes, tags et coefficients sont donc préservées.

### Segmentation automatique des clients

Un cron quotidien (désactivé par défaut) classe les clients en **Nouveau** ou
**Ancien** selon l'ancienneté et la récance de leurs factures, et leur applique la
catégorie correspondante.

## Sous-traitance

Sur une proposition ou commande client, le bouton **« Choisir un sous-traitant »**
ouvre la liste des propositions fournisseur liées. À la sélection :

1. la proposition fournisseur retenue est signée ;
2. son prix d'achat est **propagé** sur les lignes du document client ;
3. une **commande fournisseur validée** est créée à partir de la proposition ;
4. les autres propositions fournisseur liées sont refusées ;
5. un **email** avec le PDF de la commande est envoyé au sous-traitant.

Le même enchaînement se déclenche automatiquement lorsqu'une proposition fournisseur
est signée via le bouton d'acceptation standard de Dolibarr.

> Note : sur un document client **brouillon**, le prix de vente est recalculé selon le
> taux de marge/marque minimum (paramétré via Discountrules : surcharge tiers, puis
> produit, puis valeur globale). Sur un document **validé**, seul le prix d'achat est
> mis à jour ; la marge est recalculée à l'affichage.

À la validation d'une proposition ou commande, un **avertissement en rouge** signale
toute ligne dont la marge passe sous le minimum configuré.

### Paramétrage

L'onglet **Sous-traitance** regroupe les réglages du workflow (modèle d'email de
commande fournisseur, contacts, etc.). L'email de commande utilise le modèle défini
par la constante de configuration dédiée ; si aucun modèle valide n'est trouvé, la
commande est créée mais l'email n'est pas envoyé (avertissement).

## RFA (remises de fin d'année)

Le module gère deux types de RFA, **fournisseur** et **client**, accessibles depuis
des entrées de menu dédiées et des onglets sur la fiche tiers.

- Une **RFA** définit des paliers de chiffre d'affaires et les taux de remise associés.
- Les **synthèses annuelles** agrègent le chiffre d'affaires (factures **closes**) par
  tiers et par année, en remontant la hiérarchie des sociétés (société mère / filles),
  puis déterminent le meilleur palier atteint et le montant de remise.

> Note : seules les factures au statut « Payée / Close » sont comptabilisées. La
> synthèse se reconstruit intégralement pour l'année concernée à chaque recalcul.

Le recalcul peut être lancé par cron (un job par type) ou via le script de
reconstruction, en précisant l'année et le type de RFA.

## Connexions API (synchronisation des prix fournisseur)

L'onglet **Connexions API** configure les connecteurs de synchronisation de prix.
Le premier connecteur est **ANTALIS** (SOAP) :

- identifiants de connexion (URL, login, **mot de passe stocké chiffré**, identifiants
  de compte) ;
- **mode simulation (dry-run)** pour tester sans rien écrire ;
- limite du nombre de produits (pour validation) ;
- destinataires et politique d'envoi du rapport (jamais / erreurs / erreurs+alertes /
  toujours) ;
- bouton de **test de connexion** et de **test d'email**.

Un cron quotidien réconcilie les grilles ANTALIS avec les prix d'achat Dolibarr :

- mise à jour du prix d'achat uniquement s'il diffère (conservation de l'historique) ;
- réconciliation **par unité de prix** (feuille, rame, lot, m²…), pas par quantité ;
- mode **mise à jour seule** (aucune création de palier) ;
- garde-fous : nouvelle tentative en cas d'erreur SOAP, arrêt automatique après
  plusieurs lots en échec, et **plafond du pourcentage de lignes pouvant être
  désactivées** lors d'un même passage (protection contre une réponse API partielle).

Le résultat du dernier passage est affiché sur la page (badges colorés) et, selon la
politique choisie, envoyé par email sous forme de rapport HTML détaillé.

## FAQ

> Note : pour toute question fonctionnelle ou demande d'évolution, contactez
> ATM Consulting.

### La réactivation du module échoue sur une erreur de doublon

Corrigé : la migration du dictionnaire de commissions est désormais une opération
à passage unique. Si le problème persiste sur une ancienne version, mettez le module
à jour avant de le réactiver.

### Limitation connue — dictionnaire de coefficients de commission

Le dictionnaire de coefficients est actuellement une **donnée de référence** :
il est éditable et la segmentation client fonctionne, mais aucun calcul automatique
de commission ne consomme encore ces coefficients. Modifier une valeur n'a donc pas
d'effet sur un montant calculé tant que le raccordement n'a pas été réalisé.
Contactez ATM Consulting pour activer ce branchement.
