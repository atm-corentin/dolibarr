-- <one line to give the program's name and a brief idea of what it does.>
-- Copyright (C) <year>  <name of author>
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

-- Export
INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('xlsx', 'export', '', 'ExportXLSX', '', 'xlsx.png', 1, 1);
INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('pdf', 'export', '', 'ExportPDF', '', 'pdf.png', 2, 1);
-- INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('png', 'export', '', 'ExportPNG', '', 'image.png', 3, 1);
-- INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('sql', 'export', '', 'ExportSQL', 'ExportsFullList', 'sql.png', 4, 1);
-- INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('csvfromdb', 'export', 'CSVFromDBDescription', 'ExportCSVFromDB', 'ExportsFullList', 'csv.png', 5, 0);

-- Import
-- INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('csv', 'import', 'DataShouldFitToTableFields', 'ImportCSV', '', 'csv.png', 10, 0);
-- INSERT INTO llx_doliexportlist_format(format, type, description, title, warning, picto, position, active) VALUES ('sql', 'import', '', 'ImportSQL', '', 'sql_import.png', 11, 1);
