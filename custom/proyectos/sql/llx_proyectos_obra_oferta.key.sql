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
ALTER TABLE llx_proyectos_obra_oferta ADD INDEX idx_proyectos_obra_oferta_rowid (rowid);
ALTER TABLE llx_proyectos_obra_oferta ADD INDEX idx_proyectos_obra_oferta_fk_project (fk_project);
ALTER TABLE llx_proyectos_obra_oferta ADD INDEX idx_proyectos_obra_oferta_fk_obra (fk_obra);
ALTER TABLE llx_proyectos_obra_oferta ADD INDEX idx_proyectos_obra_oferta_adjudicado (adjudicado);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_proyectos_obra_oferta ADD UNIQUE INDEX uk_proyectos_obra_oferta_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_proyectos_obra_oferta ADD CONSTRAINT llx_proyectos_obra_oferta_fk_field FOREIGN KEY (fk_field) REFERENCES llx_proyectos_myotherobject(rowid);

