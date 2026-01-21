# Changelog pour le module Dolibarr : [CustomiseCard](https://git.code42.io/dolibarr/modules/customisecard)

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# <span style='color:white;background-color:#ed6b00;border-radius:5px;padding: 5px;font-size:small'>Nouveautés</span> [v.20.0.00] - 2024-10-06

* Fixed :  
  * [#21] Compatibilité dolibarr v20
  * [#20] Il est maintenant possible de cacher une ligne 

# [v.18.0.00] - 2024-03-27

* Fixed : 
  * [#18] La personnalisation dépend de l'action 
  * [#17] Compatibilité php 8+ et v17+

# [v.16.2.01] - 2023-07-24

* Fixed :
  * [#16] Si H2G2 est manquant, une erreur est retournée demandant de le télécharger

# [v.16.2.00] - 2023-07-03

* Added :
  * [#12] Il est possible de masquer la partie "Date limite" du formulaire sur les document commerciaux
  * [#10] Intégration de H2G2
  * [#9] Il est possible de masquer / démasquer les informations des documents commerciaux
  * [#13] Masque de la colonne n'est plus effectif lors de l'ajout d'un produit
  * [#15] Message d'erreur sur dolibarr v13.0.xx

# [v.16.1.00] - 2023-06-12

* Added :
  * [#13] Lors de l'ajout d'un produit à un document commercial, la personnalisation est conservée
  * [#6] Il est possible de personnaliser la recherche avancée dans les documents commericaux

# [v.16.0.00] - 2023-05-23

* Added :
  * [#4] Il est possible d'afficher ou non le champ description sur les documents commerciaux
  * [#3] Il est possible de cacher les colonnes sur les documents commerciaux (Proposition commerical, commande et facture)

# [v.15.0.00] - 2022-09-13

* Fixed :
  * [#2] Compatibilité du module avec Dolibarr 15

# [v.14.0.00] - 2022-09-12

* Fixed :
  * [#1] Impossibilité de cacher les lignes

# [v.12.1.22] - 2022-09-05

- Fixed :
  - [H2G2 - #44] Nettoyage SweetAlert2 et ajout de la page migrations

# [v.12.1.21] - 2022-05-02

* [FIX] Bug lors de la modification d'une card en mode view aussi appliqué au mode create

# [v.12.1.2] - 2021-03-16

* [NEW] Page information

* [FIX] Mode multi entités bug lors de la première modification

# [v.12.1.1] - 2021-02-10

* [FIX] Nombres de lignes différentes sur la page card d'un tier en fonctions de l'origine de celui-ci ou bien si il est client ou non

```Customisation différente de la page tier en fonction de si il est client ou non```

# [v.12.1.0] - 2021-02-08

* [FIX] Correction de bug où les modifications faites sur une page impacté d'autres pages

```Attention: toutes modifications faites avec la version précédente seront perdues avec cette version et les suivantes ``` 

# [v.12.0.0]

>> Version initiale

* [NEW] Ajout de la possibilité de customisé les vues card de Dolibarr
    * Suppression de lignes
    * Mise en gras
    * Ajout de tooltips (compatible html)
    * Modification tu titre de la ligne
    * Cacher les boutons d'actions
    
* [NEW] Ajout de la possibilité de partager les customisation entre les entités
