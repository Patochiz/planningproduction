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

-- Exemples de données de test pour les matières premières
-- Ces données permettent de tester la fonctionnalité de réorganisation

-- ATTENTION: Ce script est uniquement pour les tests de développement
-- Ne pas exécuter en production sans vérifier les données existantes

-- Insérer des exemples de matières premières avec ordre défini
INSERT INTO llx_planningproduction_matieres (code_mp, stock, ordre, date_creation, fk_user_creat, entity) VALUES
('ACIER INOX 304', 1500.00, 1, NOW(), 1, 1),
('ALUMINIUM 6061', 800.50, 2, NOW(), 1, 1),
('CUIVRE C101', 250.75, 3, NOW(), 1, 1),
('BRONZE PHOSPHOREUX', 120.00, 4, NOW(), 1, 1),
('LAITON CZ121', 300.25, 5, NOW(), 1, 1),
('ACIER CARBONE S235', 2000.00, 6, NOW(), 1, 1),
('TITANE GRADE 2', 50.50, 7, NOW(), 1, 1),
('INCONEL 625', 15.75, 8, NOW(), 1, 1),
('HASTELLOY C276', 8.25, 9, NOW(), 1, 1),
('DUPLEX 2205', 180.00, 10, NOW(), 1, 1);

-- Variantes de codes pour tester la recherche et le tri
INSERT INTO llx_planningproduction_matieres (code_mp, stock, ordre, date_creation, fk_user_creat, entity) VALUES
('400 BLANC', 500.00, 11, NOW(), 1, 1),
('400 NOIR', 450.75, 12, NOW(), 1, 1),
('400 ROUGE', 275.50, 13, NOW(), 1, 1),
('400 BLEU', 320.25, 14, NOW(), 1, 1),
('500 STANDARD', 800.00, 15, NOW(), 1, 1),
('500 PREMIUM', 600.50, 16, NOW(), 1, 1),
('600 ÉCONOM.', 1200.75, 17, NOW(), 1, 1),
('TUBE Ø20x2', 150.00, 18, NOW(), 1, 1),
('TUBE Ø25x2', 200.00, 19, NOW(), 1, 1),
('TUBE Ø30x3', 180.50, 20, NOW(), 1, 1);

-- Matières avec stocks faibles pour tester les alertes
INSERT INTO llx_planningproduction_matieres (code_mp, stock, ordre, date_creation, fk_user_creat, entity) VALUES
('STOCK CRITIQUE 1', 5.00, 21, NOW(), 1, 1),
('STOCK CRITIQUE 2', 2.50, 22, NOW(), 1, 1),
('STOCK ZERO', 0.00, 23, NOW(), 1, 1);

-- Matières avec caractères spéciaux pour tester la robustesse
INSERT INTO llx_planningproduction_matieres (code_mp, stock, ordre, date_creation, fk_user_creat, entity) VALUES
('MATIÈRE SPÉCIALE É', 100.00, 24, NOW(), 1, 1),
('MAT-AVEC-TIRETS', 150.00, 25, NOW(), 1, 1),
('MAT_AVEC_UNDERSCORES', 200.00, 26, NOW(), 1, 1),
('MAT (PARENTHÈSES)', 75.50, 27, NOW(), 1, 1),
('MAT&SYMBOLES#123', 300.75, 28, NOW(), 1, 1);

-- Informations sur les données de test
-- Total: 28 matières premières de test
-- Ordre: de 1 à 28 (séquentiel)
-- Stocks variés: de 0 à 2000
-- Différents types de codes pour tester toutes les situations
