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


CREATE TABLE llx_mantenimiento_contratos_equipos(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_contract integer NOT NULL, 
	id_fase integer, 
	fk_product integer NOT NULL, 
	id_tipo_articulo integer, 
	tipo_articulo integer, 
	cantidad float, 
	num_serie varchar(100), 
	lote varchar(100), 
	mantenido integer, 
	observaciones varchar(100), 
	location varchar(255) DEFAULT NULL, 
	fin_garantia date DEFAULT NULL, 
	usuario varchar(100) DEFAULT NULL, 
	date_creation date DEFAULT NULL, 
	id_khonos integer DEFAULT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
