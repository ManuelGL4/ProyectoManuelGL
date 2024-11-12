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
ALTER TABLE llx_produccion_orden_de_trabajo_gastos ADD INDEX idx_produccion_orden_de_trabajo_gastos_rowid (rowid);
ALTER TABLE llx_produccion_orden_de_trabajo_gastos ADD INDEX idx_produccion_orden_de_trabajo_gastos_fk_orden (fk_orden);
ALTER TABLE llx_produccion_orden_de_trabajo_gastos ADD INDEX idx_produccion_orden_de_trabajo_gastos_fk_fase (fk_fase);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_produccion_orden_de_trabajo_gastos ADD UNIQUE INDEX uk_produccion_orden_de_trabajo_gastos_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_produccion_orden_de_trabajo_gastos ADD CONSTRAINT llx_produccion_orden_de_trabajo_gastos_fk_field FOREIGN KEY (fk_field) REFERENCES llx_produccion_myotherobject(rowid);

