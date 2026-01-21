# CHANGELOG pour le module Dolibarr :  [LaReponse](https://git.code42.io/dolibarr/modules/project/suite-services/lareponse)

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# <span style='color:white;background-color:#ed6b00;border-radius:5px;padding: 5px;font-size:small'>Nouveautés</span> [v20.3.00] - 2025-10-16

- Changed :
    - [#296] Pleine utilisation de la hauteur de l'écran lors de l'édition d'un article

- Added :
  - [#89] Ajout d'un sommaire cliquable
  - [#295] Possibilité de masquer les sommaires

# [v20.2.00] - 2025-08-29

- Added :
  - [#263] Ajout de modèle d'article dans le mode 'edit'

- Fixed :
  - [#287] Il est à nouveau possible de selectionner le mode de l'editeur WYSIWYG
  - [#288] Uniformisation du CSS concernant l'étoile "favoris"
  - [#289] La page d’article publique a été améliorée
  - [#293] Le code à été corrigé pour plus de clarté

# [v20.1.01] - 2025-08-18

- Added :
  - [#284] Champs "Favoris" dans les listings

- Fixed :
  - [#285] Le champ des tags dans le listing des articles n’est plus masqué
  - [#273] Uniformisation des popups LaRéponse

# [v20.1.00] - 2025-07-16

- Fixed :
  - [#278] Modification du droits de correction
  - [#279] Affichage des boutons "Ancre" et "Surlignage" dans la barre WYSIWYG réduite
  - [#280] Il est possible de masquer les images dans l'infobulle d'un article
  - [#281] Le mode de l'editeur WYSIWYG est de nouveau disponible

# [v20.0.01] - 2025-05-15

- Fixed :
  - [#274] Le message d'erreur à la sauvegarde d'un article n'apparait plus
  - [#275] Le sélecteur de tag est mieux affiché sur les listings
  - [#277] Les tags sont cloner

# [v20.0.00] - 2025-03-03

- Added :
  - [#268] Ajout d'un mode "URL"/"Iframe" pour les articles

# [v19.2.03] - 2025-02-18

- Fixed :
  - [#267] Modification des clés de traduction des droits pour éviter les conflits avec d'autres modules 

# [v19.2.02] - 2024-12-30

- Added :
  - [#262] Le bandeau dans les pages d'articles publique est personnalisable dans la page de configuration

- Fixed :
  - [#261] Si on effectue une recherche sur le tag et le titre/contenu, le tag est bien pris en compte ans la recherche

# [v19.2.01] - 2024-30-10

- Added : 
    - [#257] Une pagination est disponible pour afficher tous les commentaires
    - [#256] Il est désormais possible de modifier un commentaire

- Fixed :
    - [#259] Les heures des commentaires des articles sont corrigées
    - [#255] Les erreurs dans le menu en php8 si l'utilisateur n'a pas les droits LaReponse sont corrigées
    - [#254] Il est possible de supprimer ses propres commentaires sans avoir le droit de suppression des articles
    - [#253] Les icônes fontawesome sont corrigées

# [v19.2.00] - 2024-09-05

- Added :
    - [#245] Mise en forme bouton modifier
    - [#116] Notification des utilisateurs lors de la modification d'un article à l'aide d'une tâche cron
    - [#210] Ajout d'événements sur les articles
    - [#177] Mise en place d'un WIZARD / ASSISTANT à la création d'un nouvel article

- Changed :
    - [#239] Modification d'une consigne sur l'importation d'article
    - [#242] Les tags sont maintenant filtré alphabetiquement par module puis par label

- Fixed :
    - [#244] Retrait des mauvais insert de version de migration en base de données
    - [#241] Fixe du type du tag lorsque celui-ci est inconnu
    - [#246] Les articles associés n'était plus filtré correctement
    - [#248] Correction dans le listing des utilisateurs de création/modification d'article
    - [#249] Correction d'un problème qui empecher de mettre en favoris les articles
    - [#250] L'affichage des tags n'est plus cassé avec une souris branchée

# [v19.1.01] - 2024-07-30

- Added :
  - [#229] Il est possible de créer un article via le menu de création rapide
  - [#227] Un bouton "Enregistrer et Quitter" à été ajouté sur la page d'édition d'un article
  - [#232] Multiselect sur les Status d'article
  
- Changed:
  - [#237] Index - Article cloturer
  
- Fixed :
  - [#226] le Formulaire n'est plus soumit lorsqu'on appuie sur la touche entrée
  - [#230] La largeur du listing de tag en mode édition a été agrandie
  - [#236] Il est possible de rechercher "'", "\" ou "-" dans le contenu ou le titre d'un article
  - [#234] La longueur des tags est fixer à 500px maximum
  - [#231] Erreur de comptage des articles associés
  
# [v19.1.00] - 2024-07-29

- Added:
  - [#214] les badges "Interne" et "Privé" ont été ajoutés sur l'article
  - [#213] Ajout des filtres sur l'état, la publication, les dates et les utilisateurs dans le listing d'article
  - [#212] Un bouton "Enregistrer et Continuer" à été ajouté sur la page d'édition d'un article
  - [#217] Le nombre de commentaire est afficher dans l'onglet Article de celui-ci
  - [#233] Il est possible de choisir le mode de l'editeur WYSIWYG
  - [#224] Ajout des tags de couleur sur le listing
  
- Changed:
  - [#216] Le Tooltip d'un article affiches les premiers caractères de l'article, les tags et le rédacteur.
  - [#215] Les tags LaReponse mènent vers les articles associés.
  - [#225] Le Wizard a été déplacé en haut sur les listings d'article.

- Fixed:
  - [#211] Les boutons de l'article sont fixés en bas
  - [#219] Les articles publique ne sont plus centré sur la page
  - [#220] Les noms des utilisateurs sont correctement lisible sur les listings
  - [#222] Le bouton "Copier le lien public" a été réparé
  
# [v19.0.00] - 2024-03-29
- Added:
  - [#200] Compatibilité Dolibarr v19
  - [#174] Ajout de l'état "Clos" pour un article

- Fixed :
  - [#195] [ARTICLE] Problème sur "copier le lien d'un article"
  - [#204] Article Favori
  - [#205] Consultation des articles d'une autre entité
  
- Changed :
  - [#201] Evolution tableau de bord
  - [#206] Facilité la suppression d'un tag

# [v18.1.00] - 2024-02-16
- Changed :
  - [#197] [ARTICLE] Augmenter la capacité de stockage du contenu
  - [#198] Mettre les articles les plus récents en fonction de la date de modification
  
- Fixed :
  - [#202] Message d'erreur sur la card d'un article en tant que utilisateur
  - [#199] Import article

# [v18.0.00] - 2023-12-20
- Added :
  - [#188] Compatibilité PHP 8.x / DOLIBARR 17, 18 & 19
  - [#190] Test PLAYWRIGHT #188
  
- Fixed :
  - [#192] Nombreux messages d'erreurs PHP 8 - Dolibarr 17 & Dolibarr 18
  - [#191] Impossible de MODIFIER un article

- Added :
  - [#188] Resolve "Compatibilité PHP 8.x / DOLIBARR 17, 18 & 19"

- Fixed :
  - [#192] Erreur LR avec un compte utilisateur

# [v16.3.00] - 2023-09-14

- Added :
  - [#184] Listing des articles dans Gestion de Parc
  - [#187] Mettre un badge pour avoir le nombre d'articles associés sur les Modules Projet, Produit, Tiers, Contact, Ticket et Gestion de parc
  - [#175] Création d'article avec une préselection de TAG
  - [#159] [TIERS] Ajout des tags Gestion de Parc dans le listing des article
  - [#189] [ARTICLE] Retirer l'article sélectionné de base dans l'onglet "Article Associés"
  
- Changed :
  - [#185] Nom de l'article dans le header
  
# [v16.2.00] - 2023-07-28

- Added :
  - [#155] Listing des articles liés via les TAGS similaires
  - [#167] Indiquer avec un badge le nombre d'articles associés
  - [#156] [TICKET] Listing des articles
  - [#173] Mise à jour du Changelog
  - [#168] Test Playwright sur le badge et les articles associés

- Changed :
  - [#179] Correction des headers dans le listing des articles
  - [#180] Retour List - Article
  - [#171] Renommer l'onglet Fiche en Article

- Fixed :
  - [#169] Le collapse/uncollapse refonctionne
  - [#181] Correction de l'import du module si H2G2 n'est pas présent
  - [#172] Code Quality
  - [#182] Token CSRF

# [v16.1.00] -  2023-07-07

- Added :
  - [#150] CARD - Retour List
  - [#160] Ajouté créateur article dans la card
  - [#158] Liste article
  - [#146] un test Playwright a été ajouter

- Changed :
  - [#152] Si un utilisateur DOLIBARR est désactivé, il ne faut pas barrer le nom d'un créateur d'article
  - [#151] UI - Recherche TAG
  - [#164] Le contenu de la documentation utilisateur a été revu
  - [#165] Description du module
  - [#170] Changelog - Mise à jour

- Fixed :
  - [#138] Tag -> Même si option activé on peut supprimer
  - [#157] ICÔNE manquant et uniformité
  - [#142] Supprimer l'appel a sweetalert en CDN lors de la création d'un tag
  - [#164] Le contenu de la documentation utilisateur a été revu
  - [#163] les images sont rentre maintenant dans un article
  - [#161] l'étoile "favori" d'un article est de nouveau en jaune  
  - [#133] L'utilisateur de modification n'est pas enregistré

# [v16.0.00] - 2023-06-14

- Added :
  - [#148] Rendre la page article_list.php disponible en tant qu'onglet

- Changed :
  - [#147] L'UI de article_card est + optimiser
  - [#154] Le contenu du README à été mis à jour
  
- Fixed :
  - [#145] un focus et une plus grande zone de recherche sont appliquer sur le champ titre + placeholder dans le champ
  - [#143] le module est compatible dolibarrv13
  - [#134] Nous avons maintenant les types d'objets Projet, Produit, Tiers, Contact, etc au lieu de Gestion de Parc dans le select des tags du listing des articles
  - [#153] Renommer Onglet LaRéponse en Articles
  - [#149] Problème de token CRSF pour la configuration des tags

# [v15.0.00] - 2022-09-14

- Added :
  - [#140] Ajout de la page nous contacter

# [v14.1.00] - 2022-09-13

- Fixed :
  - [#129] L'import d'image est fonctionnel
  - [#130] Les tags claires sont désormais visibles
  - [#131] Les images dans le module laréponse sont fonctionnelles
  - [#137] La date des articles est fonctionnelle
  - [#139] La configuration de LaRéponse est plus simple

- Changed :
  - [#122] Design des boutons sur la carte d'un article revue
  - [#125] Hiérarchie des tags
  - [#127] Le lien publique est désormais copiable
  - [#128] L'icône Lareponse a été redesigné

# [v14.0.00] - 2022-04-08

* Added :
  * [#87] Mise en place de The Galaxy sur le module
  * [#112] Mise en place de partage d'articles/tags entre différentes entités
  * [#106] Mis en place de l'arborescence des tags du module
  * [#115] Ajout d'un nouveau droit de publication
  
* Changed :
  * [#121] Le droit ReadObjectImages est désormais déprécier
  * [#119] La page list tags ainsi que la page List tag est désormais accessible pour un utilisateur externe
  * [#118] Les widgets situé sur le tableau de bord du module n'affichent plus les articles privés
  * [#123] Modification de l'attribut Publique à interne sur les articles
 
# [v13.0.02] - 2021-02-28

* Added :
  * Suppression des Fichiers joints et Evénements d'un Tag
  * Modification de la position des boutons dans page d'un article
  * Modifier les nombres en fonction de la configuration de Dolibarr dans les widgets du tableau de bord du module
  * Ajout d'une protection paramétrable lors de la suppression des Tags empêchant la suppression de celui-ci si il est lié à des objets
  * [#144] Mise en place du picto de la reponse sur le gestionnaire des modules
  
* Fixed :
  * Correction de l'affichage des données dans le widget "les 5 dernières modifications"
  * Supression de ckeditor
  * Modification de la valeur de la redirection de la page afin que l'upload d'une image peut être effectué


  
# [v13.0.01]

* Added :
  * Ajout de filtre selon les tags dans le listing des articles
  
* Fixed :
  * Les messages stylisés dans le wizard sont désormais pris en compte
  * Lors du clonage d'un article, la date de création prend en compte la date actuelle
  * La modification d'un article met bien à jour la date de modification



# [v13.0.0]

* Added : 
  * Compatibilité avec dolibarr 13
  
* Fixed :
    * Corrections sur l'affichage des box sur le menu d'accueil du menu
    * Correction lors de la création d'un tag depuis la création/modification d'un article
    * Correction lors de l'export d'une ou plusieurs liste(s)

# [v12.8.2]

* Added :
  * Changement du type d'encodage du texte d'un article. Le rendant ainsi compatible caractères non-utf8 (smiley, puces, ...)
  * Compatibilité Mermaid sur Firefox et Safari

# [v12.8.1]

* Added :
  * Exportation des articles en archive zip
  * Importation reformatée pour accueillir les archives zip
  * Possibilité d'ajouter des images avec l'éditeur de texte (disponible à la modification du contenu)

# [v12.8.0]

* Added :
  * Possibilité d'intégrer des tableaux mermaid (https://mermaid-js.github.io/mermaid/#/) dans :
    * Les articles
    * Les commentaires
  * Bouton "Imprimer" pour une impression ou un enregistrement pdf
  * Bouton "Exporter" pour exporter un ou des articles en fichier json, prêts à un importation
  * Lien d'importation pour importer les articles d'un fichier json, préalablement générés
  * Possibilité de passer un article en mode "Web", permettant aux utilisateurs externes à Dolibarr de le visualiser
    * Token généré au passage d'un article en mode "Web"
    * Lien de l'article public cliquable sur le token de celui-ci
  * Permissions ajoutées pour l'export/import/modeWeb d'un ou de plusieurs articles

* Fixed :
  * Corrections sur les permissions

# [v12.7.1]

* Tags :
  * Les tags listés sur un article sont des liens reliés à leur page descriptive
  * La liste des articles associés à un tag sont accessibles sur la page du tag

* Clone d'un article :
  * Un article fraîchement cloné redirigera directement sur son mode édition
  * Les tags de l'article parent cloné ne seront pas affectés au nouvel article
 
* Sécurités renforcées entre les utilisateurs et les multientités

# [v12.7.0]

* Tags Améliorés :
  * Tags Dolibarr module "Categories" et module "GestionParc" ajoutés à la liste des Tags
  * Possibilité d'activation/désactivation de ces tags dans le listing
  * Ajout d'une description pour les tags Lareponse
  * Les tags sont maintenant supprimables/modifiables
  
* Hotfix
  * Les tags peuvent à nouveau être créés dans le listing des tags, ainsi que dans un article
  * La migration des tags est corrigée, cela permet de supprimer la table des tags et de les intégrer dans le module de base "Categorie"

* Multientité supportée

# [v0.6.1]

* Modification des icones du module :
  * Icones plus voyants
  * Icones par défaut Dolibarr remplacés
  
* Ajout d'un préfix à la duplication d'un article
* Traductions Anglaises complètes
* Le nom du module peut être modifié dans Accueil->Configuration->Traduction Key='Lareponse'

# [v0.6.0]

* Ajout d'un état privé et public pour un article :
  * Modification de l'état à la création et à la modification d'un article
  * Ajout des informations d'état des articles sur le tableau de bord

* Possibilité de mettre un article en favori
  * Ajout disponible sur la page de l'article en question
  * Listing des articles mis en favori sur le tableau de bord

* Modification d'un tag

[ Fix ] Duplication d'un article disponible à nouveau

# [v.0.5.0]

* Articles :
  * Création d'un article
  * Visualisation d'un article avec ses commentaires
  * Listing des articles
  * Suppression des articles sur leur listing
    
* Ajout d'un système de commentaire :
  * Ajout de commentaires sur un article
  * Chargement de plus de commentaires d'un article suivant le scroll

* Ajout d'un système de tag :
  * Création de tags sur la page article
  * Listing des tags
  * Suppression des tags sur leur listing

* Ajout d'un Tableau de bord listant :
  * Les derniers articles
  * Les derniers articles mis à jour
  * Les contributeurs les plus actifs

* Amélioration des menus de gauche

* Ajout de documentations et changelog

---

# [v.0.2.0]

> Version initiale
