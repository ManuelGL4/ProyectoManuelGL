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


CREATE TABLE llx_mantenimiento_contratos(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	order_number varchar(128), 
	name varchar(128), 
	description text, 
	project_id integer NOT NULL, 
	representative_id integer NOT NULL, 
	representative_commission integer, 
	offer_id integer NOT NULL, 
	offer_date date, 
	offer_type smallint, 
	contact_discount integer, 
	spare_parts_discount integer, 
	client_id integer NOT NULL, 
	client_discount integer, 
	client_authorization smallint, 
	client_same_facture smallint, 
	contact_id integer NOT NULL, 
	currency smallint NOT NULL, 
	languaje smallint NOT NULL, 
	delegation_id integer NOT NULL, 
	delegation_id_accountant integer, 
	delegation_id_manager integer, 
	delegation_id_processing integer, 
	payment_method smallint NOT NULL, 
	expirations integer, 
	periodicity smallint NOT NULL, 
	periodicity_select integer, 
	date_start date NOT NULL, 
	date_end date NOT NULL, 
	warranty_end date, 
	estimated_time integer, 
	estimated_anual_time integer, 
	ejercicio varchar(128), 
	numero integer, 
	estado_homologacion varchar(255), 
	id_regimen_iva integer, 
	ref_anterior varchar(128), 
	garantia varchar(128), 
	porc_resolucion integer, 
	plazo_entrega varchar(255), 
	ref_proyecto varchar(128), 
	id_contrato_padre integer, 
	id_estado integer, 
	usuario varchar(255), 
	fecha varchar(255), 
	dto_general varchar(128), 
	id_khonos integer, 
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
