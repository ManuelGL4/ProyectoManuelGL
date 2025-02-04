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


CREATE TABLE llx_produccion_orden_de_trabajo_gastos(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_orden integer NOT NULL, 
	fk_fase integer NOT NULL, 
	codigo varchar(255) NOT NULL, 
	descripcion text NOT NULL, 
	unidades integer NOT NULL, 
	stock integer, 
	tipo varchar(255) NOT NULL, 
	coste double NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
