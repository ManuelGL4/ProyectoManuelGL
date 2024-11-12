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


CREATE TABLE khns_calidad_accionc_p(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status smallint NOT NULL, 
	codigo varchar(255) NOT NULL, 
	fecha_definicion datetime NOT NULL, 
	tipo integer NOT NULL, 
	problema varchar(255), 
	causas varchar(255), 
	descripcion varchar(255), 
	responsable integer, 
	plazo_implantacion varchar(255), 
	director_general integer NOT NULL, 
	fecha_aprobacion datetime, 
	director_calidad integer NOT NULL, 
	fecha_seguimiento datetime
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
