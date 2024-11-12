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


CREATE TABLE llx_averiasreparaciones_averias(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	fecha_averia datetime NOT NULL, 
	fecha_estimada datetime, 
	tiempo_estimado integer, 
	descripcion text, 
	fk_cliente integer NOT NULL, 
	fk_delegacion integer, 
	razon_social varchar(255), 
	cif varchar(255), 
	direccion varchar(256), 
	poblacion varchar(256), 
	codigo_postal varchar(10), 
	provincia varchar(256), 
	fk_project integer NOT NULL, 
	fk_informe integer, 
	contacto integer, 
	movil varchar(20), 
	telefono varchar(20), 
	ext varchar(20), 
	email varchar(100), 
	fax varchar(50), 
	direccion_envio text NOT NULL, 
	estado_averia smallint NOT NULL, 
	description_problema text, 
	control smallint NOT NULL, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status smallint NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
