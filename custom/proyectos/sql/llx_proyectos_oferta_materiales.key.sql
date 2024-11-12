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
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_rowid (rowid);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_fk_project (fk_project);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_fk_product (fk_product);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_fk_chapter (fk_chapter);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_quantity (quantity);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_price (price);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_discount (discount);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_taxable_base (taxable_base);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_material_cost (material_cost);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_transport_cost (transport_cost);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_installation_cost (installation_cost);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_development_cost (development_cost);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_test_cost (test_cost);
ALTER TABLE llx_proyectos_oferta_materiales ADD INDEX idx_proyectos_oferta_materiales_equivalence (equivalence);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_proyectos_oferta_materiales ADD UNIQUE INDEX uk_proyectos_oferta_materiales_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_proyectos_oferta_materiales ADD CONSTRAINT llx_proyectos_oferta_materiales_fk_field FOREIGN KEY (fk_field) REFERENCES llx_proyectos_myotherobject(rowid);

