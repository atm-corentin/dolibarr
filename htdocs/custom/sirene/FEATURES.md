## Features module Sirene

### Extra-fields
Ajout de champs complémentaires
- sur la fiche du tiers
  - Maj Sirene détectée
    - sirene_status : booléen
    - visible uniquement sur la liste (visible partout si version d'Easya est inférieure à 2022.5.3 ou version de Dolibarr inférieure à 18.0.5)
  - Date dernier appel Sirene
    - sirene_update_date : date et heure
    - visible uniquement sur la liste (visible partout si version d'Easya est inférieure à 2022.5.3 ou version de Dolibarr inférieure à 18.0.5)
  - Statut Sirene
    - sirene_company_admin_status : liste de sélection
    F. Fermé
    A. En activité
    - visible partout (liste et fiche en mode visuel / création / modification)
  - Date tâche planifiée Sirene
    - sirene_cron_date : date et heure
    - visible uniquement sur la liste (visible partout si version d'Easya est inférieure à 2022.5.3 ou version de Dolibarr inférieure à 18.0.5)

### Hooks
- Hook formObjectOptions affiche un formulaire sur la page de création / édition de tiers pour rechercher un tiers à partir d'un numéro de SIREN, SIRET, RNA, raison social...
- Hook addMoreActionsButtons ajoute un bouton 'Vérification informations tiers' sur la fiche d'un tiers pour comparer les informations du tiers avec celle de Sirene
    et mettre à jour le tiers. Si le tiers est signalé fermé auprès de l'INSEE, on relance la recherche sur le SIREN pour vérifier qu'il ne s'agit pas simplement d'un déménagement.
- Hook doActions pour valider la recherche d'un tiers depuis le formulaire ou valider la mise à jour d'un tiers avec les informations de la base Sirene
- Hook replaceThirdparty pour verifier la coherence des SIREN et SIRET

### Dictionnaires
#### Liste des codes NAF dans le fichier data/codenaf.csv
#### Correspondance des pays avec l'API Sirene
#### Correspondance des effectifs avec l'API Sirene

### Cron jobs
* Ajoute une tâche planifiée pour détecter les tiers à mettre à jour.  
Elle s'exécute toutes les 10 minutes et est désactivé par défault.  
La tache planifiée à la possibilité d'envoyer un mail contenant la liste des entreprises qui ont fermés.  
Les conditions d'envoie du mail sont :
  * envoi du mail que si au moins une societe a ferme
  * envoi du mail si la constante SIRENE_MAIL_TO_SEND est configurée

### Différentes versions / serveurs de l'API
* système pour prévenir les utilisateurs lors des mises à jour forcées dûes aux nouvelles versions d'API (pour le changement de serveur le 28/02/2025)


- Rajout d'une option dans le module pour etendre le nom ( avec la ville et le NIC) recuperer depuis sirene si le nom existe deja dans dolibarr
