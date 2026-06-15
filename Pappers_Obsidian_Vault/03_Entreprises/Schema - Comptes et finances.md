# Schéma — Comptes et finances

## Sources

- `informations-entreprise.finances[]`
- `informations-entreprise.comptes[]`
- `comptes-entreprise`

## Finances annuelles

Clé candidate : `(siren, annee, date_de_cloture_exercice)`

### Champs observés

| Champ | Commentaire |
|---|---|
| `annee` | Exercice |
| `date_de_cloture_exercice` | Clôture |
| `duree_exercice` | Mois |
| `chiffre_affaires` | CA |
| `chiffre_affaires_export` | Export |
| `resultat` | Résultat |
| `effectif` | Effectif |
| `marge_brute` | Marge brute |
| `excedent_brut_exploitation` | EBE |
| `resultat_exploitation` | Exploitation |
| `taux_croissance_chiffre_affaires` | Croissance |
| `taux_marge_brute` | Ratio |
| `taux_marge_EBITDA` | Ratio |
| `taux_marge_operationnelle` | Ratio |
| `BFR`, `BFR_exploitation`, `BFR_hors_exploitation` | Besoin fonds de roulement |
| `BFR_jours_CA` | BFR en jours de CA |
| `delai_paiement_clients_jours` | DSO |
| `delai_paiement_fournisseurs_jours` | DPO |
| `capacite_autofinancement` | CAF |
| `fonds_roulement_net_global` | FRNG |
| `tresorerie` | Trésorerie |
| `dettes_financieres` | Dettes |
| `capacite_remboursement` | Ratio |
| `ratio_endettement` | Ratio |
| `autonomie_financiere` | Ratio |
| `liquidite_generale` | Ratio |
| `marge_nette` | Ratio |
| `fonds_propres` | Fonds propres |
| `rentabilite_fonds_propres` | ROE |
| `rentabilite_economique` | ROA |
| `valeur_ajoutee` | VA |
| `salaires_charges_sociales` | Salaires + charges |
| `impots_taxes` | Impôts et taxes |

## Dépôts de comptes

Clé candidate : `(siren, annee_cloture, type_comptes)`

| Champ | Commentaire |
|---|---|
| `date_depot` | Date dépôt |
| `date_cloture` | Date clôture |
| `annee_cloture` | Année |
| `type_comptes` | CS, CC, etc. |
| `confidentialite` | Confidentialité |
| `disponible` | PDF disponible |
| `nom_fichier_pdf` | Nom fichier |
| `token` | Token pour `lire-documents` |
| `disponible_xlsx` | XLSX disponible |
| `token_xlsx` | Token XLSX |

## `comptes-entreprise`

Permet de demander :

- une ou plusieurs années ;
- des ratios précis ;
- option `inclure_bilan_complet` pour récupérer les liasses fiscales structurées.

À utiliser avec parcimonie, surtout si le besoin est déjà couvert par `informations-entreprise.finances`.
