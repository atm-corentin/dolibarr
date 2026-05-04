-- Copyright (C) 2026        ATM Consulting
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.

ALTER TABLE llx_c_clichaumeil_commission_coeff ADD UNIQUE INDEX uk_c_clichaumeil_commission_coeff_entity_code (entity, code);
ALTER TABLE llx_c_clichaumeil_commission_coeff ADD UNIQUE INDEX uk_c_clichaumeil_commission_coeff_entity_role_tag (entity, role_code, customer_tag);
ALTER TABLE llx_c_clichaumeil_commission_coeff ADD INDEX idx_c_clichaumeil_commission_coeff_entity_active (entity, active);
