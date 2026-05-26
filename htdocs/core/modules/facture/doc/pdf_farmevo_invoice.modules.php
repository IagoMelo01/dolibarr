<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/facture/doc/pdf_farmevo_invoice.modules.php
 *	\ingroup    invoice
 *	\brief      Farmevo PDF model for customer invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/pdf_sponge.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo invoice PDF model.
 */
class pdf_farmevo_invoice extends pdf_sponge
{
	use FarmevoPdfDesignTrait;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);

		$this->name = 'farmevo_invoice';
		$this->description = 'Farmevo - fatura moderna';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF      $pdf            PDF object
	 * @param Facture    $object         Object to show
	 * @param int<0,1>   $showaddress    0=no, 1=yes
	 * @param Translate  $outputlangs    Output language
	 * @param ?Translate $outputlangsbis Secondary output language
	 * @return array<string,float>
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$title = $outputlangs->transnoentities("PdfInvoiceTitle");
		if ($object->type == 1) {
			$title = $outputlangs->transnoentities("InvoiceReplacement");
		}
		if ($object->type == 2) {
			$title = $outputlangs->transnoentities("InvoiceAvoir");
		}
		if ($object->type == 3) {
			$title = $outputlangs->transnoentities("InvoiceDeposit");
		}
		if ($object->type == 4) {
			$title = $outputlangs->transnoentities("InvoiceProForma");
		}
		if ($this->situationinvoice) {
			$outputlangs->loadLangs(array("other"));
			$title = $outputlangs->transnoentities("PDFInvoiceSituation")." ".$outputlangs->transnoentities("NumberingShort").$object->situation_counter;
		}
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - ';
			if ($object->type == 0) {
				if ($this->situationinvoice) {
					$title .= $outputlangsbis->transnoentities("PDFInvoiceSituation");
				}
				$title .= $outputlangsbis->transnoentities("PdfInvoiceTitle");
			} elseif ($object->type == 1) {
				$title .= $outputlangsbis->transnoentities("InvoiceReplacement");
			} elseif ($object->type == 2) {
				$title .= $outputlangsbis->transnoentities("InvoiceAvoir");
			} elseif ($object->type == 3) {
				$title .= $outputlangsbis->transnoentities("InvoiceDeposit");
			} elseif ($object->type == 4) {
				$title .= $outputlangsbis->transnoentities("InvoiceProForma");
			}
		}

		$meta = array(
			array('label' => $outputlangs->transnoentities("DateInvoice"), 'value' => dol_print_date($object->date, "day", false, $outputlangs, true)),
		);
		if ($object->type != 2) {
			$meta[] = array('label' => $outputlangs->transnoentities("DateDue"), 'value' => dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true));
		}
		if ($object->ref_customer) {
			$meta[] = array('label' => $outputlangs->transnoentities("RefCustomer"), 'value' => $object->ref_customer);
		}
		if (getDolGlobalString('INVOICE_POINTOFTAX_DATE')) {
			$meta[] = array('label' => $outputlangs->transnoentities("DatePointOfTax"), 'value' => dol_print_date($object->date_pointoftax, "day", false, $outputlangs));
		}
		$objectidnext = $object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext) {
			$objectreplacing = new Facture($this->db);
			$objectreplacing->fetch($objectidnext);
			$meta[] = array('label' => $outputlangs->transnoentities("ReplacementByInvoice"), 'value' => $objectreplacing->ref);
		}
		if ($object->type == 1) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);
			$meta[] = array('label' => $outputlangs->transnoentities("ReplacementInvoice"), 'value' => $objectreplaced->ref);
		}
		if ($object->type == 2 && !empty($object->fk_facture_source)) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);
			$meta[] = array('label' => $outputlangs->transnoentities("CorrectionInvoice"), 'value' => $objectreplaced->ref);
		}
		$meta = $this->farmevoAddCommonMetaRows($object, $outputlangs, $meta);

		return $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $title,
			'ref' => $object->ref,
			'draft' => ($object->status == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'BILLING',
			'external_contact_type' => 'BILLING',
			'source_label' => $outputlangs->transnoentities("BillFrom"),
			'target_label' => $outputlangs->transnoentities("BillTo"),
			'show_shipping' => (bool) getDolGlobalInt('INVOICE_SHOW_SHIPPING_ADDRESS'),
			'blocked_log_signature' => true,
		));
	}

	/**
	 * Show table for lines.
	 *
	 * @param TCPDF        $pdf            PDF object
	 * @param float        $tab_top        Top position
	 * @param float        $tab_height     Height
	 * @param float        $nexY           Y
	 * @param Translate    $outputlangs    Output language
	 * @param int<0,1>     $hidetop        Hide top
	 * @param int<0,1>     $hidebottom     Hide bottom
	 * @param CommonObject|string $object  Object or empty string
	 * @param ?Translate   $outputlangsbis Secondary output language
	 * @return void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $object = '', $outputlangsbis = null)
	{
		$currency = (is_object($object) && !empty($object->multicurrency_code)) ? $object->multicurrency_code : '';
		$leftLabel = '';
		if (is_object($object) && getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
			$leftLabel = $outputlangs->transnoentities("MentionCategoryOfOperations").' : '.$outputlangs->transnoentities("MentionCategoryOfOperations".$this->categoryOfOperation);
		}

		$this->farmevoTableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop, $hidebottom, $currency, $outputlangsbis, $leftLabel);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF     $pdf                PDF object
	 * @param Facture   $object             Object to show
	 * @param Translate $outputlangs        Output language
	 * @param int       $hidefreetext       Hide free text
	 * @param int       $heightforqrinvoice Height for QR invoice
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0, $heightforqrinvoice = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'INVOICE_FREE_TEXT', $hidefreetext, $heightforqrinvoice);
	}
}
