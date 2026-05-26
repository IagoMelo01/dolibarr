<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/reception/doc/pdf_farmevo_reception.modules.php
 *	\ingroup    reception
 *	\brief      Farmevo PDF model for receptions
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/reception/doc/pdf_squille.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo reception PDF model.
 */
class pdf_farmevo_reception extends pdf_squille
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

		$this->name = 'farmevo_reception';
		$this->description = 'Farmevo - recebimento moderno';
		$this->corner_radius = 2;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF     $pdf         PDF object
	 * @param Reception $object      Object to show
	 * @param int<0,1>  $showaddress Show sender/recipient blocks
	 * @param Translate $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		$outputlangs->loadLangs(array("main", "orders", "companies", "receptions"));

		$meta = array();
		if (!empty($object->date_delivery)) {
			$meta[] = array('label' => 'Recebimento', 'value' => dol_print_date($object->date_delivery, 'day', false, $outputlangs, true));
		}
		if (!empty($object->thirdparty->code_fournisseur)) {
			$meta[] = array('label' => 'Cod. forn.', 'value' => $object->thirdparty->code_fournisseur);
		}

		$pagehead = $this->farmevoDrawHeader($pdf, $object, $showaddress, $outputlangs, array(
			'title' => $outputlangs->transnoentities("ReceptionSheet"),
			'ref' => $object->ref,
			'draft' => ($object->status == $object::STATUS_DRAFT),
			'meta' => $meta,
			'internal_contact_type' => 'SALESREPFOLL',
			'external_contact_type' => 'SHIPPING',
			'source_label' => $outputlangs->transnoentities("Sender"),
			'target_label' => $outputlangs->transnoentities("Supplier"),
			'show_shipping' => false,
		));

		return $pagehead['top_shift'] + $pagehead['shipp_shift'];
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF     $pdf          PDF object
	 * @param Reception $object       Object to show
	 * @param Translate $outputlangs  Output language
	 * @param int       $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'RECEPTION_FREE_TEXT', $hidefreetext);
	}
}
