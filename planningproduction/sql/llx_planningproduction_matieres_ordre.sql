-- Copyright (C) 2024 Patrick Delcroix
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- Ajouter la colonne ordre pour permettre le tri des matières premières
ALTER TABLE llx_planningproduction_matieres ADD COLUMN ordre integer DEFAULT 0 NOT NULL;

-- Mettre à jour l'ordre pour les enregistrements existants
UPDATE llx_planningproduction_matieres SET ordre = rowid WHERE ordre = 0;

-- Ajouter un index sur la colonne ordre pour améliorer les performances
ALTER TABLE llx_planningproduction_matieres ADD INDEX idx_planningproduction_matieres_ordre (ordre);
