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
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_rowid (rowid);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_ref (ref);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_representante (representante);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_divisa (divisa);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_iva (iva);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_dto_cliente (dto_cliente);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_dto_factura (dto_factura);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_dto_pp (dto_pp);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_retencion (retencion);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_retencion_pagada (retencion_pagada);
ALTER TABLE llx_ventas_factura_origen ADD CONSTRAINT llx_ventas_factura_origen_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_ventas_factura_origen ADD INDEX idx_ventas_factura_origen_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_ventas_factura_origen ADD UNIQUE INDEX uk_ventas_factura_origen_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_ventas_factura_origen ADD CONSTRAINT llx_ventas_factura_origen_fk_field FOREIGN KEY (fk_field) REFERENCES llx_ventas_myotherobject(rowid);

