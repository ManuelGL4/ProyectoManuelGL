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


CREATE TABLE llx_proyectos_oferta_materiales(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_project integer NOT NULL, 
	fk_product integer NOT NULL, 
	fk_chapter integer NOT NULL, 
	quantity double NOT NULL, 
	price double NOT NULL, 
	discount double NOT NULL, 
	taxable_base double NOT NULL, 
	material_cost double NOT NULL, 
	transport_cost double NOT NULL, 
	installation_cost double NOT NULL, 
	development_cost double NOT NULL, 
	test_cost double NOT NULL, 
	equivalence varchar(255) NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
