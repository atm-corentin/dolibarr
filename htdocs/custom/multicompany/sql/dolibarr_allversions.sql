-- ============================================================================
-- Copyright (C) 2010-2024 Regis Houssin  <regis.houssin@inodbox.com>
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
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===========================================================================

-- 3.1.0 to 3.2.0

ALTER TABLE llx_entity ADD COLUMN options text AFTER fk_user_creat;

-- 3.6.0 to 3.7.0

create table llx_entity_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)
) ENGINE=innodb;

-- 3.8.0 to 3.9.0

create table llx_entity_thirdparty
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  fk_entity			integer NOT NULL,
  fk_soc			integer NOT NULL
  
) ENGINE=innodb;

ALTER TABLE llx_entity_thirdparty ADD UNIQUE INDEX idx_entity_thirdparty_fk_soc (entity, fk_entity, fk_soc);

ALTER TABLE llx_entity_thirdparty ADD CONSTRAINT fk_entity_thirdparty_fk_entity FOREIGN KEY (fk_entity) REFERENCES llx_entity (rowid);
ALTER TABLE llx_entity_thirdparty ADD CONSTRAINT fk_entity_thirdparty_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid);

-- 5.0.0 to 6.0.0

UPDATE llx_const SET name = __ENCRYPT('MULTICOMPANY_THIRDPARTY_SHARING_ENABLED')__ WHERE name = __ENCRYPT('MULTICOMPANY_SOCIETE_SHARING_ENABLED')__;
UPDATE llx_const SET name = __ENCRYPT('MULTICOMPANY_BANKACCOUNT_SHARING_ENABLED')__ WHERE name = __ENCRYPT('MULTICOMPANY_BANK_ACCOUNT_SHARING_ENABLED')__;

ALTER TABLE llx_entity ADD COLUMN rang smallint DEFAULT 0 NOT NULL;

ALTER TABLE llx_entity MODIFY COLUMN visible tinyint DEFAULT 1 NOT NULL;
ALTER TABLE llx_entity MODIFY COLUMN active tinyint DEFAULT 1 NOT NULL;

-- 13.0.0 to 14.0.0

ALTER TABLE llx_product_perentity ADD COLUMN tva_tx double(6,3) DEFAULT NULL;

CREATE TABLE llx_entity_element_sharing
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer	DEFAULT 1 NOT NULL,
  element		varchar(64) NOT NULL,
  fk_element	integer NOT NULL
)ENGINE=innodb;

ALTER TABLE llx_entity_element_sharing ADD UNIQUE INDEX idx_entity_element_sharing_id (entity, fk_element, element);
ALTER TABLE llx_entity_element_sharing ADD CONSTRAINT fk_entity_element_sharing_fk_entity FOREIGN KEY (entity) REFERENCES llx_entity (rowid);

-- 15.0.0 to 16.0.0

ALTER TABLE llx_product_perentity MODIFY tva_tx double(7,4) DEFAULT NULL;

ALTER TABLE llx_product_perentity ADD COLUMN default_vat_code varchar(10);
ALTER TABLE llx_product_perentity ADD COLUMN recuperableonly integer NOT NULL DEFAULT '0';
ALTER TABLE llx_product_perentity ADD COLUMN localtax1_tx    double(7,4)  DEFAULT 0;
ALTER TABLE llx_product_perentity ADD COLUMN localtax1_type  varchar(10)  NOT NULL DEFAULT '0';
ALTER TABLE llx_product_perentity ADD COLUMN localtax2_tx    double(7,4)  DEFAULT 0;
ALTER TABLE llx_product_perentity ADD COLUMN localtax2_type  varchar(10)  NOT NULL DEFAULT '0';
ALTER TABLE llx_product_perentity ADD COLUMN tosell          tinyint      DEFAULT 1;
ALTER TABLE llx_product_perentity ADD COLUMN tobuy           tinyint      DEFAULT 1;
ALTER TABLE llx_product_perentity ADD COLUMN url             varchar(255);
ALTER TABLE llx_product_perentity ADD COLUMN barcode         varchar(180) DEFAULT NULL;
ALTER TABLE llx_product_perentity ADD COLUMN fk_barcode_type integer      DEFAULT NULL;

-- 19.0.2 to 19.0.3

ALTER TABLE llx_entity ADD COLUMN url varchar(255) AFTER description;
