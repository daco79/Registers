# Exemple JSON observé — `recherche-parcelles`

## Appel réalisé

```json
{
  "adresse": "10 Rue Ordener",
  "nom_commune": "Paris",
  "code_postal": "75018",
  "par_page": 1,
  "page": 1,
  "return_fields": [
    "numero",
    "adresse",
    "code_commune",
    "commune",
    "code_departement",
    "contenance",
    "proprietaires_siren",
    "proprietaires_nom_entreprise",
    "proprietaires_personnes_physiques",
    "occupants_siren",
    "occupants_nom_entreprise",
    "ventes",
    "batiments",
    "dpe",
    "coproprietes",
    "permis",
    "fonds_de_commerce",
    "documents_urbanisme",
    "statistiques"
  ],
  "statistiques": "prix_de_vente_au_m2"
}
```

## Forme synthétique observée

```json
{
  "resultats": [
    {
      "numero": "75118000CL0016",
      "adresse": "10 RUE ORDENER 75018 PARIS 18",
      "code_commune": "75118",
      "commune": "PARIS 18E ARRONDISSEMENT",
      "code_departement": "75",
      "contenance": 1573,
      "proprietaires": [
        {
          "siren": "353776404",
          "nom_entreprise": "CABINET GELIS"
        }
      ],
      "occupants": [
        {
          "siren": "039252606",
          "nom_entreprise": "SYND.COPR. 10 RUE ORDENER 75018 PARIS RE"
        }
      ],
      "ventes": [
        {
          "id": "2025-1277132",
          "date": "2025-03-20",
          "nature": "vente",
          "valeur_fonciere": 924000,
          "type_local": "appartement",
          "surface_reelle_bati": 120,
          "nombre_pieces": 4,
          "lots": [
            {
              "numero": "1038",
              "surface_carrez": 116.24
            }
          ]
        }
      ],
      "batiments": [
        {
          "batiment_groupe_id": "bdnb-bg-D4D8-J22G-HFED",
          "surface": 366,
          "usages": ["Résidentiel collectif"],
          "annee_construction": 1978,
          "nombre_logements": 39
        }
      ],
      "dpe": [
        {
          "identifiant_dpe": "2675E1342049J",
          "source": "ademe",
          "arrete_2021": true,
          "classe_bilan_dpe": "E",
          "classe_emission_ges": "E",
          "date_reception_dpe": "2026-05-19"
        }
      ],
      "coproprietes": [
        {
          "nom": "ORDENER 10",
          "numero_immatriculation": "AB0368480",
          "nombre_total_lots": 122,
          "type_syndic": "professionnel",
          "syndic_professionnel": {
            "siren": "353776404",
            "nom_entreprise": "CABINET GELIS"
          }
        }
      ],
      "permis": [],
      "fonds_de_commerce": [],
      "documents_urbanisme": [
        {
          "type": "PLU",
          "titre": "Plan Local d'Urbanisme (PLU) de la commune de PARIS",
          "zones": [
            {
              "libelle": "UG",
              "libelle_long": "Zone urbaine générale"
            }
          ]
        }
      ]
    }
  ],
  "total": 2
}
```

## Enseignements

- Une parcelle peut contenir beaucoup plus que le cadastre : ventes, DPE, copropriété, urbanisme, occupants.
- Les champs propriétaires/occupants demandés sous forme aplatie reviennent en objets `proprietaires[]` et `occupants[]`.
- Les DPE ont des structures très variables selon source et génération réglementaire.
- Les ventes doivent être dédoublonnées.
