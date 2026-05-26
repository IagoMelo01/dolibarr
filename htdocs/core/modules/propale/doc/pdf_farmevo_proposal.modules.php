<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/propale/doc/pdf_farmevo_proposal.modules.php
 *	\ingroup    propale
 *	\brief      Farmevo PDF model for commercial proposals
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/doc/pdf_cyan.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo proposal PDF model.
 */
class pdf_farmevo_proposal extends pdf_cyan
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

		$this->name = 'farmevo_proposal';
		$this->description = 'Farmevo - proposta moderna';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF      $pdf            PDF object
	 * @param Propal     $object         Object to show
	 * @param int<0,1>   $showaddress    0=no, 1=yes
	 * @param Translate  $outputlangs    Output language
	 * @param ?Translate $outputlangsbis Secondary output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

		$displaydate = getDolGlobalString('MAIN_PDF_DATE_TEXT') ? 'daytext' : 'day';
		$refCustomer = $object->ref_customer ?: $object->ref_client;
		$meta = array(
			array('label' => $outputlangs->transnoentities("Date"), 'value' => dol_print_date($object->date, $displaydate, false, $outputlangs, true)),
			array('label' => $outputlangs->transnoentities("DateEndPropal"), 'value' => dol_print_date($object->fin_validite, $displaydate, false, $outputlangs, true)),
		);
		if ($refCustomer) {
			$meta[] = array('label' => $outputlangs->transnoentities("RefCustomer"), 'value' => $refCustomer);
		}
		$meta = $this->farmevoAddCommonMetaRows($object, $outputlangs, $meta);

		$pagehead = $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $outputlangs->transnoentities("PdfCommercialProposalTitle"),
			'ref' => $object->ref,
			'draft' => ($object->status == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'SALESREPFOLL',
			'external_contact_type' => 'CUSTOMER',
			'source_label' => $outputlangs->transnoentities("BillFrom"),
			'target_label' => $outputlangs->transnoentities("BillTo"),
			'show_shipping' => (bool) getDolGlobalInt('PROPOSAL_SHOW_SHIPPING_ADDRESS'),
		));

		return $pagehead['top_shift'] + $pagehead['shipp_shift'];
	}

	/**
	 * Show table for lines.
	 *
	 * @param TCPDF      $pdf            PDF object
	 * @param float|int  $tab_top        Top position
	 * @param float|int  $tab_height     Height
	 * @param int        $nexY           Y
	 * @param Translate  $outputlangs    Output language
	 * @param int<-1,1>  $hidetop        Hide top
	 * @param int<0,1>   $hidebottom     Hide bottom
	 * @param string     $currency       Currency
	 * @param ?Translate $outputlangsbis Secondary output language
	 * @return void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
	{
		$this->farmevoTableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop, $hidebottom, $currency, $outputlangsbis);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF     $pdf          PDF object
	 * @param Propal    $object       Object to show
	 * @param Translate $outputlangs  Output language
	 * @param int       $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'PROPOSAL_FREE_TEXT', $hidefreetext);
	}
}
