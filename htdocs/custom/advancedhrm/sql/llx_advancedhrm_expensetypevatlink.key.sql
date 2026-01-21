-- Copyright (C) ---Put here your own copyright and developer email---
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
ALTER TABLE llx_advancedhrm_expensetypevatlink ADD INDEX idx_advancedhrm_expensetypevatlink_rowid (rowid);
ALTER TABLE llx_advancedhrm_expensetypevatlink ADD INDEX idx_advancedhrm_expensetypevatlink_fk_expenseType (fk_expenseType);
ALTER TABLE llx_advancedhrm_expensetypevatlink ADD INDEX idx_advancedhrm_expensetypevatlink_fk_vat_tx (fk_vat_tx);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_advancedhrm_expensetypevatlink ADD UNIQUE INDEX uk_advancedhrm_expensetypevatlink_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_advancedhrm_expensetypevatlink ADD CONSTRAINT llx_advancedhrm_expensetypevatlink_fk_field FOREIGN KEY (fk_field) REFERENCES llx_advancedhrm_myotherobject(rowid);

