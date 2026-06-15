# Schéma — Permis, fonds de commerce, urbanisme

## Permis

Champs possibles :

| Champ | Commentaire |
|---|---|
| `permis_numero` | Numéro |
| `permis_type` | Type |
| `permis_etat` | État |
| `permis_date_autorisation` | Date |
| `permis_denomination_demandeur` | Demandeur |
| `permis_adresse` | Adresse |
| `permis_superficie_terrain` | Terrain |
| `permis_zone_operatoire` | Champ supplémentaire |
| `permis_complet` | Champ supplémentaire |

## Fonds de commerce

Champs possibles :

| Champ | Commentaire |
|---|---|
| `fonds_de_commerce_activite` | Activité |
| `fonds_de_commerce_prix` | Prix |
| `fonds_de_commerce_devise` | Devise |
| `fonds_de_commerce_categorie_vente` | Catégorie vente |
| `fonds_de_commerce_date_debut_activite` | Date |
| `fonds_de_commerce_annonce_bodacc` | Annonce |
| `fonds_de_commerce_origine_fonds` | Origine |
| `fonds_de_commerce_acheteur` | Acheteur |
| `fonds_de_commerce_precedent_proprietaire` | Précédent propriétaire |
| `fonds_de_commerce_precedent_exploitant` | Précédent exploitant |
| `fonds_de_commerce_fiabilite_appartenance_parcelle` | Fiabilité |

## Documents d’urbanisme

Source : `documents_urbanisme[]`

| Champ | Commentaire |
|---|---|
| `id` | ID document |
| `titre` | Titre |
| `nom` | Nom technique |
| `statut` | Statut |
| `type` | PLU, PLUi, SCoT, SUP |
| `statut_legal` | APPROVED, etc. |
| `zones[]` | Zones applicables |

## Zones

```json
{
  "libelle": "UG",
  "libelle_long": "Zone urbaine générale",
  "type_psc": null,
  "stype_psc": null,
  "type_zone": null,
  "date_approbation": null,
  "date_validation": null,
  "nom_fichier": "75056_reglement_20241120.pdf"
}
```

## Usage opérationnel

- Identifier les contraintes PLU/SUP.
- Croiser avec ventes, DPE, copropriété.
- Détecter les opportunités : permis, fonds, zones urbaines, parcelles avec surface disponible.
