-- Migration 002 : ajout profil_proprietaire et nb_proprietaires_identifies sur parcelles
-- profil_proprietaire : 'particulier' | 'societe' | 'indivision' | 'inconnu'
-- nb_proprietaires_identifies : nb de proprietaires avec SIREN connu (0 = particulier)

ALTER TABLE parcelles
  ADD COLUMN nb_proprietaires_identifies INT NULL AFTER surface_disponible,
  ADD COLUMN profil_proprietaire VARCHAR(32) NULL AFTER nb_proprietaires_identifies,
  ADD KEY idx_parcelles_profil (profil_proprietaire);
