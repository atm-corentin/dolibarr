-- Copyright (C) 2026        ATM Consulting
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_clichaumeil_rfa_summary(
	`rowid` INTEGER AUTO_INCREMENT PRIMARY KEY NOT NULL,
	`entity` integer NOT NULL DEFAULT 1,
	`year` integer NOT NULL,
	fk_soc integer NOT NULL,
	fk_root_soc integer NOT NULL,
	fk_chaumeilrfa integer NOT NULL,
	is_aggregated smallint NOT NULL DEFAULT 0,
	contributor_count integer NOT NULL DEFAULT 0,
	ca_achats double NOT NULL DEFAULT 0,
	taux_rfa double NOT NULL DEFAULT 0,
	discount_amount_rfa double NOT NULL DEFAULT 0,
	rfa_status integer NOT NULL DEFAULT 0,
	date_calculated DATETIME NOT NULL
) ENGINE=INNODB;
