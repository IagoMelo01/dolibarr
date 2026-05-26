<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/supplier_proposal/doc/pdf_farmevo_supplier_proposal.modules.php
 *	\ingroup    supplier_proposal
 *	\brief      Farmevo PDF model for supplier proposals
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/doc/pdf_aurore.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo supplier proposal PDF model.
 */
class pdf_farmevo_supplier_proposal extends pdf_aurore
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

		$this->name = 'farmevo_supplier_proposal';
		$this->description = 'Farmevo - proposta de fornecedor moderna';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF          $pdf         PDF object
	 * @param SupplierProposal $object      Object to show
	 * @param int<0,1>       $showaddress Show sender/recipient blocks
	 * @param Translate      $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		$outputlangs->loadLangs(array("main", "bills", "supplier_proposal", "companies", "projects"));

		$displaydate = getDolGlobalString('MAIN_PDF_DATE_TEXT') ? 'daytext' : 'day';
		$refSupplier = !empty($object->ref_supplier) ? $object->ref_supplier : (!empty($object->ref_fourn) ? $object->ref_fourn : '');
		$meta = array();
		if (!empty($object->date)) {
			$meta[] = array('label' => $outputlangs->transnoentities("Date"), 'value' => dol_print_date($object->date, $displaydate, false, $outputlangs, true));
		}
		if ($refSupplier) {
			$meta[] = array('label' => 'Ref. forn.', 'value' => $refSupplier);
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

		$pagehead = $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $outputlangs->transnoentities("CommercialAsk"),
			'ref' => $object->ref,
			'draft' => ($object->status == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'SALESREPFOLL',
			'external_contact_type' => 'CUSTOMER',
			'source_label' => $outputlangs->transnoentities("BillFrom"),
			'target_label' => $outputlangs->transnoentities("BillTo"),
			'show_shipping' => false,
		));

		return $pagehead['top_shift'] + $pagehead['shipp_shift'];
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
			$pdf->MultiCell($this->posxup - $this->posxdesc, 2, $outputlangs->transnoentities("Designation"), '', 'L');

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
		$pdf->Line($this->posxup + 1, $bodyTop, $this->posxup + 1, $tab_top + $tab_height);
		$pdf->Line($this->posxqty - 1, $bodyTop, $this->posxqty - 1, $tab_top + $tab_height);
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$pdf->Line($this->posxunit - 1, $bodyTop, $this->posxunit - 1, $tab_top + $tab_height);
		}
		$pdf->Line($this->posxdiscount - 1, $bodyTop, $this->posxdiscount - 1, $tab_top + $tab_height);
		if ($this->atleastonediscount) {
			$pdf->Line($this->postotalht, $bodyTop, $this->postotalht, $tab_top + $tab_height);
		}

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF            $pdf          PDF object
	 * @param SupplierProposal $object       Object to show
	 * @param Translate        $outputlangs  Output language
	 * @param int              $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'SUPPLIER_PROPOSAL_FREE_TEXT', $hidefreetext);
	}
}
