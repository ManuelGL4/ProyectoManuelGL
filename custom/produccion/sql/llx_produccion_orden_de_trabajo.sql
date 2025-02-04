-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_produccion_orden_de_trabajo(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	fk_project integer NOT NULL, 
	fk_task integer, 
	fk_client integer NOT NULL, 
	fk_construction integer NOT NULL, 
	fk_user integer NOT NULL, 
	fk_averia integer, 
	fk_equipo integer, 
	type_order integer NOT NULL, 
	date_issue date NOT NULL, 
	days_estimated integer NOT NULL, 
	days_real integer NOT NULL, 
	date_planned date NOT NULL, 
	date_end date NOT NULL, 
	description varchar(255) NOT NULL, 
	reason_ending varchar(255), 
	teoric_cost double, 
	actual_cost double, 
	closed smallint, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	status smallint NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
