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


CREATE TABLE llx_averiasreparaciones_averias_ofertas(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	adjudicatario integer, 
	nombre text NOT NULL, 
	representante integer NOT NULL, 
	obra integer NOT NULL, 
	fecha_oferta date NOT NULL, 
	tipo_oferta smallint, 
	averia integer, 
	cliente integer NOT NULL, 
	dto_cliente integer, 
	dto_oferta integer, 
	dto_pp integer, 
	tipo_cliente smallint, 
	regimen_iva smallint, 
	delegacion integer NOT NULL, 
	contacto integer NOT NULL, 
	divisa_origen smallint, 
	divisa_destino smallint, 
	idioma smallint, 
	forma_pago smallint NOT NULL, 
	observaciones text, 
	plazo_entrega varchar(255), 
	vencimientos integer, 
	porcent_res integer, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	status smallint NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
