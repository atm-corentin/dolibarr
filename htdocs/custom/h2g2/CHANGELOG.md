# Changelog pour le module Dolibarr : [H2G2](https://git.code42.io/dolibarr/modules/project/suite-services/h2g2/)

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

#  <span style='color:white;background-color:#ed6b00;border-radius:5px;padding: 5px;font-size:small'>Nouveautés</span> [20.3.00] 2025-08-29

- Added :
  - [#96] Fonctions génériques Booléen/Fontawesome/Tooltip/Check variable vide
  
- Changed :
  - [#117] Fermer une "topbar" ne recharge plus la page
  
- Fixed :
  - [#120] le bouton ScrollToTop est accessible sur tablette et mobile

#  [20.2.01] 2025-08-20

- Fixed : 
  - [#118] Le multi formulaire fonctionne correctement

# [20.2.00] 2025-08-06

- Fixed :
  - [#110] Les textes dans les boutons multientrées sont collé à gauche
  - [#112] Les boutons multientrées ne passe plus derrière les menus

# [20.1.04] 2025-08-06

- Added :
  - [#107] Librairie H2G2 pour la création rapide de formulaire multiple dans l'admin des modules
  - [#95] Ajout de fonctions de log destinées aux développeurs
  - [#108] Affiche une barre d'information lors de l'activation d'un module si une nouvelle version est détectée

- Fixed :
  - [#105] Le texte contenu dans les boutons multientrée est lisible qu'importe la couleur
  - [#106] Il est possible d'utiliser un article "iframe" dans le popup

# [20.1.03] 2025-07-03

- Fixed :
  - [#89] Correction d'un problème de vérification lorsqu'aucune news n'est en cours ou visible par l'utilisateur
  
# [20.1.02] 2025-06-06

- Fixed :
  - [#104] La mise à jour du menu "version" est plus stricte

# [20.1.01] 2025-05-28

- Fixed :
  - [#103] la version du module est mis à jour dans les menus adéquat

# [20.1.00] 2025-05-21

- Added :
  - [#98] Ajout d'un bouton de scroll vers le bas dans la boite de dialogue
  - [#99] Affichage d’un article dans le menu "Nouveautés" si défini

- Changed :
  - [#102] Traduction du libellé du menu 'Nouveauté'
  - [#86] La page de configuration des news a été revue
  
- Fixed : 
  - [#63] Des migrations inexistantes sont supprimées de la base de données afin d'éviter les blocages de versions
  - [#101] La personnalisation des menus des modules Code42 n'est pas supprimer à chaque réactivation

#  [20.0.01] 2025-01-20
- Added :
  - [#92] Ajout du multiselect d'entité
  
- Fixed :
  - [#91] Une Topbar identique ne se crée pas si elle existe déjà

# [20.0.00] 2024-11-13

- Added :
  - [#73] Ajout des nouveautés sous forme de "topbar"
  - [#74] Il est possible d'ajouter un article à une nouveauté et de lire le contenu dedans
  - [#75] Ajout de la fonctionnalité de copier/coller en 1 clic sur les informations des fiches
  - [#76] Ajout d'un bouton "ScrollToTop"

- Fixed :
  - [#84] Le bouton pour scroll est disponible dans les listings
  - [#85] Le bouton pour scroll est fixer en vu mobile
  - [#87] Les Info Topbar se ferment correctement

# [19.0.00] 2024-09-16

- Added :
  - [#81] Les nouvelles versions des modules H2G2 sont vérifiées par la fonctionnalité core de Dolibarr

- Changed :
  - [#79] Les boutons multientrées sont maintenant plus large, les options sont cliquable entièrement et un effet surlignage s'applique sur les options
  - [#78] Correction d'un comportement avec les boutons multientrées qui créer un décalage en bas de page
  - [#77] Les options des boutons multientrées s'accordent avec la couleur du bouton principal et/ou de l'action du bouton

# [18.2.00] 2024-04-18

- Changed :
  - [#70] Les tooltips des boutons multi entrées ont été repensé
  
- Fixed :
  - [#71] La qualité du code à été corrigée
  - [#55] Le Système de nouveauté a été corrigé.
  - [#54] ils est possible de fermer les boutons multiselect si l'on clique sur un autre bouton multiselect

# [18.1.00] 2024-01-29

- Fixed :
  - [#62] les listing H2G2 sont compatible avec le module quicklist

# [18.0.00] 2023-12-13

- Changed :
  - [#66] La page consacrée au thème a été revue dû au nouveau fonctionnement de celui-ci

- Fixed : 
  - [#67] Plus de message d'erreur dans les pages de migrations
  - [#64] Plus de message d'erreur à l'utilisation des boutons multi-select H2G2

# [16.0.00] 2023-05-02

- Added :
  - [#60] Une fonction d'ajout et de suppression de clés de traduction a été ajouté à TheGalaxy

# [15.1.00] 2023-03-21

- Added :
  - [#56] Ajout d'un menu pour avoir acces aux informations sur le thème
  - [#50] - Multiselect2 avec ouverture vers le haut ou vers le bas via un paramètre de la fonction


# [15.0.12] - 2023-02-20

- Fixed :
  - [#57] Le path du module extrafield est maintenant accessible

- Added :
  - [#52] - Ajout de la documentation pour les boutons colorés, up/down et create/delete
  - [#50] - Multiselect2 avec ouverture vers le haut ou vers le bas via un paramètre de la fonction


# [15.0.11] - 2022-??-??

- Added :
  - [#51] Boutons Standard "Création" et "Suppresion" + Ajout des boutons et picto colorés

- Removed :
  - [#16] Système de localisation supprimé du module

- Changed :
  - [#43] Lorsqu'une nouveautée est lu, l'évènement lié est mis a 100%
  - [#47] Possiblité d'insérer des emojis et des icônes via la création de news par CLI
  - [#48] Pour vérifier les nouveaux versions des modules de Code 42 une configuration est désormais disponible

# [15.0.10] - 2022-08-16

- Added :
  - [#41] Système de nouveautés pour les modules
  - [#42] Ajout de nouveautés en CLI

# [15.0.05] - 2022-07-12

- Added :
  - Ajout de la possibilité d'avoir un filtre par défaut sur les listings par défaut

# [15.0.04] - 2022-06-27

- Added :

  - Ajout de la notion de withPopup pour les actions de masses des listings par défaut

- Changed :
  - Modification de l'adresse cible de la page nous contacter

# [15.0.03] - 2022-05-18

- Added :
  - [#38] Ajout d'une page de contact

# [15.0.02] - 2022-05-13

- Added :
  - [#217] Ajout de la posibililité de filtrer les listes via les champs dates

# [15.0.01] - 2022-05-06

- Fixed :
  - Erreurs liées à la fonction update du QueryBuilder

# [15.0.00] - 2022-03-31

- Added :

  - [#29] Ajout des boutons multiples
  - [#35] Ajout d'une librairie de dev
  - Les listings H2G2 intègrent une valeur par défaut aux champs de recherche avec les paramètres GET

- Fixed :
  - Erreur PHP lors de la vérification des dernières versions de module disponible en ligne
  - Problème de statut sur les listings par défaut

# [13.1.21] - 2022-02-28

- Added :

  - La pagination est maintenant compatible avec la global MAIN_PAGESIZE_CHOICES
  - Il est possible de sélectionner plusieurs ligne d'un coup en cliquant glissant
  - Ajout d'un template de listing

- Fixed :

  - Les tooltips sont maintenant interprétées dans les listings

- Changed :
  - Amélioration visuelle pour les filtres
  - Amélioration visuelle pour la pagination

# [13.1.20] - 2022-02-24

- Added :

  - [#22] Ajout d'un wizard désactivable pour annoncer un module installé en version supérieur mais non réactivé
  - [#27] Ajout d'un nouveau système de listing

- Fixed :
  - Optimisation de la taille des images

# [13.1.11] - 2021-09-11

- Added :

  - Ajout de la possibilité de réinitialiser la version initial d'un module

- Fixed :
  - Bug lors de la mise à jour d'un module

# [13.1.10] - 2021-08-23

- Added :

  - [#20] Ajout d'une page de gestion des migrations

- Changed :

  - [#8] Ajout d'informations complémentaires concernant la section Dolibarr de la page information module
  - [#17] Ajout d'informations complémentaires concernant la section Module de la page information module
  - [#19] Ajout d'informations complémentaires concernant la section PHP de la page information module

- Fixed :
  - [#18] Ajout d'une documentation concernant la vérification à effectuer avant d'utiliser la classe `TheGalaxy`

# [13.1.0] - 2021-06-30

- Ajout d'un assistant de construction de requêtes (QueryBuilder)
- Ajout d'un gestionnaire de version de base de données pour les modules (MigrationManager)
- Ajout d'une nouvelle classe de descripteur de module

# [13.0.1] - 2021-05-11

- Optimisation de la taille des images

# [13.0.0] - 2021-03-24

> Version initiale

- Ajout de page générique d'information d'un module
