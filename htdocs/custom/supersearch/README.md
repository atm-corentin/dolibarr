# Module SUPER SEARCH
Le but de ce TP est de travailler en groupe dans l’idée de créer un module DOLIBARR qui permettra de parcourir le contenu d’un outil d’indexation.

### Les participants à ce TP sont :

- Baya pour la partie système d’indexation et interface
- Thomas pour la partie module DOLIBARR et en support 
- Mathieu pour la partie système. 
- Kevin en support pour la partie UX.

### Nous utiliserons les outils suivants :

- MEILISEARCH pour l’outil d’indexation
- Les librairies JS INSTANTSERACH & Autoccomplete pour l’interrogation.

### Ce module aura 2 fonctions :

1 . d’alimenter l’outil d’indexation à partir de données DOLIBARR,
2 . interroger le contenu de l’outil d’indexation.
Concernant la partie alimentation en données, ce module utilisera les HOOKS DOLIBARR.

Du côté DOLIBARR, les hooks suivants : création, modification & suppression devront être utilisés.

#### Dans une première phase, nous testerons l’indexation sur les objets :

- PRODUITS / SERVICE (objet produit). 
- Tiers (objet Teirs & contacts).

#### Dans un 2 ième temps, nous questionnerons les modules Code 42 :
- LA REPONSE (objet article).
- GESTION DE PARC (objet equipement, applications).

#### Dans un 3ieme temps, nous ajouterons les objets :

- Interventions,
- Proposition clients.

#### Dans un 4ième temps, nous ajouterons les objets :

- commande clients,
- facture clients.
- commande fournisseurs,
- facture fournisseurs.

- L’accès à l’outil d’indexation devra être sécurisé.

Le module permettra de définir l’adresse API & le token de l’outil d’indexation.

Du côté application, je propose de déployer l’outil dans l’environnement DOCKER maitrisé par Mathieu.
