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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_rowid (rowid);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_ref (ref);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_adjudicatario (adjudicatario);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_representante (representante);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_obra (obra);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_tipo_oferta (tipo_oferta);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_averia (averia);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_cliente (cliente);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_dto_cliente (dto_cliente);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_dto_oferta (dto_oferta);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_dto_pp (dto_pp);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_tipo_cliente (tipo_cliente);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_regimen_iva (regimen_iva);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_contacto (contacto);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_divisa_origen (divisa_origen);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_divisa_destino (divisa_destino);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_idioma (idioma);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_forma_pago (forma_pago);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_vencimientos (vencimientos);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD CONSTRAINT llx_averiasreparaciones_averias_ofertas_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD INDEX idx_averiasreparaciones_averias_ofertas_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD UNIQUE INDEX uk_averiasreparaciones_averias_ofertas_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_averiasreparaciones_averias_ofertas ADD CONSTRAINT llx_averiasreparaciones_averias_ofertas_fk_field FOREIGN KEY (fk_field) REFERENCES llx_averiasreparaciones_myotherobject(rowid);

