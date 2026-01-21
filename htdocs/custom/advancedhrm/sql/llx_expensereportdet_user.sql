CREATE TABLE llx_expensereportdet_user(
    -- BEGIN MODULEBUILDER FIELDS
                                                  rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
                                                  fk_user integer,
                                                  fk_expensereportdet integer
    -- END MODULEBUILDER FIELDS
) ENGINE=innodb;
