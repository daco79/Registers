# Schéma — Actes, comptes, BODACC

## Actes

Source : `informations-entreprise.depots_actes[]`

```json
{
  "date_depot": "YYYY-MM-DD",
  "date_depot_formate": "DD/MM/YYYY",
  "disponible": true,
  "nom_fichier_pdf": "...pdf",
  "token": "...",
  "actes": [
    {
      "type": "Statuts mis à jour",
      "decision": null,
      "date_acte": "YYYY-MM-DD",
      "date_acte_formate": "DD/MM/YYYY"
    }
  ]
}
```

## Comptes déposés

Voir [[Schema - Comptes et finances]].

## Publications BODACC

Source : `informations-entreprise.publications_bodacc[]`

Champs communs :

| Champ | Commentaire |
|---|---|
| `numero_parution` | Numéro parution |
| `date` | Date |
| `numero_annonce` | Numéro annonce |
| `annonce_rectificative` | Booléen |
| `bodacc` | A, B, C |
| `type` | Création, Modification, Vente, Radiation, Dépôt des comptes |
| `rcs` | RCS |
| `greffe` | Greffe |
| `nom_entreprise` | Nom |
| `personne_morale` | Booléen |
| `denomination` | Dénomination |
| `forme_juridique` | Forme |
| `adresse` | Adresse |
| `capital` | Capital |
| `devise_capital` | Devise |
| `administration` | Texte variable |
| `activite` | Activité |
| `description` | Description |
| `commentaires` | Commentaires longs possibles |

## Variabilité

Le BODACC est très hétérogène :

- `Vente` peut avoir `categorie_vente`, `origine_fonds`, `oppositions`, `commentaires`.
- `Dépôt des comptes` a `date_cloture`, `type_depot`, `descriptif`.
- `Modification` a souvent `administration` et `description`.
- `Radiation` peut avoir `date_cessation_activite`.
