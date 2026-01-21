# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [14.0.20] - 10-10-2025
- Compatibilité V22

## [14.0.19] - 08-10-2025
- Correction bug acces a un index d'une variable de type tableau non présente

## [14.0.18] - 27-06-2025
- Correction calcule des totaux avec des factures forcés classées payées
- Correction affichage de la periode sur le PDF

## [14.0.17] - 18-06-2025
- Ajout des avoirs, escomptes, ... dans le detail des paiements de la facture
- Correction de l'affichage des lignes

## [14.0.16] - 12-05-2025
- Correction modèle PDF : hauteur de l'en-tête du tableau en cas de case sur plusieurs lignes

## [14.0.15] - 10-02-2025
- Compatibilité V21

## [14.0.14] - 07-11-2024
- Ajout: Génération document compte client depuis la génération des documents du tiers

## [14.0.13] - 07-11-2024
- Correction affichage PDF avec l'option multidevise pour afficher les montants avec les devises des factures

## [14.0.12] - 07-11-2024
- Correction génération document Dolibarr 14

## [14.0.11] - 22-10-2024
- Correction requête SQL état du compte client PDF

## [14.0.10] - 21-10-2024
- Correction requête SQL état du compte fournisseur

## [14.0.9] - 27-09-2024
- Compatibility V20

## [14.0.8] - 27-08-2024
- Ajout update_main_doc_field dans les modeles de document

## [14.0.7] - 27-08-2024
- Correction des modele odt avec le chemin du fichier generer passer en resultat

## [14.0.6] - 24-05-2024
- Changement de logo et page support.

## [14.0.5] - 23-04-2024
- Utilisation des devises des factures avec l'option multi devise pour l'export PDF

## [14.0.4] - 27-03-2024
- Correctif : utilisation du modele par default account_statut si le modèle sélectionnée est l'option vide (-1) 
- Correctif : proposer les models extrait de compte seulement si ils sont activés dans les paramètres
- CI gitlab

## [14.0.3] - 27-03-2024
- Ajout CI Gitlab

## [14.0.2] - 26-02-2024
- Ajout du lien vers la documentation dans le readme

## [14.0.1] - 23-02-2024
- Changement de numérotation

## [7.0.38] - 20-02-2024
- Correctif : impossibilité de générer des extraits compte client si multi-devises a été activé après la création de factures et paiements liés au client.

## [7.0.37] - 16-02-2024
- Correctif : séparateur CSV (DLB18)

## [7.0.36] - 14-02-2024
- Ajout d'une option pour déplacer l'emplacement du nom de l'entité au début dans le nom de fichier généré si le tiers est partagé entre plusieurs entités

## [7.0.35] - 23-01-2024
- Ajout d'une compatibilité avec le module InfraSpack pour l'affichage lors de la génération des pdfs
- Correction des requêtes SQL dans le cas où la base de données est configuré avec ONLY_FULL_GROUP_BY

## [7.0.34] - 17-01-2024
- Correction des requêtes SQL dans le cas où la base de données est configuré avec ONLY_FULL_GROUP_BY

## [7.0.33] - 20-12-2023
- Ajout du nom de l'entité dans le nom de fichier généré si le tiers est partagé entre plusieurs entités

## [7.0.32] - 08-12-2023
- Correction tenir compte du multi-entités pour la liste des factures retournées

## [7.0.31] - 22-09-2023
- Changement de logo

## [7.0.30] - 27-08-2023
- Clarification of the administration panel
- Fix height of generator popup
- Fix french / english / deutsch languages
- Add italian language

## [7.0.29] - 04-07-2023
- Fix prefix table problem

## [7.0.28] - 20-06-2023
- Remove composer file
- Fix admin option to add detail payment
- Add VERSION file
- Move information OpenDSI to Easya Solutions

## [7.0.27] - 16-06-2023
- Added an option to support multicurrency

## [7.0.26] - 29-03-2023
- Added an option to display the payment details (PDF)

## [7.0.25] - 01-03-2023
- Correction of the wording of the payment methods in the CSV file
- Added the options "Display paid invoice(s)" and "Display payment deadline dates" if the option "Add payment details" is checked

## [7.0.24] - 28-02-2023
- Added an option to display the payment details (CSV)

## [7.0.23] - 21-09-2022
- Added an option to display the invoices of subsidiaries (Thanks Habot-it)

## [7.0.22] - 17-08-2022
- Compatibility PHP8

## [7.0.21] - 04-07-2022
- Add an option to define sort order on document
- Remove old code

## [7.0.20] - 08-06-2022
- Fix display of external references in the supplier account extract
- Fix size of the popup window
- Compatibility Dolibarr v16
- Update language

## [7.0.19] - 30-05-2022
- Add a column for the payment deadline between the label and the total amount
- Add an option to display the external label on top of the label (only for the suppliers)
- Add an option to remove spaces in numbers (only on csv)

## [7.0.18] - 01-02-2022
- Compatibility Dolibarr v15
- Add an option to not display the invoices already paid

## [7.0.17] - 23-07-2021
- Compatibility Dolibarr v14
- Add German translation to module

## [7.0.16] - 06-04-2021
- FIX Correction of the calculation when the constant "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS" is activated
- Addition of a support tab in the administration of the module to identify more quickly most particular case
- FIX on the pdf, overlapping problem with some footer a bit too complete

## [7.0.15] - 26-03-2021
- FIX Problem recording additional invoice field in the module administration

## [7.0.14] - 23-03-2021
- FIX rounding problem when generating the PDF (balance calculation)

## [7.0.13] - 19-03-2021
- FIX calculation problem inherent to the overpayment

## [7.0.12] - 28-01-2021
- Fix SQL error when setup is saved with empty customer invoice extrafield

## [7.0.11] - 23-12-2020
- Add panel admin for the new options of the module
- Added an option to display customer invoice extrafield on generate report
- Fix error when only_full_group_by enable ON mysql > 5.7.5

## [7.0.10] - 18-12-2020
- Adding the option to display product tags when generating the pdf/csv (the option can be checked by default by activating the following constant: EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS)
- The tags produced in the csv are in a new column separated by ";" (or the value defined in the constant: EXTRACTCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR)

## [7.0.9] - 27-10-2020
- Correction of the calculation of down payments paid with the constant "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS" on supplier

## [7.0.8] - 21-10-2020
- Added an option to display abandoned invoices when generating the report (abandoned invoices are not displayed by default - the option can be checked by default by activating the following constant: EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED)
- Some adjustments

## [7.0.7] - 16-10-2020
- Correction of the calculation of down payments paid with the constant "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS"

## [7.0.6] - 19-06-2020
- Fix bad case for a translation key (Thanks to Eldy)
- Compatibility v12

## [7.0.5] - 13-12-2019
- Compatibility with constant FACTURE_DEPOSITS_ARE_JUST_PAYMENTS

## [7.0.4] - 21-11-2019
- Fix a better sorting on invoice type (Remove draft invoice from the list) and status (Remove proforma invoice).
- Add an option to generate account status also for supplier (Distinction if thirdparty is customer or supplier or both)
- Fix when credit invoice is transform as a reduction 

## [7.0.3] - 14-11-2019
- Fix possible carriage return character problem in the end of the filename.

## [7.0.2] - 11-11-2019
- Fix space problem to generate pdf file.
- Fix the width of the columns on the pdf model. (English model)

## [7.0.1] - 04-11-2019
- Compatibility v10.

## [7.0.0] - 20-06-2019
- Version initial.


[Non Distribué]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/compare/14.0.20...HEAD
[14.0.20]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.20
[14.0.19]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.19
[14.0.18]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.18
[14.0.17]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.17
[14.0.16]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.16
[14.0.15]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.15
[14.0.14]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.14
[14.0.13]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.13
[14.0.12]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.12
[14.0.11]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.11
[14.0.10]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.10
[14.0.9]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.9
[14.0.8]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.8
[14.0.7]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.7
[14.0.6]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.6
[14.0.5]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.5
[14.0.4]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.4
[14.0.3]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.3
[14.0.2]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.2
[14.0.1]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/14.0.1
[7.0.38]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.38
[7.0.37]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.37
[7.0.36]: https://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.36
[7.0.35]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.35
[7.0.34]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.34
[7.0.33]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.33
[7.0.32]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.32
[7.0.31]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.31
[7.0.30]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.30
[7.0.29]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.29
[7.0.28]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.28
[7.0.27]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.27
[7.0.26]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.26
[7.0.25]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.25
[7.0.24]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.24
[7.0.23]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.23
[7.0.22]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.22
[7.0.21]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.21
[7.0.20]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.20
[7.0.19]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.19
[7.0.18]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.18
[7.0.17]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.17
[7.0.16]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.16
[7.0.15]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.15
[7.0.14]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.14
[7.0.13]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.13
[7.0.12]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.12
[7.0.11]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.11
[7.0.10]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.10
[7.0.9]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.9
[7.0.8]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.8
[7.0.7]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.7
[7.0.6]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.6
[7.0.5]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.5
[7.0.4]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.4
[7.0.3]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.3
[7.0.2]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.2
[7.0.1]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.1
[7.0.0]: http://git.open-dsi.fr/dolibarr-extension/extraitcompteclient/commits/v7.0.0
