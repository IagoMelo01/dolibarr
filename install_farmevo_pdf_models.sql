-- Farmevo PDF models for Dolibarr 23
-- Run this on the Dolibarr database after copying the PHP model files.
-- Change @entity if your Dolibarr company/entity is not 1.

SET @entity = 1;

START TRANSACTION;

-- Register PHP PDF document models.
-- Keep description NULL: Dolibarr treats a non-empty description as an ODT template directory.
INSERT INTO llx_document_model (nom, type, entity, libelle, description) VALUES
('farmevo_proposal', 'propal', @entity, 'Farmevo Proposta', NULL),
('farmevo_order', 'order', @entity, 'Farmevo Pedido', NULL),
('farmevo_invoice', 'invoice', @entity, 'Farmevo Fatura', NULL),
('farmevo_supplier_proposal', 'supplier_proposal', @entity, 'Farmevo Proposta Fornecedor', NULL),
('farmevo_supplier_order', 'order_supplier', @entity, 'Farmevo Pedido Fornecedor', NULL),
('farmevo_supplier_invoice', 'invoice_supplier', @entity, 'Farmevo Fatura Fornecedor', NULL),
('farmevo_shipping', 'shipping', @entity, 'Farmevo Remessa', NULL),
('farmevo_delivery', 'delivery', @entity, 'Farmevo Entrega', NULL),
('farmevo_reception', 'reception', @entity, 'Farmevo Recebimento', NULL),
('farmevo_expensereport', 'expensereport', @entity, 'Farmevo Relatorio de Despesas', NULL)
ON DUPLICATE KEY UPDATE
	libelle = VALUES(libelle),
	description = NULL;

-- Set Farmevo models as the default generation models.
INSERT INTO llx_const (name, entity, value, type, visible, note) VALUES
('PROPALE_ADDON_PDF', @entity, 'farmevo_proposal', 'chaine', 0, ''),
('COMMANDE_ADDON_PDF', @entity, 'farmevo_order', 'chaine', 0, ''),
('FACTURE_ADDON_PDF', @entity, 'farmevo_invoice', 'chaine', 0, ''),
('SUPPLIER_PROPOSAL_ADDON_PDF', @entity, 'farmevo_supplier_proposal', 'chaine', 0, ''),
('COMMANDE_SUPPLIER_ADDON_PDF', @entity, 'farmevo_supplier_order', 'chaine', 0, ''),
('INVOICE_SUPPLIER_ADDON_PDF', @entity, 'farmevo_supplier_invoice', 'chaine', 0, ''),
('EXPEDITION_ADDON_PDF', @entity, 'farmevo_shipping', 'chaine', 0, ''),
('DELIVERY_ADDON_PDF', @entity, 'farmevo_delivery', 'chaine', 0, ''),
('RECEPTION_ADDON_PDF', @entity, 'farmevo_reception', 'chaine', 0, ''),
('EXPENSEREPORT_ADDON_PDF', @entity, 'farmevo_expensereport', 'chaine', 0, '')
ON DUPLICATE KEY UPDATE
	value = VALUES(value),
	type = VALUES(type),
	visible = VALUES(visible);

-- Move existing documents still using Dolibarr defaults to Farmevo.
-- These updates avoid replacing records already assigned to another custom model.
UPDATE llx_propal
SET model_pdf = 'farmevo_proposal'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('azur', 'cyan'));

UPDATE llx_commande
SET model_pdf = 'farmevo_order'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('einstein', 'eratosthene'));

UPDATE llx_facture
SET model_pdf = 'farmevo_invoice'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('crabe', 'sponge'));

UPDATE llx_supplier_proposal
SET model_pdf = 'farmevo_supplier_proposal'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('aurore'));

UPDATE llx_commande_fournisseur
SET model_pdf = 'farmevo_supplier_order'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('cornas'));

UPDATE llx_facture_fourn
SET model_pdf = 'farmevo_supplier_invoice'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('canelle'));

UPDATE llx_expedition
SET model_pdf = 'farmevo_shipping'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('rouget', 'espadon'));

UPDATE llx_delivery
SET model_pdf = 'farmevo_delivery'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('storm'));

UPDATE llx_reception
SET model_pdf = 'farmevo_reception'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('squille'));

UPDATE llx_expensereport
SET model_pdf = 'farmevo_expensereport'
WHERE entity = @entity
	AND (model_pdf IS NULL OR model_pdf = '' OR model_pdf IN ('standard', 'standard_expensereport'));

COMMIT;

-- Optional verification.
SELECT rowid, nom, type, entity, libelle, description
FROM llx_document_model
WHERE entity = @entity AND nom LIKE 'farmevo%'
ORDER BY type, nom;

SELECT name, value
FROM llx_const
WHERE entity = @entity
	AND name IN (
		'PROPALE_ADDON_PDF',
		'COMMANDE_ADDON_PDF',
		'FACTURE_ADDON_PDF',
		'SUPPLIER_PROPOSAL_ADDON_PDF',
		'COMMANDE_SUPPLIER_ADDON_PDF',
		'INVOICE_SUPPLIER_ADDON_PDF',
		'EXPEDITION_ADDON_PDF',
		'DELIVERY_ADDON_PDF',
		'RECEPTION_ADDON_PDF',
		'EXPENSEREPORT_ADDON_PDF'
	)
ORDER BY name;
