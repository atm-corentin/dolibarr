-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2012 	   Tommaso Basilici       <t.basilici@19.coop>
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
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
--

--
-- Do not include comments at end of line, this file is parsed during install and string '--' are removed.
--

insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('RECEP',       1,1, 'Due upon receipt','Due upon receipt',0,1,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('30D',         2,1, '30 days','Due in 30 days',0,30,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('30DENDMONTH', 3,1, '30 days end of month','Due in 30 days, end of month',1,30,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('60D',         4,1, '60 days','Due in 60 days, end of month',0,60,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('60DENDMONTH', 5,1, '60 days end of month','Due in 60 days, end of month',1,60,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('PT_ORDER',    6,1, 'Due on order','Due on order',0,1,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('PT_DELIVERY', 7,1, 'Due on delivery','Due on delivery',0,1,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('PT_5050',     8,1, '50 and 50','50% on order, 50% on delivery',0,1,NULL,__ENTITY__);

-- Add additional payment terms often needed in Austria
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('10D',         9,1,  '10 days','Due in 10 days',0,10,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('10DENDMONTH', 10,1, '10 days end of month','Due in 10 days, end of month',1,10,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('14D',         11,1, '14 days','Due in 14 days',0,14,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('14DENDMONTH', 12,1, '14 days end of month','Due in 14 days, end of month',1,14,NULL,__ENTITY__);
insert into llx_c_payment_term(code, sortorder, active, libelle, libelle_facture, type_cdr, nbjour, deposit_percent, entity) values ('DEP30PCTDEL', 13,0, '__DEPOSIT_PERCENT__% deposit','__DEPOSIT_PERCENT__% deposit, remainder on delivery',0,1,'30',__ENTITY__);