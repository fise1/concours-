-- =============================================================================
-- SCRIPT DE NETTOYAGE ET SÉCURISATION DE LA BASE DE DONNÉES CONCOURS
-- =============================================================================

USE concours;

-- 1. Suppression des doublons stricts d'inscriptions (INC-201 / INC-203)
-- On conserve uniquement la première inscription enregistrée (id le plus petit) pour un (concours_id, npi)
DELETE i1 FROM inscriptions i1
INNER JOIN inscriptions i2 
    ON i1.concours_id = i2.concours_id 
    AND i1.npi = i2.npi 
    AND i1.id > i2.id;

-- 2. Traitement des candidats excédentaires du centre de Lokossa (INC-202)
-- Conformément aux consignes, AUCUNE suppression physique n'est effectuée.
-- Les candidats enregistrés au-delà de la capacité de 300 places sont basculés au statut 'en_attente_validation_capacite'
UPDATE inscriptions 
SET statut = 'en_attente_validation_capacite'
WHERE centre_id = (SELECT id FROM centres WHERE code = 'L' LIMIT 1)
  AND id NOT IN (
      SELECT id FROM (
          SELECT id FROM inscriptions 
          WHERE centre_id = (SELECT id FROM centres WHERE code = 'L' LIMIT 1)
          ORDER BY id ASC 
          LIMIT 300
      ) AS premiers_inscrits
  );

-- 3. Emprunte d'unicité physique sur la table pour interdire définitivement les doublons au niveau SGBD
-- Si la contrainte n'existe pas encore :
ALTER TABLE inscriptions 
ADD CONSTRAINT uk_concours_npi UNIQUE (concours_id, npi);

-- 4. Indexation pour accélérer les consultations et requêtes de listes d'inscrits par centre (INC-207)
ALTER TABLE inscriptions 
ADD INDEX idx_centre_statut (centre_id, statut);