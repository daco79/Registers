# Schéma — Copropriétés

## Source

`recherche-parcelles.coproprietes[]`

## Clé primaire conseillée

`numero_immatriculation`

## Champs principaux

| Champ | Commentaire |
|---|---|
| `nom` | Nom copropriété |
| `numero_immatriculation` | RNIC |
| `numero_immatriculation_principal` | Principal |
| `mandat_en_cours` | Mandat |
| `nombre_total_lots` | Lots totaux |
| `nombre_total_lots_a_usage_habitation_bureaux_commerces` | Lots utiles |
| `nombre_lots_a_usage_habitation` | Habitation |
| `nombre_lots_stationnement` | Stationnement |
| `periode_construction` | Période |
| `type_syndic` | Professionnel / bénévole |
| `syndic_professionnel` | Objet entreprise |
| `syndicat_cooperatif` | Booléen |
| `syndicat_principal_ou_syndicat_secondaire` | Principal/secondaire |
| `representant_legal` | Objet entreprise |
| `date_immatriculation` | RNIC |
| `date_reglement_copropriete` | RCP |
| `residence_service` | Booléen |
| `appartenances` | Flags |
| `rattachements_syndicat` | ASL/AFUL/unions |
| `arretes` | Arrêtés en cours |
| `autres_parcelles[]` | Parcelles rattachées |
| `nombre_parcelles_cadastrales` | Nombre |
| `date_mise_a_jour_rnic` | Mise à jour |
| `date_derniere_maj` | Mise à jour |
| `date_fin_dernier_mandat` | Fin mandat |

## Sous-objet syndic professionnel

Structure proche d’une entreprise compacte :

```json
{
  "siren": "...",
  "siret": "...",
  "date_creation": "...",
  "nom_entreprise": "...",
  "tranche_effectifs": "...",
  "categorie_juridique": "...",
  "activite_principale": "68.32A",
  "cessation_activite": false,
  "siege": {},
  "procedures_collectives": [],
  "finances": [],
  "capital": 85000,
  "devise_capital": "Euros"
}
```

## Liaison

Le SIREN du syndic peut être enrichi par `informations-entreprise`.
