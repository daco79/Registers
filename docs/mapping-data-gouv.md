# Mapping Registers vers sources publiques

Date de premiere cartographie : 2026-06-14

Objectif : remplacer l'interrogation Pappers par une orchestration directe de sources publiques, principalement `data.gouv.fr` et les plateformes publiques referencees par `data.gouv.fr`.

Ce document mappe la structure locale `Registers` vers les sources ouvertes disponibles. Il distingue :

- `direct` : champ present dans une source publique avec correspondance forte ;
- `jointure` : champ reconstructible par jointure entre sources ;
- `partiel` : champ approchable mais pas strictement equivalent a Pappers ;
- `non_couvert` : pas de source open data evidente ou acces reglemente ;
- `a_confirmer` : source identifiee mais colonnes exactes a verifier dans un import pilote.

## Sources pivots

| Code | Source | Dataset data.gouv | ID | Acces / ressource utile |
|---|---|---|---|---|
| `sirene_ul` | Base Sirene - unite legale | Base Sirene des entreprises et de leurs etablissements | `5b7ffc618b4c4169d30727e0` | `StockUniteLegale`, CSV/Parquet mensuel |
| `sirene_etab` | Base Sirene - etablissement | Base Sirene des entreprises et de leurs etablissements | `5b7ffc618b4c4169d30727e0` | `StockEtablissement`, CSV/Parquet mensuel |
| `annuaire_entreprises` | Donnees consolidees Annuaire des Entreprises | Donnees des entreprises utilisees dans l'Annuaire des Entreprises | `667ebdd4547ab9bd6e4682d3` | CSV/Parquet `unites-legales`, `etablissements` |
| `rna` | Repertoire national des associations | RNA agrege a l'echelle nationale | `67164a231c2a52cc06b9b618` | `import.csv`, `waldec.csv`, Parquet |
| `bodacc` | BODACC | BODACC | `559395f1c751df0f51a453b9` | XML flux courant/historique + export Opendatasoft |
| `ban` | Base Adresse Nationale | Base Adresse Nationale | `5530fbacc751df5ff937dddb` | CSV national latest, API adresse |
| `cadastre` | Cadastre / PCI Etalab | Cadastre Etalab et fichiers cadastre.data.gouv.fr | a confirmer | GeoJSON/Parquet departementaux via cadastre.data.gouv.fr |
| `geo_dvf` | DVF geolocalisee | Demandes de valeurs foncieres geolocalisees | `5cc1b94a634f4165e96436c1` | `dvf.csv.gz`, CSV par millesime |
| `dvf_plus` | DVF+ Cerema | DVF+ open-data | `5d78f093634f411f434cd637` | CSV/SHP ou dump PostgreSQL/PostGIS |
| `rnb` | Referentiel National des Batiments | Referentiel National des Batiments | `65a5568dfc88169d0a5416ca` | Export national/departemental CSV zip |
| `dpe_logements` | DPE logements existants | DPE Logements existants depuis juillet 2021 | `67f7e557cb268460ce66c8d4` | data.ademe.fr `dpe03existant` + API |
| `dpe_neufs` | DPE logements neufs | DPE Logements neufs depuis juillet 2021 | `67f7e5758ffc5d79ab9e8c27` | data.ademe.fr |
| `dpe_tertiaire` | DPE tertiaire | DPE Tertiaire depuis juillet 2021 | `67f7e59231d941e1b216cb37` | data.ademe.fr `dpe01tertiaire` + API |
| `rnic` | Registre national des coproprietes | Registre national d'Immatriculation des Coproprietes | `62da71c068871f4c54258c7c` | CSV quotidien + dictionnaire CSV |
| `sitadel` | Permis / autorisations urbanisme | Liste des permis de construire et autres autorisations d'urbanisme | `689c42fa521ccf80ce954f83` | CSV Dido : logements, locaux, amenager, demolir |
| `gpu` | Geoportail de l'Urbanisme | Datasets GPU / services GPU | variable | A confirmer hors catalogue simple data.gouv |

## Entreprises

### `entreprises`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `siren` | `sirene_ul` | `siren` | direct | Cle primaire unite legale. |
| `siren_formate` | calcul | `siren` | jointure | Formatage local `XXX XXX XXX`. |
| `diffusable` | `sirene_ul` | `statutDiffusionUniteLegale` | direct | A convertir en booleen/statut. |
| `nom_entreprise` | `sirene_ul` | `denominationUniteLegale` ou nom/prenoms | jointure | Pour personnes physiques, recomposer depuis `nomUniteLegale`, `prenomUsuelUniteLegale`. |
| `personne_morale` | `sirene_ul` | `categorieJuridiqueUniteLegale`, `nomUniteLegale` | jointure | Inferer selon categorie juridique / presence nom personne physique. |
| `denomination` | `sirene_ul` | `denominationUniteLegale` | direct | Personnes morales. |
| `nom` | `sirene_ul` | `nomUniteLegale` | direct | Personnes physiques. |
| `prenom` | `sirene_ul` | `prenomUsuelUniteLegale` ou `prenom1UniteLegale` | direct | Personnes physiques. |
| `sexe` | `sirene_ul` | `sexeUniteLegale` | direct | Personnes physiques. |
| `sigle` | `sirene_ul` | `sigleUniteLegale` | direct |  |
| `code_naf` | `sirene_ul` | `activitePrincipaleUniteLegale` | direct | Equivalent Pappers `code_naf`. |
| `libelle_code_naf` | nomenclature NAF externe | code NAF | jointure | Necessite table de nomenclature NAF/APE. |
| `nomenclature_code_naf` | `sirene_ul` | `nomenclatureActivitePrincipaleUniteLegale` | direct |  |
| `domaine_activite` | nomenclature locale | `activitePrincipaleUniteLegale` | partiel | Regroupement a construire localement. |
| `objet_social` | RNE/actes non ouverts | - | non_couvert | Pas dans Sirene. Peut venir RNE/INPI si acces et licence. |
| `categorie_juridique` | `sirene_ul` | `categorieJuridiqueUniteLegale` | direct |  |
| `forme_juridique` | nomenclature INSEE | `categorieJuridiqueUniteLegale` | jointure | Table libelles categories juridiques. |
| `forme_exercice` | RNE/Pappers | - | non_couvert | Pas dans Sirene open stock. |
| `micro_entreprise` | `sirene_ul` + categorie/NAF | - | partiel | Pas un champ direct fiable dans le stock. |
| `entreprise_cessee` | `sirene_ul` | `etatAdministratifUniteLegale` | direct | `C` = cesse, `A` = active. |
| `date_creation` | `sirene_ul` | `dateCreationUniteLegale` | direct |  |
| `date_cessation` | `sirene_ul_historique` | periodes historiques | partiel | A reconstruire via historique unite legale. |
| `entreprise_employeuse` | `sirene_ul` | `caractereEmployeurUniteLegale` | direct |  |
| `societe_a_mission` | `sirene_ul` | `societeMissionUniteLegale` | direct |  |
| `economie_sociale_solidaire` | `sirene_ul` | `economieSocialeSolidaireUniteLegale` | direct |  |
| `effectif`, `effectif_min/max` | `sirene_ul` | `trancheEffectifsUniteLegale` | jointure | Convertir tranche vers bornes locales. |
| `annee_effectif` | `sirene_ul` | `anneeEffectifsUniteLegale` | direct |  |
| `categorie_entreprise` | `sirene_ul` | `categorieEntreprise` | direct |  |
| `annee_categorie_entreprise` | `sirene_ul` | `anneeCategorieEntreprise` | direct |  |
| `capital`, `devise_capital` | RNE/BODACC/annuaire | - | partiel | Peut apparaitre dans BODACC ou Annuaire, pas Sirene stock. |
| `statut_rcs`, `statut_rne`, `numero_rcs`, `greffe` | RNE/BODACC | - | partiel | BODACC donne `rcs`/greffe dans annonces, pas un etat complet. |
| `numero_tva_intracommunautaire` | calcul | `siren` | partiel | TVA FR calculable mais validite non garantie. |
| `validite_tva_intracommunautaire` | VIES/API tiers | - | non_couvert | Pas data.gouv. |
| `conventions_collectives` | sources travail/conventions | - | a_confirmer | Pas identifie dans data.gouv comme mapping direct. |
| `labels` | multiples | - | partiel | ORIAS/CCI/qualifications via jeux specialises a ajouter au cas par cas. |
| `parcelles_detenues` | `cadastre` + proprietaires fonciers | - | a_confirmer | Donnees proprietaires fonciers ouvertes seulement avec limites fortes/RGPD ; Pappers a une base enrichie. |

### `etablissements`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `siret` | `sirene_etab` | `siret` | direct | Cle primaire. |
| `siren` | `sirene_etab` | `siren` | direct | FK vers `entreprises`. |
| `nic` | `sirene_etab` | `nic` | direct |  |
| `type_etablissement` | calcul | `etablissementSiege` | jointure | Siege/secondaire. |
| `siege` | `sirene_etab` | `etablissementSiege` | direct | `true/false`. |
| `numero_voie` | `sirene_etab` | `numeroVoieEtablissement` | direct |  |
| `indice_repetition` | `sirene_etab` | `indiceRepetitionEtablissement` | direct |  |
| `type_voie` | `sirene_etab` | `typeVoieEtablissement` | direct |  |
| `libelle_voie` | `sirene_etab` | `libelleVoieEtablissement` | direct |  |
| `complement_adresse` | `sirene_etab` | `complementAdresseEtablissement` | direct |  |
| `adresse_ligne_1` | calcul | voie Sirene ou BAN | jointure | Recomposer ou normaliser avec BAN. |
| `code_postal` | `sirene_etab` | `codePostalEtablissement` | direct |  |
| `code_commune` | `sirene_etab` | `codeCommuneEtablissement` | direct |  |
| `ville` | `sirene_etab` | `libelleCommuneEtablissement` | direct |  |
| `pays`, `code_pays` | `sirene_etab` | `libellePaysEtrangerEtablissement`, `codePaysEtrangerEtablissement` | partiel | France implicite si vide. |
| `latitude`, `longitude` | `sirene_etab` ou `ban` | Lambert Sirene / geocodage BAN | jointure | Convertir Lambert ou geocoder. |
| `code_naf` | `sirene_etab` | `activitePrincipaleEtablissement` | direct |  |
| `libelle_code_naf` | nomenclature NAF | code NAF | jointure |  |
| `etablissement_employeur` | `sirene_etab` | `caractereEmployeurEtablissement` | direct |  |
| `effectif`, `effectif_min/max` | `sirene_etab` | `trancheEffectifsEtablissement` | jointure | Convertir tranche vers bornes. |
| `annee_effectif` | `sirene_etab` | `anneeEffectifsEtablissement` | direct |  |
| `date_de_creation` | `sirene_etab` | `dateCreationEtablissement` | direct |  |
| `etablissement_cesse` | `sirene_etab` | `etatAdministratifEtablissement` | direct | `F` ferme, `A` actif. |
| `enseigne` | `sirene_etab` | `enseigne1/2/3Etablissement` | direct | Fusion possible. |
| `nom_commercial` | `sirene_etab` | `denominationUsuelleEtablissement` | direct |  |
| `predecesseurs`, `successeurs` | `sirene_liens` | StockEtablissementLiensSuccession | direct | Mapping detaille a faire. |

### Gouvernance, beneficiaires, finances et documents

| Table / champ Registers | Source | Type | Notes |
|---|---|---|---|
| `representants.*` | RNE/INPI, BODACC partiel | partiel | Pas trouve en open data data.gouv generalise. BODACC contient parfois administration texte, pas structure exhaustive. |
| `beneficiaires_effectifs.*` | Registre BE | non_couvert | Acces soumis a habilitation ; pas d'open data public equivalent trouve. |
| `finances_annuelles.*` | comptes deposes, annuaire entreprises, INPI/RNE | partiel | Donnees agregees parfois dans Annuaire des Entreprises ; liasses detaillees non garanties. |
| `comptes_deposes.*` | BODACC / comptes associations / RNE | partiel | BODACC annonce depots, mais pas toujours donnees comptables structurees. |
| `depots_actes.*` | RNE/INPI | non_couvert | Documents d'actes non exposes comme Pappers dans data.gouv. |
| `publications_bodacc.*` | `bodacc` | direct | XML BODACC + export annonces commerciales. |
| `procedures_collectives.*` | `bodacc` | direct/partiel | Les procedures sont dans les annonces BODACC, parsing XML necessaire. |
| `sanctions.*` | jeux sectoriels AMF/CNIL/etc. | partiel | Sources multiples a brancher selon autorite. |
| `observations.*` | RCS/RNE | non_couvert | Pas trouve en open data general. |
| `marques.*` | INPI marques | a_confirmer | Probable source INPI, pas cartographie data.gouv validee ici. |
| `sites_internet.*` | Annuaire entreprises / sources web | partiel | Pas Sirene. |
| `cartographie_*` | derive local | jointure | A reconstruire depuis representants, filiales, proprietaires, BODACC, mais BE absent. |

## Immobilier

### `lieux_geocodes`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `query_text` | requete locale | - | direct | Parametre utilisateur. |
| `label` | `ban` | `label` | direct | Via API adresse ou CSV BAN. |
| `type_lieu` | `ban` | `type` | direct | Adresse, voie, lieu-dit, commune selon API. |
| `numero` | `ban` | `numero` / `housenumber` | direct | Selon format. |
| `rue` | `ban` | `nom_voie` / `street` | direct | Selon format. |
| `ville` | `ban` | `nom_commune` / `city` | direct |  |
| `code_postal` | `ban` | `code_postal` / `postcode` | direct |  |
| `code_commune` | `ban` | `code_insee` / `citycode` | direct |  |
| `latitude`, `longitude` | `ban` | `lat`, `lon` | direct |  |

### `parcelles`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `numero` | `cadastre` | id parcelle | direct | Format type `75111000BW0060`. |
| `parcelle_cadastrale` | `cadastre` | id parcelle | direct | Alias possible. |
| `geometrie` | `cadastre` | geometry | direct | GeoJSON/geom source. |
| `prefixe` | `cadastre` | prefixe | direct |  |
| `section` | `cadastre` | section | direct |  |
| `numero_plan` | `cadastre` | numero | direct |  |
| `adresse` | `ban` + cadastre/adresses cadastrales | libelle adresse | jointure | Cadastre pur ne porte pas toujours l'adresse. |
| `code_commune` | `cadastre` | commune / code INSEE | direct |  |
| `commune` | COG/INSEE ou BAN | nom commune | jointure |  |
| `code_departement` | derive | `code_commune` | jointure |  |
| `departement`, `region` | COG/INSEE | codes geo | jointure | Necessite table communes/departements/regions. |
| `contenance` | `cadastre` | contenance | direct | Surface cadastrale. |
| `surface_batie`, `surface_disponible` | `rnb` / BDNB / calcul | - | partiel | A calculer depuis batiments + parcelle. |
| `statistiques` | derive local | DVF/DPE/etc. | jointure | Calcul local. |

### Proprietaires et occupants

| Table / champ Registers | Source | Type | Notes |
|---|---|---|---|
| `parcelle_proprietaires.siren` | Fichiers fonciers / MAJIC / donnees proprietaires | a_confirmer | Donnee sensible ; data.gouv standard ne semble pas fournir proprietaires nominaux complets librement. Tes anciens exports Pappers l'ont, mais open data direct a confirmer. |
| `parcelle_proprietaires.nom_entreprise` | idem + Sirene | a_confirmer | Si SIREN obtenu, enrichissement via Sirene. |
| `parcelle_proprietaires.categorie_juridique` | `sirene_ul` | jointure | Via SIREN proprietaire. |
| `parcelle_proprietaires.activite_principale` | `sirene_ul` | jointure | Via SIREN proprietaire. |
| `parcelle_proprietaire_locaux.*` | Fichiers fonciers locaux/MAJIC | a_confirmer | Pappers expose des locaux proprietaire ; source ouverte a identifier. |
| `parcelle_occupants.siren/siret` | Sirene geocodee + adresse/parcelle | jointure/partiel | Reconstituer par geocodage des etablissements Sirene et intersection parcelle ; fiabilite moindre que Pappers. |
| `parcelle_occupants.activite_principale` | `sirene_etab` | jointure | Via SIRET/SIREN occupant. |
| `parcelle_occupants.date_entree_lieux` | Sirene historique | partiel | Date creation et periodes etablissement, pas entree effective dans les lieux. |

### `ventes_immobilieres`, `vente_lots`, `vente_parcelles_associees`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `date_vente` | `geo_dvf` / `dvf_plus` | `date_mutation` | direct |  |
| `nature` | `geo_dvf` / `dvf_plus` | `nature_mutation` | direct |  |
| `valeur_fonciere` | `geo_dvf` / `dvf_plus` | `valeur_fonciere` | direct |  |
| `type_local` | `geo_dvf` / `dvf_plus` | `type_local` | direct |  |
| `code_type_local` | `geo_dvf` / `dvf_plus` | `code_type_local` | direct |  |
| `surface_reelle_bati` | `geo_dvf` / `dvf_plus` | `surface_reelle_bati` | direct |  |
| `surface_terrain` | `geo_dvf` / `dvf_plus` | `surface_terrain` | direct |  |
| `nombre_pieces` | `geo_dvf` / `dvf_plus` | `nombre_pieces_principales` | direct |  |
| `nombre_lots` | `geo_dvf` / `dvf_plus` | lots DVF | direct/partiel | Structure a normaliser. |
| `adresse` | `geo_dvf` / BAN | adresse DVF + BAN | jointure |  |
| `ancienne_parcelle_cadastrale` | `geo_dvf` | parcelle ancienne si presente | a_confirmer |  |
| `vente_hash` | calcul | mutation fields | jointure | Dedupe local. |
| `vente_lots.numero` | `geo_dvf` | `lot1_numero`, etc. | direct | Plusieurs colonnes a pivoter. |
| `vente_lots.surface_carrez` | `geo_dvf` | surfaces Carrez lots | direct | Plusieurs colonnes a pivoter. |
| `vente_parcelles_associees.*` | `geo_dvf` / `dvf_plus` | references cadastrales | direct | Pivoter si plusieurs parcelles. |

### `batiments`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `batiment_groupe_id` | `rnb` ou BDNB | id batiment | direct | RNB/RNB ID ; Pappers utilisait souvent `batiment_groupe_id`. |
| `parcelle_numero` | `rnb` + cadastre | references parcelles / geometrie | jointure | Selon export RNB : jointure spatiale ou attribut. |
| `surface` | `rnb` / BDNB | surface | direct/partiel | Selon dataset. |
| `annee_construction` | BDNB / DPE | annee/periode construction | partiel | RNB pur peut etre moins riche ; BDNB/DPE enrichissent. |
| `nombre_logements` | BDNB / DPE / RNIC | nb logements | partiel | Selon source. |
| `hauteur_moyenne`, `hauteur_max` | BDNB | hauteur | partiel | A confirmer dans export retenu. |
| `etat`, `materiaux_mur`, `materiaux_toit` | BDNB | variables batiment | partiel | Pas toujours dans RNB. |
| `natures`, `usages` | `rnb` / BDNB | usages/nature | direct/partiel |  |

### `dpe`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `identifiant_dpe` | `dpe_logements` / `dpe_tertiaire` | identifiant DPE | direct | Nom exact a confirmer par API ADEME. |
| `parcelle_numero` | DPE + BAN/cadastre/RNB | adresse/geometrie/batiment | jointure | Pas toujours une reference parcelle directe. |
| `batiment_groupe_id` | DPE/RNB/BDNB | identifiant batiment si present | partiel |  |
| `source` | local | dataset DPE | direct | logement existant/neuf/tertiaire. |
| `arrete_2021` | dataset | depuis juillet 2021 | direct | Tous ces datasets sont post-2021. |
| `date_etablissement_dpe` | DPE ADEME | date_etablissement_dpe | direct |  |
| `date_reception_dpe` | DPE ADEME | date_reception_dpe | direct |  |
| `classe_bilan_dpe` | DPE ADEME | classe_bilan_dpe | direct |  |
| `classe_emission_ges` | DPE ADEME | classe_emission_ges | direct |  |
| `type_batiment_dpe` | DPE ADEME | type_batiment_dpe | direct |  |
| `surface_habitable_logement` | DPE ADEME | surface_habitable_logement | direct |  |
| `conso_5_usages_ep_m2` | DPE ADEME | conso_5_usages_ep_m2 | direct |  |
| `emission_ges_5_usages_m2` | DPE ADEME | emission_ges_5_usages_m2 | direct |  |
| `type_energie_chauffage` | DPE ADEME | type_energie_chauffage | direct |  |
| `type_installation_chauffage` | DPE ADEME | type_installation_chauffage | direct |  |
| `periode_construction_dpe` | DPE ADEME | periode_construction | direct/partiel | Nom exact a confirmer. |

### `coproprietes`

| Champ Registers | Source | Champ source RNIC | Type | Notes |
|---|---|---|---|---|
| `numero_immatriculation` | `rnic` | `numero_d_immatriculation` | direct | Cle RNIC. |
| `parcelle_numero` | `rnic` | `reference_cadastrale_1/2/3` | direct/partiel | Plusieurs parcelles a pivoter. |
| `nom` | `rnic` | `nom_d_usage_de_la_copropriete` | direct |  |
| `mandat_en_cours` | `rnic` | `mandat_en_cours_dans_la_copropriete` | direct | Convertir libelle en statut/booleen. |
| `nombre_total_lots` | `rnic` | `nombre_total_de_lots` | direct |  |
| `nombre_total_lots_a_usage_habitation_bureaux_commerces` | `rnic` | `nombre_total_de_lots_a_usage_d_habitation_de_bureaux_ou_de_comm...` | direct | Nom long exact a nettoyer. |
| `nombre_lots_a_usage_habitation` | `rnic` | `nombre_de_lots_a_usage_d_habitation` | direct |  |
| `nombre_lots_stationnement` | `rnic` | `nombre_de_lots_de_stationnement` | direct |  |
| `periode_construction` | `rnic` | `periode_de_construction` | direct | Attention dictionnaire decale dans l'affichage tabular, verifier fichier brut. |
| `type_syndic` | `rnic` | `type_de_syndic_benevole_professionnel_non_connu` | direct |  |
| `syndic_siret` | `rnic` | `siret_du_representant_legal` | direct | Permet SIREN par substring. |
| `syndic_nom` | `rnic` | `raison_sociale_du_representant_legal` | direct |  |
| `date_immatriculation` | `rnic` | `date_d_immatriculation` | direct |  |
| `date_reglement_copropriete` | `rnic` | `date_du_reglement_de_copropriete` | direct |  |
| `residence_service` | `rnic` | `residence_service` | direct |  |
| `syndicat_cooperatif` | `rnic` | `syndicat_cooperatif` | direct |  |
| `syndicat_principal_ou_syndicat_secondaire` | `rnic` | `syndicat_principal_ou_syndicat_secondaire` | direct |  |
| `date_mise_a_jour_rnic`, `date_derniere_maj` | `rnic` | `date_de_la_derniere_maj` | direct |  |
| `date_fin_dernier_mandat` | `rnic` | `date_de_fin_du_dernier_mandat` | direct |  |
| `adresse` | `rnic` | `adresse_de_reference` / numero voie / CP / commune | direct |  |
| `lat`, `long` | `rnic` | `lat`, `long` | direct | Pas encore dans schema, possible ajout futur. |

### `permis_urbanisme`

| Champ Registers | Source | Champ source | Type | Notes |
|---|---|---|---|---|
| `numero` | `sitadel` | numero autorisation/permis | direct | Nom exact selon fichier logements/locaux/amenager/demolir. |
| `type_permis` | `sitadel` | type autorisation / fichier source | direct |  |
| `etat` | `sitadel` | etat / decision / avancement | direct/partiel | A verifier dans dictionnaire Sitadel. |
| `date_autorisation` | `sitadel` | date autorisation | direct |  |
| `denomination_demandeur` | `sitadel` | demandeur | partiel | Possiblement anonymise/limite selon fichier. |
| `adresse` | `sitadel` | adresse terrain | direct/partiel | Normaliser avec BAN. |
| `superficie_terrain` | `sitadel` | superficie terrain | direct |  |
| `parcelle_numero` | `sitadel` + cadastre/BAN | adresse/geometrie | jointure | Pas toujours reference parcelle directe. |

### `fonds_de_commerce`

| Champ Registers | Source | Type | Notes |
|---|---|---|---|
| `activite`, `prix`, `categorie_vente`, `annonce_bodacc`, `origine_fonds` | `bodacc` | direct/partiel | BODACC ventes/cessions fonds ; parser annonces. |
| `acheteur`, `precedent_proprietaire`, `precedent_exploitant` | `bodacc` + Sirene | partiel | Texte annonce ou champs export BODACC. |
| `parcelle_numero` | BODACC + BAN/cadastre | jointure | Via adresse du fonds, geocodage puis parcelle. |

### `documents_urbanisme`, `zones_urbanisme`, `amenagements`

| Table / champ Registers | Source | Type | Notes |
|---|---|---|---|
| `documents_urbanisme.*` | `gpu` | a_confirmer | Le catalogue data.gouv remonte surtout des jeux locaux ; utiliser GPU/API/exports comme source pivot. |
| `zones_urbanisme.*` | `gpu` | a_confirmer | Jointure spatiale parcelle -> zonage. |
| `amenagements.type/surface` | cadastre, BD TOPO, OCS GE, sources locales | partiel | Pappers agrege probablement plusieurs sources ; mapping a construire par type. |

## Trous importants par rapport a Pappers

| Bloc Pappers / Registers | Couverture data.gouv | Commentaire |
|---|---|---|
| Beneficiaires effectifs | faible / non ouverte | Acces habilite, pas open data general. |
| Dirigeants structures | faible | BODACC a du texte, pas un referentiel complet type Pappers. |
| Actes/statuts PDF | faible | Plutot INPI/RNE, pas dataset data.gouv simple. |
| Objet social | partiel | RNE/actes, pas Sirene. |
| Capital social | partiel | BODACC/Annuaire/RNE selon cas. |
| Proprietaires de parcelles | a confirmer | Donnee fonciere sensible ; Pappers possede une consolidation qui n'est pas trivialement reproduite en open data. |
| Occupants par parcelle | reconstructible mais approximatif | Geocoder Sirene etablissements puis intersection parcelle. |
| Scorings Pappers | non couvert | A reconstruire localement par indicateurs, pas source publique directe. |
| Contacts emails/telephones | non couvert | Pas objectif open data par defaut. |

## Ordre conseille pour construire les connecteurs Registers

1. `sirene_ul` + `sirene_etab` : entreprises et etablissements.
2. `ban` + `cadastre` : adresses et parcelles.
3. `geo_dvf` ou `dvf_plus` : ventes.
4. `rnb` + `dpe_*` : batiments et DPE.
5. `rnic` : coproprietes.
6. `sitadel` : permis.
7. `bodacc` : publications, procedures collectives, fonds de commerce.
8. `gpu` : urbanisme.
9. Sources sensibles/manquantes : proprietaires, dirigeants, BE, actes.

