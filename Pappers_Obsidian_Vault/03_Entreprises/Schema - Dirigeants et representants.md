# Schéma — Dirigeants et représentants

## Sources

- `informations-entreprise.representants[]`
- `recherche-dirigeants`
- `cartographie-entreprise`

## Deux types de représentants

### Personne physique

Champs fréquents :

| Champ | Commentaire |
|---|---|
| `qualite` | Fonction principale |
| `qualites[]` | Fonctions multiples |
| `personne_morale` | false |
| `date_prise_de_poste` | Date |
| `nom_complet` | Nom complet |
| `nom` | Nom |
| `prenom` | Prénom |
| `prenom_usuel` | Prénom usuel |
| `sexe` | M/F/null |
| `date_de_naissance_rgpd` | Mois/année |
| `date_de_naissance` | Date complète si disponible |
| `age` | Âge |
| `nationalite` | Nationalité |
| `codes_nationalites[]` | Codes pays |
| `ville_de_naissance` | Naissance |
| `pays_de_naissance` | Naissance |
| `adresse_ligne_1/2/3` | Adresse |
| `code_postal` | Adresse |
| `ville` | Adresse |
| `pays` | Adresse |
| `personne_politiquement_exposee` | Objet ou null |
| `sanctions_en_cours` | Booléen |
| `sanctions[]` | Sanctions |

### Personne morale

Champs fréquents :

| Champ | Commentaire |
|---|---|
| `qualite` | Fonction |
| `personne_morale` | true |
| `date_prise_de_poste` | Date |
| `nom_complet` | Dénomination |
| `denomination` | Dénomination |
| `siren` | SIREN de la personne morale |
| `forme_juridique` | Forme |
| `adresse_*` | Adresse parfois vide |
| `sanctions_en_cours` | Booléen |

## Recherche dédiée : `recherche-dirigeants`

À utiliser pour rechercher une personne ou morale dirigeante à travers les entreprises.

Champs de sortie utiles :

```json
[
  "nom_complet",
  "nom",
  "prenom",
  "personne_morale",
  "denomination",
  "siren",
  "qualites",
  "date_prise_de_poste",
  "entreprises",
  "nb_entreprises_total"
]
```

## Point de conformité

Si un dirigeant a une PPE active ou une sanction en cours, le signaler clairement.
