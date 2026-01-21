-- ========================================================================
-- Copyright (C) 2020 		Open-DSI      <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

-- Contenu de la table llx_c_sirene_staff
INSERT IGNORE INTO llx_c_sirene_staff (code_sirene_staff, label_sirene_staff, code_dolibarr_staff) VALUES
('NN', '-', '0'),
('0', '0', 'EF0'),
('1', '1 – 5', 'EF1-5'),
('2', '1 – 5', 'EF1-5'),
('3', '6 – 10', 'EF6-10'),
('11', '11 – 50', 'EF11-50'),
('12', '11 - 50 ', 'EF11-50'),
('21', '51 – 100', 'EF51-100'),
('22', '100 – 500', 'EF100-500'),
('31', '100 – 500', 'EF100-500'),
('32', '100 – 500', 'EF100-500'),
('41', '> 500', 'EF500-'),
('42', '> 500', 'EF500-'),
('51', '> 500', 'EF500-'),
('52', '> 500', 'EF500-'),
('53', '> 500', 'EF500-');
