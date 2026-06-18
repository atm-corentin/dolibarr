# clichaumeil — Documentation

> Fichier source de l'onglet de documentation intégré.
> Le titre h1 et tout contenu avant le premier h2 sont ignorés.
> h2 (`##`) = section principale, h3 (`###`) = sous-section.
> Inline supporté : **gras**, *italique*, `code`. Les citations (`>`) deviennent
> des encarts ("Attention"/"Warning" = rouge, "Note"/"Info" = bleu).

## Présentation

Module custom Chaumeil regroupant plusieurs fonctionnalités métier :

- **Commissions** — barème de coefficients par rôle et catégorie de client,
  gestion des catégories/groupes commerciaux.
- **Sous-traitance** — sélection de sous-traitant depuis les propositions
  fournisseur, propagation du prix d'achat, taux de marge minimum (Discountrules).
- **RFA** — suivi des RFA fournisseur et client, synthèses annuelles.
- **Synchronisation prix fournisseur** — moteur de synchronisation des grilles
  tarifaires (connecteur ANTALIS via SOAP), cron quotidien et rapport.

## Configuration

Les réglages sont répartis en onglets dans la page d'administration du module :
Général, Produits, Contrats, Sous-traitance, Commissions, Connexions API.

### Commissions

Les coefficients de commission sont gérés via un **dictionnaire éditable**
(`Accueil > Configuration > Dictionnaires`), indexé par `(rôle, catégorie client)`.

> Attention : le dictionnaire est seedé une seule fois à l'activation du module.
> Une réactivation ne réinsère rien si des lignes existent déjà pour l'entité —
> les `code` et `customer_tag` que vous personnalisez sont donc préservés.

### Connexions API

L'onglet Connexions API configure les connecteurs de synchronisation de prix
(ex. ANTALIS) : identifiants, mot de passe chiffré, mode dry-run, destinataires
du rapport et planification du cron.

## Utilisation

Reportez-vous aux onglets de configuration et aux boutons d'action ajoutés sur
les fiches tiers, propositions et commandes selon la fonctionnalité concernée.

## FAQ

> Note : pour toute question fonctionnelle, contactez ATM Consulting.
