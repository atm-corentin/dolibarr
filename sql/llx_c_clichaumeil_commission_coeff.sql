-- Copyright (C) 2026        ATM Consulting
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_c_clichaumeil_commission_coeff(
	`rowid` integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	entity integer NOT NULL DEFAULT 1,
	`code` varchar(64) NOT NULL,
	role_code varchar(64) NOT NULL,
	customer_tag varchar(64) NOT NULL,
	label varchar(255) NOT NULL,
	coefficient double NOT NULL DEFAULT 0,
	`active` smallint NOT NULL DEFAULT 1
) ENGINE=innodb;
