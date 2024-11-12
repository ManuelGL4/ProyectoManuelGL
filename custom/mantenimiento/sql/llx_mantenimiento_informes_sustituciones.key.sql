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
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_rowid (rowid);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_fk_report (fk_report);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_id_fase_khonos (id_fase_khonos);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_quantity (quantity);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_is_future (is_future);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_is_retired (is_retired);
ALTER TABLE llx_mantenimiento_informes_sustituciones ADD INDEX idx_mantenimiento_informes_sustituciones_is_returned (is_returned);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_mantenimiento_informes_sustituciones ADD UNIQUE INDEX uk_mantenimiento_informes_sustituciones_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_mantenimiento_informes_sustituciones ADD CONSTRAINT llx_mantenimiento_informes_sustituciones_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mantenimiento_myotherobject(rowid);

