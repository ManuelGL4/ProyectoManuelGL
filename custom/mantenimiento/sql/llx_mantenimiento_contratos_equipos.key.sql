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
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_rowid (rowid);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_fk_contract (fk_contract);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_id_fase (id_fase);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_fk_product (fk_product);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_id_tipo_articulo (id_tipo_articulo);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_tipo_articulo (tipo_articulo);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_cantidad (cantidad);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_num_serie (num_serie);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_lote (lote);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_mantenido (mantenido);
ALTER TABLE llx_mantenimiento_contratos_equipos ADD INDEX idx_mantenimiento_contratos_equipos_observaciones (observaciones);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_mantenimiento_contratos_equipos ADD UNIQUE INDEX uk_mantenimiento_contratos_equipos_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_mantenimiento_contratos_equipos ADD CONSTRAINT llx_mantenimiento_contratos_equipos_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mantenimiento_myotherobject(rowid);

