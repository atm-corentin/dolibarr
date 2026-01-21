# CHANGELOG THEME CODE42 FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

# <span style='color:white;background-color:#ed6b00;border-radius:5px;padding: 5px;font-size:small'>Nouveautés</span> [20.2.00] - 2025-12-02

- Fixed :
  - [#409] Rétablissement du scroll dans le sélecteur des médias
  - [#382] Repositionnement automatique suite à l'édition d'une ligne proposition/facture/etc...
  - [#415] Fixed display on webhooks listing
  - [#414] Fixed display on workstations listing

- Changed :
  - [#395] Ajustement de la taille des champs de ligne sur les propositions commerciales + champs de la page /societe/consumption.php
  - [#378] Correction des couleurs de sous-total en mode sombre
  - [#408] Correction de l'affichage des listings sur cas spécifiques
  - [#381] Retour à gauche pour la colonne de sélection des listings en v20
  - [#390] Optimisation / Correction des effets de bord des listings en v20

# [20.1.07] - 2025-11-10

- Changed :
  - [#401] Style des boutons + minor fixes

# [20.1.06] - 2025-10-28

- Fixed :
  - [#403] Les listings des fiches sont ré-affichés en écran réduit

# [20.1.05] - 2025-10-16

- Fixed :
  - [#385] Affichage du bloc utilisateur pour les dimensions tablette
  - [#396] Correction de l'affichage du header des listings

# [20.1.04] - 2025-08-29

- Fixed :
  - [#391] les listings adoptent une bonen architecture html pour etre sticky

# [20.1.03] - 2025-08-13

- Fixed :
  - [#387] Le css pour rendre l'en-tete des listings fixe est plus général

# [20.1.02] - 2025-08-07

- Fixed :
  - [#386] Le selecteur pour rendre l'en-tete des listings fixe est plus précis

# [20.1.01] - 2025-08-06

- Added :
  - [#369] Ajout d'un espace blanc en fin de page pour le bouton 'ScrollTop'

- Fixed :
  - [#372] Les élements d'une card passe derrière la barre d'action des boutons
  - [#370] Les filtres déroulants sur les listings ne passe plus derrière le nom des filtres
  - [#341] Ajustement du scroll pour que la ligne ciblée soit bien visible
  - [#379] Les Post-it sont compatible avec le thème sombre
  - [#380] Les filtres sont de nouveau collés en haut des listings

# [20.1.00] - 2025-07-15

- Fixed : 
  - [#360] En dolibarr v20, la colonne des actions est redeplacer à la fin des listing
  - [#363] Les Filtres et nom des colonnes sont collé en haut des listings
  - [#364] Les boutons multi-entrées natif sont compatible avec le thème
  - [#362] La fonctionnalité Sortable est réparée

# [20.0.03] - 2025-06-20

- Fixed :
  - [#342] La debug bar ne masque plus le bas de page
  - [#344] Affichage des filtres Quicklist en mode mobile sous forme de panneau dépliant.
  - [#345] Agrandissement du champ 'Réf. Client' sur les fiches des documents commerciaux
  - [#348] L'affichage des limites et précisions est réparé
  - [#349] Il est à nouveau possible de se deconnecter en mobile
  - [#352] Le filtre "Tags/catégories" est utilisable en mobile
  - [#365] la liste deroulante "lier à" ne passe plus derrière la barre d'action
  
# [20.0.02] - 2025-05-21

- Fixed :
  - [#347] Une erreur apparaisait lors de l'accès au fonctionnalité du module Export
  
# [20.0.01] - 2025-05-20

- Fixed :
  - [#346] La position du menu est défini sur toutes les entitées

# [20.0.00] - 2025-05-19

- Added :
  - [#330] Le menu peut etre placer horizontalement

- Changed :
  - [#331] La réduction du menu secondaire ne masque plus ses icônes
  
- Fixed :
  - [#333] Compatibilité Dolibarr v20 et PHP8.2
  - [#265] Compatibilité avec le module Post-it
  - [#337] L'affichage des permissions des utilisateurs n'est plus cassé
  - [#340] Le mode sombre est de nouveau disponible
  - [#338] Le bloc blanc dans les logs inaltérables n'apparait plus

# [19.2.11] - 2025-03-26

- Fixed :
  - [#335] Les lignes dans les documents commerciaux ont un affichage correct

# [19.2.10] - 2025-02-27

- Changed :
  - [#329] Amelioration des messages d'erreur lors de la copie du thème

- Fixed :
  - [#327] Le messages d'erreur xdebug sont mieux afficher
  - [#328] Le scroll horizontal infini en v20 sur les listing à été corriger
  - [#325] La liste déroulante "lier à" ne depasse plus de la fiche
  - [#323] Il est possible de copier coller les infos en brouillons dans les lignes de proposition commercial, facture, etc...
  
# [19.2.00] - 2024-11-13

- Added :
  - [#322] Ajout d'un on/off sur le deplacement de ligne avancée

- Fixed :
  - [#296] Utilisation du select2 pour le champs "Rechercher" en mobile
  - [#319] Le scroll horizontal infini sur les listing à été corriger
  - [#320] Il est possible de deplacer les lignes que lorsque l'objet est en brouillon
  - [#321] Les déplacements de lignes s'enregistrent correctement

# [19.1.00] - 2024-09-04

- Added :
  - [#306] Ajout de Raccourci clavier (option/alt + h)

- Changed :
  - [#297] Modification du deplacement des lignes sur les propositions / factures / etc

- Fixed :
  - [#315] Le datePicker passe au dessus du popup de clonage
  - [#316] La page de configuration de "Collecteur d'emails" a été réparée.
  - [#317] La page de configuration "Uptosign" à été réparée.

# [19.0.01] - 2024-07-29

- Added :
  - [#304] Ajout d'une page pour ajouter du CSS à la volée

- Changed :
  - [#309] Modification de l'icone d'impression dans le menu

- Fixed :
  - [#308] Le contenu de l'infobox de l'utilisateur est lisible
  - [#287] La previsualisation des pdf est revenue
  - [#303] les propales, commandes, factures sont réalisable sur mobile
  - [#301] Les onglets de 'card' sont fixer en haut de la page
  - [#311] Le thème est compatible avec le module ExtendedContract
  - [#313] Correction d'un problème d'affichage avec le module listinCSV
  
# [19.0.00] - 2024-06-19

- Added :
  - [#208] Ajout d'une page de configuration des elements visuelle du thème et de dolibarr

- Fixed :
  - [#291] Un problème de script faisait apparaitre un bloc blanc en vue reduite / mobile
  - [#289] Le listing des Affaires d'un tiers est de nouveau visible

# [18.0.06] - 2024-05-30

- Fixed :
  - [#297] Le champ de lots/serie dans la fiche d'ordres de fabrication a été agrandi
  - [#300] Le listing d'équipement gestion de parc à été fixer sur mobile

# [18.0.05] - 2024-04-17

- Fixed :
  - [#292] Les filtres "date" passe devant le nom du filtre
  - [#290] Le thème s'applique seulement si h2g2 est activé

# [18.0.04] - 2024-03-08

- Fixed :
  - [#276] La recherche en vue mobile à été corrigée
  - [#243] L'option css "print" est réparée
  - [#277] Le listing dans les factures lors de la mass action "Envoyer Email" est corrigé
  - [#279] Les scrollbar customiser ne causent plus de crash du dolibarr
  - [#280] Le filtre sur les dates dans contrat plus n'est plus décalé
  - [#282] Fix de l'editeur WYSIWYG en plein écran
  - [#283] Compatibilité entre les multi bouton de H2G2 et le thème dans la barre d'action
  - [#285] Le mode focus est corrigé
  
# [18.0.03] - 2024-01-29

- Fixed :
  - [#272] Le script pour les boutons d'envoi d'email est reparer
  - [#271] La configuration des expediteurs des emails à été réglée
  - [#270] Les boutons sont organisé correctement dans la barre d'action
  
# [18.0.02] - 2024-01-17

- Changed :
  - [#264] La barre de scroll horizontale apparait en bas de l'écran lorsqu'une souris est brancher
  - [#213] La couleur des badges dans les onglets ont la meme couleur que le menu principal

- Fixed :
  - [#261] L'icone pour deplacer les lignes réapparait à nouveaux
  - [#251] L'incident sur les URLs de ScriptInject est résolu
  - [#263] Le listing des informations PHP, OS, etc.. est fixer
  - [#258] La loupe est presente dans les listings sur un Ipad
  - [#266] L'erreur de la copie des droits du dossier thème est résolue
  - [#268] Le Fil d'Ariane apparait constamment

# [18.0.00] - 2023-12-21

- Added :
  - [#197] Nouvelle fonctionnalité pour l'affichage des scrollbars (pour l'activer, cocher la case "Afficher les bordures G-D..." dans le menu Affichage/Thème)
  - [#255] Ajout d'un script pour deplacer les boutons "email" et "annuler" lors de l'envois d'un mail

- Fixed :
  - [#155] Optimisations du menu principal et du breadcrumb
  - [#194] Ajustement visuel de la barre d'action
  - [#199] Compatiblité avec le dark mode
  - [#207] Prise en charge des personnalisation du menu principal (icônes + textes...)
  - [#209] La barre d'action se place le plus possible en bas de l'écran
  - [#238] Le Scroll se repositionne correctement
  - [#233] Le module est compatible dolibarr v18 et php 8
  - [#247] Le Scroll est posible dans les fiches ticket publique
  - [#257] retour des differents mode de l'editeur cke
  - [#245] Le fil d'ariane se lie dans le bon ordre en desktop
  - [#248] Le theme est compatible v18
  - [#259] La Fixation des colonnes s'applique au listing dans les div "tabBar"
  - [#250] Produit rapide est compatible theme v18
  - [#260] Le listing des destinataires dans les mails

# [13.6.01] - 2023-12-12

- Added :
  - [#230] Ajout d'un script pour réduire le menu secondaire
  
- Fixed :
  - [#246] Le script pour reduire le menu secondaire ne s'applique pas en vue mobile
  - [#229] Le bouton impression est affiché à nouveau

# [13.6.00] - 2023-11-24

- Added :
  - [#195] Le thème a dorénavant un module
  - [#217] Ajout d'un onglet Changelog
  - [#211] Ajout de ScriptInject
  - [#214] Ajout de HideTopMenu
  - [#221] La CI à été rajouter

- Changed :
  - [#220] Modification du readme pour integrer celui de ScriptsInject & HideTopMenu

# [13.5.10] - 2023-10-31

- Fixed :
  - [#215] Le datepicker ne passe plus en dessous lors du clonage d'un objet
  - [#219] Le popup pour telecharger directement les documents généré est réafficher correctement
  - [#203] Optimisation d'une cardd au niveau des tableaux et reduction de la marge gauche et droite
  - [#204] Le bouton "Vider" dans la liste des modules à une bordure et le style du thème
  - [#201] Le Listing des colonnes à afficher est revenu
  - [#198] Le bandeau au niveau des tickets ne passe plus par dessus

# [13.5.00] - 2023-10-31

- Added :

  - [#193] Script pour passer une commande avec un pop ajouté

- Fixed :
  - [#201] La liste des colonnes n'est plus masqué sur le listing
  - [#198] Le bordereaux ne passe plus sur le listing
  - [#204] Le bouton "vider" possède un style
  - [#190] Le menu burger est réutilisable en vue paysage
  - [#192] Les boutons sont réalligner
  - [#188] Les colorPicker ne sont plus cassé
  - [#191] PackingList est compatible avec le thème

# [13.4.00] - 2023-07-21

- Added :

  - [#129] Ajout d'une documentation technique

- Changed :

  - [#180] TabsAction est maintenant de la meme couleur que le menu secondaire
  - [#174] TabsAction est maintenant le + en avant possible

- Fixed :
  - [#187] l'écart de tabsAction sur une card est corriger
  - [#172] le bouton enregistrer dans la partie "enregistrer une reception" à maintenant la bonne couleur
  - [#186] les inputs date ne sont plus cassé dans le listing des fournisseurs
  - [#175] la plupart des zindex defaillant sont corriger
  - [#183] le thème est comptabile quicklist
  - [#182] l'icone "fav" d'un article est de nouveau jaune
  - [#179] la fiche de pointage n'est plus cassée en vue mobile
  - [#177] les icones dans les boutons sont bien placée
  - [#176] le thème est compatible avec recherche avancée
  - [#170] le Fil d'ariane refonctionne sur mobile
  - [#139] Le Repositionnement est de nouveau actif
  - [#169] Le Bloc User du Top menu est de nouveau accesible en mobile
  - [#162] Les items du kanban ne passe plus devant le menu (mobile)
  - [#130] Me menu ne gêne plus l'onglet "mouvement de stock"
  - [#166] Mise en avant du bouton éditer
  - [#161] Le menu active est plus lisible au format mobile
  - [#150] Bouton de couleur vert lors de la validation de l'intervention
  - [#149] Icônes non centré présent sur le menu déroulant
  - [#147] Reduction de la taille des filtres par tags en format téléphone
  - [#145] Historique des interventions responsive

# [13.1.02] - 2023-03-28

- Added :

  - [#138] Ajout d'une vignette pour le thème

- Fixed :
  - [#168] La fancybox dans une facture n'apparait plus
  - [#165] La fenetre d'envoi d'email est corrigé
  - [#159] Les boutons sont de nouveau centré
  - [#158] Les boites ne sont plus tronqué
  - [#160] Les listes sont scrollable sur téléphone
  - [#167] Le selecteur de modèle d'email réapparait correctement
  - [#156] Le bouton 'passer les commandes' est bien placer
  - [#135] la barre de navigation n'est plus raccourcie
  - [#140] Fix des boutons et de MyBooking dans la tabs action
  - [#117] Les drapeaux ont diminuer de taille
  - [#120] Fix des boutons pour dol16
  - [#125] Les boutons ont retrouvé une taille normale + ils se mettent à la ligne lorsqu'ils n'ont plus de place
  - [#133] Le zoom est coordonné avec le menu
  - [#131] Les icones des modules sont de nouveau visible
  - [#108] Les items dans la navbar des receptions sont espacés
  - [#164] La barre des pièces est maintenant scrollable
  - [#157] Le Changelog à été mis à jour
  - [#152] Le menu secondaire est accesible
  - [#141] Le menu "plus" est accesible
  - [#123] Les icones dans les boutons sont centrer
  - [#115] Le Décalage au niveau des filtres des dates est maintenant résolu

# [13.1.01] - 2023-02-15

- Fixed :

  - [#107] Bordures et Arrondis moins prononcé
  - [#106] Correction des zones de recherches dans le listing d'intervention
  - [#101] Onglet Configuration
  - [#100] Onglet du Topmenu réapparu
  - [#99] Interface onglet Contact corrigé
  - [#98] Icones du menu Cotrolia centré
  - [#97] Zone de texte "WYSIWYG" sans bordure
  - [#93] Zoom Général dimunier à 85%

- Changed :
  - [#109] Modification du comportement du thème sur l'historique d'une intervention
  - [#94] Modification des couleurs de boutons
  - [#92] Modification des réceptions
  - [#91] Modification du tableau de bord

# [13.1.00] - 2023-02-14

- Added :

  - [#89] Ajout de bordure sur le fil d'Ariane

- Changed :
  - [#95] Application d'une bordure aux boutons
  - [#88] Modification de l'affichage des interventions
  - [#87] Modification du listing d'interventions
  - [#86] Modification du comportement du menu
  - [#90] Préparation des Fichiers
