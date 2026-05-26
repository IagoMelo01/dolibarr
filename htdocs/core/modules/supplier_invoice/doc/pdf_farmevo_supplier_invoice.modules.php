<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/supplier_invoice/doc/pdf_farmevo_supplier_invoice.modules.php
 *	\ingroup    fournisseur
 *	\brief      Farmevo PDF model for supplier invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/doc/pdf_canelle.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo supplier invoice PDF model.
 */
class pdf_farmevo_supplier_invoice extends pdf_canelle
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

		$this->name = 'farmevo_supplier_invoice';
		$this->description = 'Farmevo - fatura de fornecedor moderna';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF              $pdf         PDF object
	 * @param FactureFournisseur $object      Object to show
	 * @param int<0,1>           $showaddress Show sender/recipient blocks
	 * @param Translate          $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $mysoc;

		$outputlangs->loadLangs(array("main", "orders", "companies", "bills", "projects"));

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

		$displaydate = getDolGlobalString('MAIN_PDF_DATE_TEXT') ? 'daytext' : 'day';
		$meta = array();
		if (!empty($object->date)) {
			$meta[] = array('label' => $outputlangs->transnoentities("Date"), 'value' => dol_print_date($object->date, $displaydate, false, $outputlangs, true));
		}
		if (!empty($object->date_echeance)) {
			$meta[] = array('label' => 'Vencimento', 'value' => dol_print_date($object->date_echeance, $displaydate, false, $outputlangs, true));
		}
		if (!empty($object->ref_supplier)) {
			$meta[] = array('label' => 'Ref. forn.', 'value' => $object->ref_supplier);
		}
		if (!empty($object->thirdparty->code_fournisseur)) {
			$meta[] = array('label' => 'Cod. forn.', 'value' => $object->thirdparty->code_fournisseur);
		}
		if (getDolGlobalString('PDF_SHOW_PROJECT_TITLE')) {
			$object->fetchProject();
			if (!empty($object->project->ref)) {
				$meta[] = array('label' => $outputlangs->transnoentities("Project"), 'value' => empty($object->project->title) ? '' : $object->project->title);
			}
		}
		if (getDolGlobalString('PDF_SHOW_PROJECT')) {
			$object->fetchProject();
			if (!empty($object->project->ref)) {
				$meta[] = array('label' => $outputlangs->transnoentities("RefProject"), 'value' => empty($object->project->ref) ? '' : $object->project->ref);
			}
		}

		$pagehead = $this->farmevoDrawHeader($pdf, $object, 0, $outputlangs, array(
			'title' => $title,
			'ref' => $object->ref,
			'draft' => ($object->status == $object::STATUS_DRAFT),
			'meta' => $meta,
		));

		if ($showaddress) {
			$availableWidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$gap = 7;
			$leftWidth = ($availableWidth - $gap) * 0.46;
			$rightWidth = $availableWidth - $gap - $leftWidth;
			$leftX = $this->marge_gauche;
			$rightX = $leftX + $leftWidth + $gap;
			$addressY = 44 + $pagehead['top_shift'];
			$cardHeight = 39;
			$green = $this->farmevoColors['green'];
			$orange = $this->farmevoColors['orange'];
			$direction = ($outputlangs->trans("DIRECTION") == 'rtl') ? 'R' : 'L';

			$supplierName = pdfBuildThirdpartyName($object->thirdparty, $outputlangs);
			$supplierAddress = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);
			$myCompanyName = pdfBuildThirdpartyName($mysoc, $outputlangs);
			$myCompanyAddress = pdf_build_address($outputlangs, $this->emetteur, $mysoc, '', 0, 'target', $object);

			$this->farmevoAddressCard($pdf, $leftX, $addressY, $leftWidth, $cardHeight, $outputlangs->transnoentities("BillFrom"), $supplierName, $supplierAddress, $green, true, $direction);
			$this->farmevoAddressCard($pdf, $rightX, $addressY, $rightWidth, $cardHeight, $outputlangs->transnoentities("BillTo"), $myCompanyName, $myCompanyAddress, $orange, false, $direction);
		}

		return $pagehead['top_shift'];
	}

	/**
	 * Show table for lines.
	 *
	 * @param TCPDF      $pdf         PDF object
	 * @param float|int  $tab_top     Top position
	 * @param float|int  $tab_height  Height
	 * @param int        $nexY        Y
	 * @param Translate  $outputlangs Output language
	 * @param int<-1,1>  $hidetop     Hide top
	 * @param int<0,1>   $hidebottom  Hide bottom
	 * @param string     $currency    Currency
	 * @return void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$darkgreen = $this->farmevoColors['darkgreen'];
		$line = $this->farmevoColors['line'];
		$muted = $this->farmevoColors['muted'];
		$rightEdge = $this->page_largeur - $this->marge_droite;
		$titleHeight = 6.2;
		$showVat = (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN'));

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($rightEdge - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre, 0, 'R');
		}

		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->SetFont('', '', $default_font_size - 1);
		$this->printRoundedRect($pdf, $this->marge_gauche, $tab_top, $rightEdge - $this->marge_gauche, $tab_height, 2, $hidetop, $hidebottom, 'D');

		if (empty($hidetop)) {
			$pdf->RoundedRect($this->marge_gauche, $tab_top, $rightEdge - $this->marge_gauche, $titleHeight, 2, '1001', 'F', array(), $darkgreen);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('', 'B', $default_font_size - 1);

			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1.2);
			$pdf->MultiCell(($showVat ? $this->posxtva : $this->posxup) - $this->posxdesc, 2, $outputlangs->transnoentities("Designation"), '', 'L');

			if ($showVat) {
				$pdf->SetXY($this->posxtva - 3, $tab_top + 1.2);
				$pdf->MultiCell($this->posxup - $this->posxtva + 3, 2, $outputlangs->transnoentities("VAT"), '', 'C');
			}

			$pdf->SetXY($this->posxup - 1, $tab_top + 1.2);
			$pdf->MultiCell($this->posxqty - $this->posxup - 1, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');

			$pdf->SetXY($this->posxqty - 1, $tab_top + 1.2);
			$pdf->MultiCell($this->posxunit - $this->posxqty - 1, 2, $outputlangs->transnoentities("Qty"), '', 'C');

			if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
				$pdf->SetXY($this->posxunit - 1, $tab_top + 1.2);
				$pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '', 'C');
			}

			if ($this->atleastonediscount) {
				$pdf->SetXY($this->posxdiscount - 1, $tab_top + 1.2);
				$pdf->MultiCell($this->postotalht - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
			}

			$pdf->SetXY($this->postotalht - 1, $tab_top + 1.2);
			$pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalHTShort"), '', 'C');

			$pdf->SetDrawColor($line[0], $line[1], $line[2]);
			$pdf->Line($this->marge_gauche, $tab_top + $titleHeight, $rightEdge, $tab_top + $titleHeight);
		}

		$bodyTop = empty($hidetop) ? $tab_top + $titleHeight : $tab_top;
		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		if ($showVat) {
			$pdf->Line($this->posxtva - 1, $bodyTop, $this->posxtva - 1, $tab_top + $tab_height);
		}
		$pdf->Line($this->posxup - 1, $bodyTop, $this->posxup - 1, $tab_top + $tab_height);
		$pdf->Line($this->posxqty - 1, $bodyTop, $this->posxqty - 1, $tab_top + $tab_height);
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$pdf->Line($this->posxunit - 1, $bodyTop, $this->posxunit - 1, $tab_top + $tab_height);
		}
		if ($this->atleastonediscount) {
			$pdf->Line($this->posxdiscount - 1, $bodyTop, $this->posxdiscount - 1, $tab_top + $tab_height);
		}
		$pdf->Line($this->postotalht, $bodyTop, $this->postotalht, $tab_top + $tab_height);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF              $pdf          PDF object
	 * @param FactureFournisseur $object       Object to show
	 * @param Translate          $outputlangs  Output language
	 * @param int                $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'SUPPLIER_INVOICE_FREE_TEXT', $hidefreetext);
	}
}
