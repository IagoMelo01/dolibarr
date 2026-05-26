<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/supplier_order/doc/pdf_farmevo_supplier_order.modules.php
 *	\ingroup    fournisseur
 *	\brief      Farmevo PDF model for supplier orders
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/doc/pdf_cornas.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo supplier order PDF model.
 */
class pdf_farmevo_supplier_order extends pdf_cornas
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

		$this->name = 'farmevo_supplier_order';
		$this->description = 'Farmevo - pedido de fornecedor moderno';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF              $pdf         PDF object
	 * @param CommandeFournisseur $object      Object to show
	 * @param int<0,1>           $showaddress Show sender/recipient blocks
	 * @param Translate          $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		$outputlangs->loadLangs(array("main", "orders", "companies", "bills", "sendings", "projects"));

		$displaydate = getDolGlobalString('MAIN_PDF_DATE_TEXT') ? 'daytext' : 'day';
		$deliveryFormat = getDolGlobalString('SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE') ? 'dayhour' : $displaydate;
		$meta = array();
		if (!empty($object->date_commande)) {
			$meta[] = array('label' => $outputlangs->transnoentities("Date"), 'value' => dol_print_date($object->date_commande, $displaydate, false, $outputlangs, true));
		}
		if (!empty($object->delivery_date)) {
			$meta[] = array('label' => 'Entrega', 'value' => dol_print_date($object->delivery_date, $deliveryFormat, false, $outputlangs, true));
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

		$pagehead = $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $outputlangs->transnoentities("SupplierOrder"),
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
		$this->farmevoTableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop, $hidebottom, $currency);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF              $pdf          PDF object
	 * @param CommandeFournisseur $object       Object to show
	 * @param Translate          $outputlangs  Output language
	 * @param int                $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'SUPPLIER_ORDER_FREE_TEXT', $hidefreetext);
	}
}
