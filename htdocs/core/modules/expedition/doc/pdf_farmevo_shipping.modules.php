<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/expedition/doc/pdf_farmevo_shipping.modules.php
 *	\ingroup    expedition
 *	\brief      Farmevo PDF model for shipments
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/doc/pdf_espadon.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo shipment PDF model.
 */
class pdf_farmevo_shipping extends pdf_espadon
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

		$this->name = 'farmevo_shipping';
		$this->description = 'Farmevo - remessa moderna';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF      $pdf         PDF object
	 * @param Expedition $object      Object to show
	 * @param int<0,1>   $showaddress Show sender/recipient blocks
	 * @param Translate  $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		$outputlangs->loadLangs(array("main", "orders", "companies", "sendings", "projects"));

		$meta = array();
		if (!empty($object->date_delivery)) {
			$meta[] = array('label' => 'Entrega', 'value' => dol_print_date($object->date_delivery, 'day', false, $outputlangs, true));
		}
		$meta = $this->farmevoAddCommonMetaRows($object, $outputlangs, $meta);

		$pagehead = $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $outputlangs->transnoentities("SendingSheet"),
			'ref' => $object->ref,
			'draft' => ($object->statut == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'SALESREPFOLL',
			'external_contact_type' => 'SHIPPING',
			'source_label' => $outputlangs->transnoentities("Sender"),
			'target_label' => $outputlangs->transnoentities("Recipient"),
			'show_shipping' => false,
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
	 * @param TCPDF      $pdf          PDF object
	 * @param Expedition $object       Object to show
	 * @param Translate  $outputlangs  Output language
	 * @param int        $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'SHIPPING_FREE_TEXT', $hidefreetext);
	}
}
