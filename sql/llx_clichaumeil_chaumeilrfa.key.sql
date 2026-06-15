-- Copyright (C) 2025		SuperAdmin
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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_clichaumeil_chaumeilrfa ADD INDEX idx_clichaumeil_chaumeilrfa_rowid (rowid);
ALTER TABLE llx_clichaumeil_chaumeilrfa ADD UNIQUE INDEX uk_clichaumeil_chaumeilrfa_ref (ref);
ALTER TABLE llx_clichaumeil_chaumeilrfa ADD INDEX idx_clichaumeil_chaumeilrfa_status (status);
ALTER TABLE llx_clichaumeil_chaumeilrfa ADD INDEX idx_clichaumeil_chaumeilrfa_soc_year_palier (fk_soc, datestart, dateend, palier);
-- END MODULEBUILDER INDEXES

-- Delete cascade RFA on delete soc
ALTER TABLE llx_clichaumeil_chaumeilrfa ADD CONSTRAINT fk_chaumeilrfa_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid) ON DELETE CASCADE;
