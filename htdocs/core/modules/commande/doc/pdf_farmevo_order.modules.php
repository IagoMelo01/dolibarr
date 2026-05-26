<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/commande/doc/pdf_farmevo_order.modules.php
 *	\ingroup    order
 *	\brief      Farmevo PDF model for customer orders
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/doc/pdf_eratosthene.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo order PDF model.
 */
class pdf_farmevo_order extends pdf_eratosthene
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

		$this->name = 'farmevo_order';
		$this->description = 'Farmevo - pedido moderno';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF      $pdf            PDF object
	 * @param Commande   $object         Object to show
	 * @param int<0,1>   $showaddress    0=no, 1=yes
	 * @param Translate  $outputlangs    Output language
	 * @param ?Translate $outputlangsbis Secondary output language
	 * @param string     $titlekey       Translation key for title
	 * @return array<string,float>
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null, $titlekey = "PdfOrderTitle")
	{
		$outputlangs->loadLangs(array("main", "bills", "propal", "orders", "companies"));

		$title = $outputlangs->transnoentities($titlekey);
		if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities($titlekey);
		}

		$meta = array(
			array('label' => $outputlangs->transnoentities("OrderDate"), 'value' => dol_print_date($object->date, "day", false, $outputlangs, true)),
		);
		if ($object->ref_client) {
			$meta[] = array('label' => $outputlangs->transnoentities("RefCustomer"), 'value' => $object->ref_client);
		}
		$meta = $this->farmevoAddCommonMetaRows($object, $outputlangs, $meta);

		return $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $title,
			'ref' => $object->ref,
			'draft' => ($object->statut == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'SALESREPFOLL',
			'external_contact_type' => 'CUSTOMER',
			'source_label' => $outputlangs->transnoentities("BillFrom"),
			'target_label' => $outputlangs->transnoentities("BillTo"),
			'show_shipping' => (bool) getDolGlobalInt('SALES_ORDER_SHOW_SHIPPING_ADDRESS'),
		));
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
	 * @param Commande  $object       Object to show
	 * @param Translate $outputlangs  Output language
	 * @param int       $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'ORDER_FREE_TEXT', $hidefreetext);
	}
}
