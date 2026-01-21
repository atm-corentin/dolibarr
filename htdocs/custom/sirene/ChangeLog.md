# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non distribué]

## [10.3.7] - 21-11-2025
- Correction de warnings (compatibilité PHP8.4) Thanks @Inovea

## [10.3.6] - 10-10-2025
- Compatibilité v22

## [10.3.5] - 06-10-2025
- Correction des chemins d'inclusion du main

## [10.3.4] - 26-09-2025
- Correction : erreur Javascript sur la page de création d'un tiers (merci @FHenry)

## [10.3.3] - 17-06-2025
- Correction du test lors de la fusion de 2 tiers pour vérifier qu'il n'y ai pas d'incoherence de SIREN et SIRET

## [10.3.2] - 17-06-2025
- Rajout d'un test lors de la fusion de 2 tiers pour vérifier qu'il n'y ai pas d'incoherence de SIREN et SIRET

## [10.3.1] - 14-06-2025
- Rajout d'une option dans le module pour etendre le nom ( avec la ville et le NIC) recuperer depuis sirene si le nom existe deja dans dolibarr

## [10.3.0] - 18-03-2025
- Refonte du module
- Affichage automatiques des critères de recherche supplémentaires si la ville ou le code postal est renseignées
- Contrôle et alerte si le nom du tiers renseigné est déjà pris sur la fiche d'un tiers (affichage, creation et edition) avec les liens vers ceux-là

## [10.2.8] - 16-06-2025
- Correction pour la remise en place des champs obligatoires par rapport à Dolibarr v18 (DLB #34406 - TS2506-11996)

## [10.2.7] - 25-03-2025
- Aggregation de la mise a jour de l'adresse (via CRON) dans le parametrage et l'execution
- Modification de la prise en compte de coordonnée via la misea jour d'un tiers dans prospectingmap
- Correction modification de la geoloc si l'adresse a été cocher dans la vrification sirene depuis la fiche
- Correction mise en forme code
- Suppression d'un warning PHP

## [10.2.6] - 13-02-2025
- Correction remplissage formulaire "Vérification Sirene"
- Compatibilité postgreSQL sur le dictionnaire 'sirene'

## [10.2.5] - 13-02-2025
- Compatibilité V21

## [10.2.4] - 12-02-2025
- Correction compatibility Dolibarr 10

## [10.2.3] - 12-02-2025
- Allow Dolibarr 10

## [10.2.2] - 13-01-2025
- Préparation de la migration du endpoint API Sirene le 28/02/2025
- Abandon des différentes versions d'API
- Lors d'une vérification d'un tiers, si le tiers est fermé, relancer la recherche sur le SIREN pour vérifier qu'il ne s'agit pas simplement d'un déménagement.
- Correctif : lors d'une modification du tiers, si le code client ne correspond pas au masque mais qu'il ne change pas, la mise à jour se fait correctement.
- Modification de la version min de PHP


## [10.2.1] - 08-01-2025
- Correction du forçage de la visibilité des champs complémentaires sur la fiche des tiers

## [10.2.0] - 03-01-2025
- Préparation de la migration vers le nouveau serveur d'API SIRENE

## [10.1.3] - 03-01-2025
- correctif de la mise à jour automatique de l'efectif et de la forme juridique.

## [10.1.2] - 19-12-2024
- Ajout de la possibilité de parametrer sur la tache cron les champs a mettre a jours

## [10.1.1] - 26-11-2024
- Fix la modification du parametrage des extrafields
- Ajout du fait d'actualiser le status de l'entreprise quand on actualise avec sirene pour le remettre en activité
- Modification: remplissage du formulaire de recherche sirene avec uniquement le numéro de siren pré-rempli
- Fix SIRENE_API_URL

## [10.1.0] - 26-11-2024
- Fix d'un problème dans la tache cron qui causait l'envoi en boucle des memes tiers par mails a cause d'un update de société qui ne fonctionnais pas (code client non conforme)
- Fix de certaines chaines de traductions
- Mise a jour du FEATURES.md au sujet de la tache cron pour ajouter des précisions sur son fonctionnement
- Modification du parametrage des extrafields pour changer leur visibilité

## [10.0.81] - 18-11-2024
- Renommage du dossier build en data

## [10.0.80] - 30-10-2024
- Compatibilité V20

## [10.0.79] - 21-08-2024
- Correction affichage lien pour vérifier le numéro de SIREN

## [10.0.78] - 01-07-2024
- Correction: affiche 'Valeur non définie' au lien de '[ND]' quand une propriété est non définie dans les résultats de l'API Sirene + features.md

## [10.0.77] - 07-06-2024
- Ajout d'un bouton pour rechercher un / des tiers si le tiers est fermé

## [10.0.76] - 07-06-2024
- Ajout version php.

## [10.0.75] - 30-05-2024
- Changement pour CI.

## [10.0.74] - 24-05-2024
- Changement de logo.

## [10.0.73] - 15-05-2024
- Ajout du protocole HTTP pour le proxy si un proxy est utilisé dans Dolibarr

## [10.0.72] - 19-04-2024
- Si un proxy est configuré dans Dolibarr, il est utilisé pour effectuer les requêtes vers l'API Sirene

## [10.0.71] - 29-03-2024
- Correction: mauvais departement affiché dans le select quand on recherche une adresse

## [10.0.7] - 28-03-2024
- Rajout de l'adresse dans la liste des champs de filtre lors de la recherche des informations tiers

## [10.0.64] - 28-03-2024
- Changement de version minimale de Dolibarr : Dolibarr 10

## [14.0.64] - 20-03-2024
- Activation du paramétrage de l'API 3.11

## [14.0.63] - 26-02-2024
- Ajout du lien vers la documentation dans le readme

## [14.0.62] - 26-02-2024
- Changement de numérotation

## [7.1.0] - 19-02-2024
- Nouvelle API SIRENE
- Recherche par numéro RNA
- Intégration de l'API AdresseFrance

## [7.0.63] - 16-02-2024
- Correction : Si des champs étaient paramétrés obligatoires, la rechere SIREN ne fonctionnait pas.

## [7.0.62] - 08-12-2023
- Correction : inversion de la recherche (API) des établissements "fermés" vers "actifs".

## [7.0.61] - 05-12-2023
- Correction d'une erreur sur les recherches d'établissements ouverts

## [7.0.60] - 25-09-2023
- Changement de logo.

## [7.0.59] - 31-08-2023
- Correction erreur 500 with PHP 8.0/8.1/8.2

## [7.0.58] - 10-08-2023
- Ajout d'un champ complémentaire pour la date de vérification du tiers depuis la tâche planifiée
- Mise à jour de la date d'appel à Sirene à la création d'un tiers et lors de la confirmation de la vérification manuelle
- Le staut de détection d'une mise à jour Sirene est remise à zéro à chaque confirmation de vérification de tiers

## [7.0.57] - 07-08-2023
- Ajout d'exemple (basé sur le Dolibarr installé) lors de la recherche (UX - Placeholder)
- Ajout libellé cliquable pour accéder aux champs de recherche (UX - Label for)
- Ajout d'une option pour rechercher uniquement les sièges sociaux (option par défaut disponible dans l'admin du module)
- Ajout aide à la saisie sur le code Naf qui ne correspond pas à celle demandée dans Dolibarr (Ajout d'un point automatiquement si non-présence de ce caractère) 
- Correction affichage responsive - Le formulaire de recherche Sirene lors de la création d'un tiers s'adapte à l'affichage smartphone 
- Correction chargement Guzzle - Conflit en cas de présence d'un autoloader (Merci Frédéric France - NetLogic)
- Ajustement libellé
- Correction CSS

## [7.0.56] - 12-07-2023
- Divers correctifs sur la tâche planifiée
- Limitation à 20 requêtes par minutes (pas plus) sur la tâche planifiée
- Renommage de "Etat maj Sirene" par "Maj Sirene détectée"
- Ajout de champ complémentaire pour avoir le statut administratif du tiers : "Fermé" ou "En activité"
- Ajout de l'icône "étoile verte" lors de la recherche d'établissement pour préciser le siège social

## [7.0.55] - 05-07-2023
- Compatibility with PgSQL on SQL file
- Move information OpenDsi to Easya Solutions

## [7.0.54] - 12-05-2023
- Fix remove duplicates entry from pre-existing table in case a fields has duplicate when creating a unique index

## [7.0.53] - 10-05-2023
- Not update third-party country if no field was selected + Fix SQL request added from v7.0.52

## [7.0.52] - 09-05-2023
- Remove duplicates entry from pre-existing table in case a fields has duplicate when creating a unique index

## [7.0.51] - 24-03-2023
- Forcer la mise à jour des ids du dictionnaire d'association des pays Sirene avec ceux de Dolibarr

## [7.0.50] - 23/03/2023
- Mise à jour des identifiants du dictionnaire sirene country pour les faire correspondre avec ceux du dictionnaire country de Dolibarr

## [7.0.49] - 08/03/2023
- Mise à jour du lien vers la documentation

## [7.0.48] - 02-03-2023
- Mise à jour de GuzzleHTTP

## [7.0.47] - 22-02-2023
- Correction compatibilité v16 (token dans les appels AJAX)

## [7.0.46] - 29-09-2022
- Ajout de messages d'erreur parlants pour les erreurs 401 (mauvaise clef d'API) et 500 (erreur serveur)

## [7.0.45] - 16-08-2022
- Correction de la récupération de l'effectif et de la forme juridique quand le SIRET n'est pas déjà renseigné

## [7.0.44] - 25-06-2022
- Compatibilité Dolibarr v16
- Compatibilité PHP8

## [7.0.43] - 23-06-2022
- Meilleure récupération du nom de l'enseigne (nom alternatif ou alias) au niveau de l'établissement
- Compatibilité avec Doliwamp sur Windows (Problème cUrl Warning GuzzleHttp)

## [7.0.42] - 30-05-2022
- Conversion de la gestion du dictionnaire codenaf avec AdvanceDictionaries

## [7.0.41] - 18-03-2022
- Rajout du département dans la liste des champs pouvant être mise à jour lors de la verification des informations tiers sur la fiche d'un tiers

## [7.0.40] - 18-03-2022
- Correction de l'auto selection des informations du tiers lorsqu'il ne trouve qu'un résultat et que l'établissement est fermé lors de la verification des informations tiers sur la fiche d'un tiers
- Correction compatibilité dolibarr v13+

## [7.0.39] - 23-02-2022
- Correction de la verification des informations tiers sur la fiche d'un tiers
- Correction de la tâche planifiée de mise a jour du status des tiers

## [7.0.38] - 20-01-2022
- Correction affichage des erreurs

## [7.0.37] - 14-12-2021
- Retrait du jeton de limitation des requêtes. Trop problématique

## [7.0.36] - 10-12-2021
- Correction de la reprise des données sur les Salariés et la Forme juridique

## [7.0.35] - 08-12-2021
- Correction de l'affichage de l'erreur lors de la recherche des informations du tiers

## [7.0.34] - 03-12-2021
- Correction de la récupération du nom de l'entreprise morale / physique

## [7.0.33] - 05-10-2021
- Correction de la récupération du code d'activité de l'entreprise et non de l'unité légale

## [7.0.32] - 20-09-2021
- Suppression du patch introduit dans la version 7.0.28 de Sirene maintenant que ce patch a été introduis dans le core de Dolibarr (v12 et +)
- Ajout de la récupération des données concernant les effectifs et le type d'entité légale

## [7.0.31] - 23-07-2021
- Données Dénomination sociale récupérées au niveau de l'établissement secondaire plutôt qu'au niveau de l'établissement principal

## [7.0.30] - 20-07-2021
- Compatibilité v14.0.x
- Correction du lien vers le site de vérification RNCS suite à une évolution du site du gouvernement (Ajout d'un "entreprise/" dans l'URL)
- Intégration d'un patch core pour les versions 13.0.0 à 13.0.3.
  Problème de sélecteur lors d'une recherche Sirene avec plusieurs résultats, uniquement le 1er résultat est repris même si on sélectionne un autre résultat.
  Patch : https://github.com/Dolibarr/dolibarr/pull/17701

  Problème réglé si vous êtes en version 13.0.4. Les autres branches de Dolibarr ne sont pas concernées par ce problème

## [7.0.29] - 09-07-2021
- Correction récupération code pays lors de la mise à jour d'une fiche
- Désactivation par défaut de la tâche planifiée lors de l'activation du module

## [7.0.28] - 05-07-2021
- Correction affichage z-index pour la fenêtre de vérification des tiers

## [7.0.27] - 30-06-2021
- Données APE récupérées au niveau de l'établissement secondaire plutôt qu'au niveau de l'établissement principal

## [7.0.26] - 11-06-2021
- Correction des droits d'accès pour la mise à jour des données d'un tiers provenant de l'API Sirene

## [7.0.25] - 08-06-2021
- Correction erreur SQL pour l'accès au dictionnaire des codes Naf

## [7.0.24] - 01-06-2021
- Affichage et correction du dictionnaire dans la configuration du module

## [7.0.23] - 21-05-2021
- Problème d'activation du jeton unique en multientité
- Ne pas afficher par défaut les champs supplémentaires Date Sirene & Etat MAJ Sirene lors de la création d'un tiers
- Intégration d'un patch core pour les versions 13.0.0 à 13.0.2.
  Problème de sélecteur lors d'une recherche Sirene avec plusieurs résultats, uniquement le 1er résultat est repris même si on sélectionne un autre résultat.
  Patch : https://github.com/Dolibarr/dolibarr/pull/17701

## [7.0.22] - 22-04-2021
- Correction balises de traduction

## [7.0.21] - 10-04-2021
- Supprimer les espaces lors de lors de la recherche d'un numéro Siret ou Siren
- Activation par défaut du jeton unique lors de l'activation du module
- Ajout d'une constante concernant la version du module Sirene
- Correctif sur la tâche planifiée
- Correctif sur la gestion du token
- Traduction

## [7.0.20] - 04-03-2021
- Ajout d'un message d'alerte sur un tiers fermé pour préciser de retirer le siret pour relancer une mise à jour des informations du tiers
- Correction du problème de calcul du numéro de TVA intracommunautaire lors d'une mise à jour d'un tiers

## [7.0.19] - 24-02-2021
- Ajout d'un contrôle planifié de vérification des données en lien avec l'API Sirene
- Correction problème sur les établissements fermés

## [7.0.18] - 23-07-2020
- Ajout d'une option dans le panneau de configuration pour choisir le lien de vérification du numéro siret (introduit dans Sirene 7.0.15)

## [7.0.17] - 06-07-2020
- Correction problème calcul numéro TVA intracommunautaire dans de rare cas (FRXX < 10)

## [7.0.16] - 01-07-2020
- Remplissage automatique du code du département

## [7.0.15] - 29-06-2020
- Modification de l'url de verification du numéro de SIRET

## [7.0.14] - 22-06-2020
- Ne pas tenir compte du Code NAF pour les pays hors France

## [7.0.13] - 16-04-2020
- Modification affichage bloc recherche répertoire SIRENE lors de la création du tiers pour les petits écrans.
- Compatibilité v12

## [7.0.12] - 12-02-2020
- Clarifie la recherche du code naf dans le service Sirene.
- Ajout d'une option pour n'afficher que les tiers qui n'ont jamais fermés

## [7.0.11] - 13-01-2020
- Fix "include vendor/autoload.php" avec d'autres modules.

## [7.0.10] - 26-11-2019
- Correction récupération des identifiants des dictionnaires (compatibilité PHP v7.2)

## [7.0.9] - 06-11-2019
- Correction du non-chargement de la fiche tiers lors de la présence d'un code NAF non présent dans le dictionnaire des codes NAF
- Correction de l'accès au dictionnaire des codes NAF

## [7.0.8] - 21-10-2019
- Ajout du calcul du numéro de TVA intracommunautaire (Basé sur le numéro Siren)

## [7.0.7] - 07-10-2019
- Correction du chemin d'un include pour une fonction nécessitant la librairie codenaf. (Compatibilité v10)

## [7.0.6] - 30-09-2019
- Correction de la pérennité des valeurs du formulaire de création du tiers à l'affichage du choix du tiers retourné par Sirene.

## [7.0.5] - 05-09-2019
- Fusion avec le module Code Naf.
- Corrections mineures.

## [7.0.4] - 02-09-2019
- Correction de la gestion des erreurs lors de la requête à la base Sirene.

## [7.0.3] - 01-07-2019
- Correction de l'ajout des paramètres du formulaires lors de l'affichage de la boite de confirmation(affichage des resultats)
- Simplification du message d'erreur (avec option pour afficher l'erreur complète)

## [7.0.2] - 27-06-2019
- N'écrase les paramètres déjà renseignés

## [7.0.1] - 26-06-2019
- Sélectionne automatiquement la première société active trouvée

## [7.0.0] - 12-06-2019
- Version initiale.



[Non Distribué]: http://git.open-dsi.fr/dolibarr-extension/sirene/compare/10.3.7...HEAD
[10.3.7]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.7
[10.3.6]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.6
[10.3.5]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.5
[10.3.4]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.4
[10.3.3]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.3
[10.3.2]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.2
[10.3.1]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.1
[10.3.0]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.3.0
[10.2.8]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.8
[10.2.7]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.7
[10.2.6]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.6
[10.2.5]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.5
[10.2.4]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.4
[10.2.3]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.3
[10.2.2]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.2
[10.2.1]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.1
[10.2.0]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.2.0
[10.1.3]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.1.3
[10.1.2]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.1.2
[10.1.1]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.1.1
[10.1.0]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.1.0
[10.0.81]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.81
[10.0.80]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.80
[10.0.79]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.79
[10.0.78]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.78
[10.0.77]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.77
[10.0.76]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.76
[10.0.75]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.75
[10.0.74]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.74
[10.0.73]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.73
[10.0.72]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/10.0.72
[10.0.71]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v10.0.71
[10.0.7]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v10.0.7
[10.0.64]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v10.0.64
[14.0.64]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v14.0.64
[14.0.63]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v14.0.63
[14.0.62]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v14.0.62
[7.1.0]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.1.0
[7.0.63]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.63
[7.0.62]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.62
[7.0.61]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.61
[7.0.60]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.60
[7.0.59]: https://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.59
[7.0.58]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.58
[7.0.57]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.57
[7.0.56]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.56
[7.0.55]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.55
[7.0.54]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.54
[7.0.53]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.53
[7.0.52]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.52
[7.0.51]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.51
[7.0.50]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.50
[7.0.49]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.49
[7.0.48]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.48
[7.0.47]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.47
[7.0.44]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.44
[7.0.43]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.43
[7.0.42]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.42
[7.0.41]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.41
[7.0.40]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.40
[7.0.39]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.39
[7.0.38]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.38
[7.0.37]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.37
[7.0.36]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.36
[7.0.35]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.35
[7.0.34]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.34
[7.0.33]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.33
[7.0.32]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.32
[7.0.31]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.31
[7.0.30]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.30
[7.0.28]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.28
[7.0.27]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.27
[7.0.26]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.26
[7.0.25]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.25
[7.0.24]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.24
[7.0.23]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.23
[7.0.22]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.22
[7.0.21]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.21
[7.0.20]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.20
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.13
[7.0.12]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.12
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.6
[7.0.5]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.5
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/sirene/commits/v7.0.0
