-- Copyright (C) 2021   Fabien FERNANDES ALVES <fabien@code42.fr>
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
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_c42migration(
    rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
    date_creation datetime NOT NULL,
    module_name varchar(255),
    module_version varchar(255),
    action varchar(255),
    entity integer DEFAULT 1
) ENGINE=innodb;
