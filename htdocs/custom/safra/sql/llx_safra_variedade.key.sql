-- Copyright (C) 2024 SuperAdmin
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
ALTER TABLE llx_safra_variedade ADD INDEX idx_safra_variedade_rowid (rowid);
ALTER TABLE llx_safra_variedade ADD INDEX idx_safra_variedade_ref (ref);
ALTER TABLE llx_safra_variedade ADD CONSTRAINT llx_safra_variedade_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_safra_variedade ADD INDEX idx_safra_variedade_status (status);
ALTER TABLE llx_safra_variedade ADD CONSTRAINT llx_safra_variedade_fk_crop FOREIGN KEY (fk_crop) REFERENCES llx_safra_cultura(rowid);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_safra_variedade ADD UNIQUE INDEX uk_safra_variedade_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_safra_variedade ADD CONSTRAINT llx_safra_variedade_fk_field FOREIGN KEY (fk_field) REFERENCES llx_safra_myotherobject(rowid);

