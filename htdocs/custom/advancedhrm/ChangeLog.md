# CHANGELOG ADVANCEDHRM FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

# UNRELEASED



## Release 1.12
- FIX : ajout d'un message d'erreur pour la liste de sélection des types de congés dans l'admin sur la conf "Bloquer la création de la demande" - **4/02/25** - 1.12.3
- FIX : Correction d'une erreur de calcule si on pose des congés pour un autre utilisateur que l'utilisateur courant- **5/12/24** - 1.12.2
- FIX : verification des jours ouvré (samedi et dimanche uniquement) + jour off - **5/12/24** - 1.12.1
- NEW : Blocage de la création des demandes de congé si le solde de jours disponibles est insuffisant, avec affichage d’un message d’alerte - **19/11/2024** - 1.12.0

## Release 1.11
- FIX : Passage du type d'un buildpath de 2 a 1 - **18/11/2024** - 1.11.1
- NEW : Ajout d'une box comprenant les soldes de congés restants directement sur la fiche utilisateur - **04/11/2024** - 1.11.0

## Release 1.10
- FIX : DA025663 : Fix d'un décalage sur le planning des absences - **06/11/2024** -1.10.1
- NEW : Compat v20 - **21/06/2024** - 1.10.0

## Release 1.9
- FIX : DA024804 Bug global d'affichage des jours féries sur le planning salarié - **22/04/2024** - 1.9.5
          - Correction pour la gestion des dates des jours fériés calculés et non calculés
          - Adaptation du formulaire pour prendre en compte les années saisies dans les datepickers start et end
- FIX : DA024738 - Display half days with different holiday types on same day properly - **03/04/2024** - 1.9.4
- FIX : HotFix Fatal - **04/03/2024** - 1.9.3
- FIX : DA024388 - Gestion des jours fériés calculés - **29/02/2024** - 1.9.2
- FIX : DA024002 All get nom url in the page was converted to holiday - **19/01/2024** - 1.9.1  
- NEW : COMPATV19 - **01/12/2023** - 1.9.0  
  Compatibilité PHP 7.0 min et 8.2 max  

## Release 1.8
- FIX : missing hook function return - **17/11/2023** - 1.8.7
- FIX : CSS compatibility between Dolibarr and Boostrap library - **08/11/2023** - 1.8.6
- FIX : missing public holidays for all countries - **15/05/2023** - 1.8.5
- FIX : inactive user were displayed when filtering on groups - **15/05/2023** - 1.8.4
- FIX : Missing TechATM - **22/02/2023** - 1.8.3
- FIX : Page setup missing test for modules dependencies - **22/02/2023** - 1.8.2
- FIX : update popin et traduction legend - **13/10/2022** - 1.8.1
- NEW : Modification visuelle de la popin map dans l'ajout note de frais des frais kilométriques - **04/10/2022** -1.8.0 
- FIX : select s'affiche en dessous de la popin lors du clone d'une note de frais - **10/01/2023** - 1.7.2
- FIX : prise en compte des utilisateurs non-salariés
- FIX : fix ergonomie
- FIX - **13/09/2022** - 1.7.2
  * Remplaçer le libellé : "Afficher sur la vue planning par défaut le planning de l’utilisateur connecté et de ses subordonnées." : "Par défaut la vue affiche le planning de l’utilisateur connecté et de ses subordonnées."
  * Réaliser une modification dans la configuration du module, séparer par type via onglet les différentes configurations. : Absence, note de frais, maps etc… 
  * Ajout d'une conf : "Utiliser une clé Google api" si ok le champ pour entrer une clé api devient disponible. 
  * Conf griser les week-ends : Remplaçer le switch par un color select
  * "Affichage de la conf suivante : "Distance minimale d'indemnisation repas (en km, disponible uniquement si règlementation prise en compte pour les repas via la conf au-dessus)" uniquement si la conf : Prendre en compte la réglementation pour les notes de frais de type repas est active. 
  * On renomme également la conf : Distance minimale d'indemnisation repas (en km, disponible uniquement si règlementation prise en compte pour les repas via la conf au-dessus) ; Distance minimale d'indemnisation repas en km.  
  * On ajoute une icone d'info contenant le texte de description"
  * "Prendre en compte la case salarié des utilisateurs. 
  * Via conf : ""Prendre en compte uniquement les utilisateurs salariés"" activée par défaut, on viendra lister uniquement les utilisateurs salariés dans les listes RH."
  * "Corriger la faute d'orthographe sur Nombre de présents : Présent avec un "s". 
  * Ajout d'un nombre de résultats au haut du tableau de période à l’identique de celle d'une liste de dolibarr."
  * Manque l'affichage d'une information (adresse complétée, ciblage sur carte) pour s'assurer que la sélection du restaurant choisi est bien le bon
  * "Modification des colonnes : est un repas, est de type invitation etc... Ajouter ces informations dans la section note de frais des configs d'advancedHrm. 
  * Créer un select multiple pour choisir qu'elles sont les types concernés pour chacun."
- FIX : PHP warning 8.0 - **17/08/2022** - 1.7.1 
- NEW : Popin de création de demande d'absence disponible sur le clic d'une case du planning utilisateur - **28/07/2022** - 1.7.0 



## Release 1.6
- FIX : PHP8 warnings - **17/08/2022** - 1.6.3
- FIX : Module icon - **12/07/2022** - 1.6.2
- FIX : Tableau et renommage planning - **07/07/2022** - 1.6.1
- NEW : Affiche maintenant par défaut le planning de l'utilisateur courant et ses subordonnés - **07/07/2022** - 1.6.0

## Release 1.5
- NEW : Permettre sur les notes de frais d'interdire l'ajout de ligne repas si la distance entre le domicile et le restaurant, et la distance entre le travail et le restaurant sont inférieures à une distance paramétrée - **15/06/2022** - 1.5.0
- NEW : Ajout de google map - **06/06/2022** - 1.4.0  
  * Ajoute une interface avec Google map permettant la sélection d'une distance dans le cadre d'une note de frais kilométrique

## Release 1.3
- FIX : Fix some stuff - **06/06/2022** - 1.3.1
  * Ajouter un tooltips sur le survol des absences dans le planning afin d'afficher la description du type d'absence.
  * Lors de l'affichage sur un période d'analyse entre deux mois, le planning ne s'affiche plus.
  * Lors de l'affichage d'une courte période, venir coller le planning contre la colonne des utilisateurs.
  * Venir modifier la mise en forme sur le nom des utilisateurs et des jours afin d'améliorer la lisibilité, agrandir la police, mettre en gras, réaliser des tests afin de voir ce qui convient le  mieux.
  * Affiché par défaut le mois en cours dans la vue planning utilisateur. 
  * En standard les statuts validés pour un congé concerne l'utilisateur, il valide son congé qui passe au statut en approbation. Il faut donc changer le libellé des légendes (ex : "Congé maladie à valider" à "Congé maladie à approuver").
  * Prendre en compte les statuts en "approbation" pour les affichés en couleur hachuré.
  * Prendre en compte les statuts "Approuvée" pour les congés s'affichant en couleur pleine.
  * Cela corrigera une erreur d'affichage dans le planning qui inverse l'affichage congés "validé" et "à valider".
- NEW : Add planning user feature  on holiday left_menu - **20/05/2022** - 1.3.0

## Release 1.2
- NEW : Toggle display select category vehicle depending on expense type - **21/04/2022** - 1.2.0

## Release 1.1
- FIX : We must clear selected guests if we change expense report type - **22/04/2022** - 1.1.4
- FIX : Missing " WHERE can_invite_guests = 1 " in sql to get expense report types with possible guest users - **21/04/2022** 1.1.3
- FIX : Refactorisation code invitation users - **06/04/2022** - 1.1.2
- FIX : Rename trigger - **04/04/2022** - 1.1.1
- NEW : Description - **23/03/2022** - 1.1.0
  * interface frontale gestion des users invités
  * Ajout de la gestion d'un champ can_invite_guests sur les types de note de frais (crud)
  * Ajout sql (2 tables)

## Release 1.0
- NEW : Add customization on expense Type dictionary to link with a vat rate - **16/03/2022** - 1.0.0
- NEW : Add ajax to select vat according to link while selecting expenseType on an expense report - **16/03/2022** - 1.0.0
- Initial version - **14/03/2022** - 1.0.0
