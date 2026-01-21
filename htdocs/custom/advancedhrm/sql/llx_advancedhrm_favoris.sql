-- ============================================================================
--
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_advancedhrm_favoris(
  rowid          integer  AUTO_INCREMENT  PRIMARY KEY,
  adresse_depart	text,
  adresse_arrivee	text,
  fk_user			integer,
  entity			tinyint(1) DEFAULT 0
)ENGINE=innodb DEFAULT CHARSET=utf8;
