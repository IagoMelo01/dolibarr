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


CREATE TABLE llx_safra_recomendacaoadubo(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	label varchar(255), 
	qty real, 
	fk_soc integer, 
	fk_project integer NOT NULL, 
	description text, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status integer NOT NULL, 
	expect_prod double(12,4) NOT NULL, 
	arquivo_json text, 
	id_metodo_fosforo integer NOT NULL, 
	id_classe_textural_solo integer NOT NULL, 
	ctc integer NOT NULL, 
	potassio integer NOT NULL, 
	materia_organica integer NOT NULL, 
	teor_argila integer NOT NULL, 
	saturacao_bases integer NOT NULL, 
	fosforo integer NOT NULL, 
	prnt_calcario integer, 
	fk_cultura integer NOT NULL, 
	dose_calc_rec double(6,2), 
	tab_compatibilidade text
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
