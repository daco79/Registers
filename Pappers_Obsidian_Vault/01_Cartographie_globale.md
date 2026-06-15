# Cartographie globale

```mermaid
flowchart LR
    SIREN[SIREN / Entreprise] --> INFO[informations-entreprise]
    SIREN --> RECH[recherche-entreprises]
    SIREN --> CARTO[cartographie-entreprise]
    SIREN --> COMPTES[comptes-entreprise]
    SIREN --> DIR[recherche-dirigeants]
    SIREN --> BE[recherche-beneficiaires]
    SIREN --> PARCDET[parcelles_detenues]

    INFO --> ETAB[Etablissements / SIRET]
    INFO --> REP[Représentants]
    INFO --> FIN[Finances]
    INFO --> ACTES[Actes / Statuts]
    INFO --> BODACC[BODACC]
    INFO --> PROC[Procédures collectives]
    INFO --> SAN[Sanctions]
    INFO --> MARQUES[Marques / brevets / dessins]

    LIEU[recherche-lieux] --> PARCELLE[recherche-parcelles]
    PARCELLE --> PROP[Propriétaires]
    PARCELLE --> OCC[Occupants]
    PARCELLE --> VENTES[Ventes / mutations]
    PARCELLE --> BAT[Batiments]
    PARCELLE --> DPE[DPE]
    PARCELLE --> COPRO[Copropriétés]
    PARCELLE --> PERMIS[Permis]
    PARCELLE --> URBA[Documents urbanisme]
    PARCELLE --> FDC[Fonds de commerce]

    PROP --> SIREN
    OCC --> SIREN
    COPRO --> SYNDIC[Syndic professionnel / représentant légal]
    SYNDIC --> SIREN
```

## Principe général

Pappers fonctionne comme un graphe de données autour de quelques identifiants centraux :

| Domaine | Identifiant principal | Identifiants secondaires |
|---|---:|---|
| Entreprise | `siren` | `siret`, `nic`, `numero_rcs`, `code_greffe` |
| Établissement | `siret` | `nic`, adresse, `code_commune` |
| Dirigeant personne physique | pas d’ID stable exposé dans les exemples | nom, prénom, date de naissance, qualité |
| Dirigeant personne morale | `siren` | dénomination, forme juridique |
| Parcelle | `numero` / `parcelle_cadastrale` | section, préfixe, numéro plan |
| Bâtiment | `batiment_groupe_id` | parcelle principale |
| DPE | `identifiant_dpe` | `batiment_groupe_id`, parcelle |
| Copropriété | `numero_immatriculation` | nom, parcelles, syndic |
| Vente | `id` quand disponible | date, lots, parcelles associées |
| Document | `token`, `id`, `documentIds` | type, date, fichier |

## Liaison Entreprise ↔ Immobilier

La liaison principale est le `siren` :

- `entreprise.siren`
- `parcelle.proprietaires[].siren`
- `parcelle.occupants[].siren`
- `coproprietes[].syndic_professionnel.siren`
- `coproprietes[].representant_legal.siren`
- `informations-entreprise.parcelles_detenues`
