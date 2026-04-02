-- Copyright (C) 2026        ATM Consulting
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

ALTER TABLE llx_clichaumeil_rfa_summary ADD UNIQUE INDEX uk_clichaumeil_rfa_summary_entity_year_soc (`entity`, `year`, fk_soc);
ALTER TABLE llx_clichaumeil_rfa_summary ADD INDEX idx_clichaumeil_rfa_summary_entity_year_root (`entity`, `year`, fk_root_soc);
ALTER TABLE llx_clichaumeil_rfa_summary ADD INDEX idx_clichaumeil_rfa_summary_entity_year_ca (`entity`, `year`, ca_achats);
ALTER TABLE llx_clichaumeil_rfa_summary ADD INDEX idx_clichaumeil_rfa_summary_entity_year_rate (`entity`, `year`, taux_rfa);
ALTER TABLE llx_clichaumeil_rfa_summary ADD INDEX idx_clichaumeil_rfa_summary_entity_year_discount (`entity`, `year`, discount_amount_rfa);
ALTER TABLE llx_clichaumeil_rfa_summary ADD INDEX idx_clichaumeil_rfa_summary_entity_year_status (`entity`, `year`, rfa_status);

ALTER TABLE llx_clichaumeil_rfa_summary ADD CONSTRAINT fk_clichaumeil_rfa_summary_societe FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid) ON DELETE CASCADE;
ALTER TABLE llx_clichaumeil_rfa_summary ADD CONSTRAINT fk_clichaumeil_rfa_summary_root_societe FOREIGN KEY (fk_root_soc) REFERENCES llx_societe (rowid) ON DELETE CASCADE;
ALTER TABLE llx_clichaumeil_rfa_summary ADD CONSTRAINT fk_clichaumeil_rfa_summary_rfa FOREIGN KEY (fk_chaumeilrfa) REFERENCES llx_clichaumeil_chaumeilrfa (rowid) ON DELETE CASCADE;
