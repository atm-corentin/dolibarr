-- <one line to give the program's name and a brief idea of what it does.>
-- Copyright (C) <year>  <name of author>
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
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

CREATE TABLE llx_doliexportlist_format(
	rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
        format VARCHAR(30) NOT NULL,
        type VARCHAR(30) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        warning VARCHAR(255) DEFAULT NULL,
        picto VARCHAR(255) NOT NULL,
        position INTEGER NOT NULL,
        active INTEGER DEFAULT 1 NOT NULL
);
