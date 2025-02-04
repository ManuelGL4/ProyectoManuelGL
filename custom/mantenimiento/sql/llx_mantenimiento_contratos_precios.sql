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


CREATE TABLE llx_mantenimiento_contratos_precios(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	id_contrato integer NOT NULL, 
	coste_material float, 
	coste_pruebas float, 
	coste_instalacion float, 
	horas_dedicadas float, 
	coste_horas float, 
	dto_cliente float, 
	dto_pedido float, 
	dto_linea_total float, 
	bruto float, 
	base_imponible float, 
	iva float, 
	suma float, 
	subtotal float, 
	total float
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
