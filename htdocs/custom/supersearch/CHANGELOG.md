# CHANGELOG SUPERSEARCH CODE42 FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# <span style='color:white;background-color:#ed6b00;border-radius:5px;padding: 5px;font-size:small'>Nouveautés</span> [18.5.00] - 2025-09-??

- Changed :
    - [#58] Possibilité d'ouvrir un résultat dans un nouvel écran
- Added :
    - [#55] Possibilité de masquer un index de recherche
- Fixed
  - [#72] Apparition du titre sur Projet, picto Article à jour, Société n'apparaît plus dans les résultats Propal

#  [18.4.02] - 2025-10-14

- Fixed :
    - [#69] Une nouvelle migration sur cette version supprime tous les éléments de la table supersearch_customization et on réinsère à nouveau tous les paramètres par défaut
    - [#70] Retrait de l'url de mapping sur le fichier js instantsearch.js

# [18.4.01] - 2025-09-25

- Fixed :
    - [#67] Un nouveau droit d'accès à la recherche est disponible

# [18.4.00] - 2025-09-08

- Changed :
    - [#65] Mise à jour de la USER_DOC
    - [#59] Masquage de l'entité dans les résultats si Multicompany n'est pas actif

# [18.3.02] - 2025-06-06

- Fixed :
  - [#63] Le bouton récupère bien les données depuis Meilisearch

# [18.3.01] - 2025-05-28

- Fixed :
  - [#62] La table "supersearch_customization" ce créer correctement
  
# [18.3.00] - 2025-05-14

- Added :
  - [#29] Il est desormais possible de personnaliser la couleur, l'url et le picto de chaque index
  - [#44] Les index peuvent être afficher dans l'ordre souhaité.
  - [#48] Les index incluent des champs affichés par défaut.
  - [#49] Des champs ont été ajouté sur l'affichage des interventions pour le client Cotrolia
  - [#50] Les clé peuvent etre retirer/remise
  - [#53] Le popup affiche un loader pendant le chargement ainsi que le numéro de version du module
  - [#56] Un message apparait sur le popup si le serveur meilisearch est inaccesible
  - [#60] le popup n'affiche plus la scrollbar du loader

- Fixed :
    - [#42] La recherche est possible sur les entités controllé par l'entité mère
    - [#51] L’information affichée dans la topbar n’apparaît que si une nouvelle version a été déployée dans les 30 minutes.
    
# [18.2.01] - 2025-03-03

- Fixed :
  - [#46] SuperSearch ne recherche / n'affiche plus les index indésirable
  - [#40] SuperSearch ne creer plus d'index indésirable
  - [#39] La recherche ne se fait plus sur le mot exact
  - [#38] Le module est compatible avec le thème eldy
  
# [18.2.00] - 2025-01-22

- Added :
  - [#33] Ajout des objets 'Contact' et 'Projet'

- Changed :
  - [#26] Changement de nom et de logo : Searchplus -> SuperSearch

# [18.1.00] - 2024-12-05

- Added :
  - [#6] Ajout de la mécanique d'ajout et suppression des objets Dolibarr dans Meilisearch
  - [#9] page de gestion des tâches
  - [#5] Ajout d'une page pour migrer des données de dolibarr vers meilisearch
  - [#14] Ajout d'un bouton pour vider l'index
  - [#15] Ajout d'une documentation utilisateur
  - [#24] Ajout du uniqueId dolibarr dans la page de configuration du module
  - [#27] Ajout d'un bouton pour ouvrir le popup de recherche

- Fixed :
  - [#12] Compatibilité dolibarr v13 / php7.4
  - [#13] Correction des problèmes de vérification des const et des appels aux fonctions du core.
  - [#17] La Clée principale est utilisé qu'une seule fois puis supprimer.
  - [#18] Le module ne bloque pas le 'CRUD' de dolibarr
  - [#20] Le css des listes à puces n'impactent plus le dolibarr
  - [#22] Correction d'un bug avec ProduitRapide qui créait un nouvel index
  - [#30] Correction d'un bug avec les noms de classe des objets Intervention & Produit

- Changed :
  - [#7] Changement du nom des index pour les liens Index-Fiche
  - [#8] Modification de la gestion des clés
  - [#14] L'exportation ne se fait plus à l'initialisation du module 
  - [#16] Amélioration de popup
  - [#21] Amélioration du design du popup
  - [#25] Amélioration des pages de configuration
  - [#34] Modification de la taille du logo
  
# [18.0.00] - 2024-09-17

- Added :
  - [#1] Initialisation du module
  - [#3] Popup de recherche
  - [#4] integration instantsearch
  
- Changed :
  - [#2] Modification de la page de configuration pour le serveur et les options meilisearch
