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
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_rowid (rowid);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_fk_report (fk_report);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_id_fase_khonos (id_fase_khonos);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_description (description);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_advance (advance);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_remarks (remarks);
ALTER TABLE llx_mantenimiento_informes_equipos ADD INDEX idx_mantenimiento_informes_equipos_future_remarks (future_remarks);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_mantenimiento_informes_equipos ADD UNIQUE INDEX uk_mantenimiento_informes_equipos_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_mantenimiento_informes_equipos ADD CONSTRAINT llx_mantenimiento_informes_equipos_fk_field FOREIGN KEY (fk_field) REFERENCES llx_mantenimiento_myotherobject(rowid);

