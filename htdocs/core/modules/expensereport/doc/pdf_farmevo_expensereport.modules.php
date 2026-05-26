<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *	\file       htdocs/core/modules/expensereport/doc/pdf_farmevo_expensereport.modules.php
 *	\ingroup    expensereport
 *	\brief      Farmevo PDF model for expense reports
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/doc/pdf_standard_expensereport.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_pdf_design.lib.php';

/**
 * Farmevo expense report PDF model.
 */
class pdf_farmevo_expensereport extends pdf_standard_expensereport
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

		$this->name = 'farmevo_expensereport';
		$this->description = 'Farmevo - relatorio de despesas moderno';
		$this->corner_radius = 2;

		$this->posxpiece = $this->marge_gauche + 1;
		$this->posxcomment = $this->marge_gauche + 9;
		$this->posxtva = $this->marge_gauche + 92;
		$this->posxup = $this->marge_gauche + 107;
		$this->posxqty = $this->marge_gauche + 131;
		$this->postotalht = $this->marge_gauche + 146;
		$this->postotalttc = $this->marge_gauche + 170;
		$this->posxdate = $this->posxcomment;
		$this->posxtype = $this->posxcomment;
		$this->posxprojet = $this->posxtva;
	}

	/**
	 * Show top header of page.
	 *
	 * @param TCPDF         $pdf         PDF object
	 * @param ExpenseReport $object      Object to show
	 * @param int<0,1>      $showaddress Show sender/recipient blocks
	 * @param Translate     $outputlangs Output language
	 * @return float|int
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf;

		$outputlangs->loadLangs(array("main", "trips", "companies", "banks"));

		if ($object->fk_statut == 0 && getDolGlobalString('EXPENSEREPORT_DRAFT_WATERMARK')) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->EXPENSEREPORT_DRAFT_WATERMARK);
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$green = $this->farmevoColors['green'];
		$darkgreen = $this->farmevoColors['darkgreen'];
		$orange = $this->farmevoColors['orange'];
		$line = $this->farmevoColors['line'];
		$text = $this->farmevoColors['text'];
		$muted = $this->farmevoColors['muted'];

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetFillColor($orange[0], $orange[1], $orange[2]);
		$pdf->Rect(0, 0, $this->page_largeur, 4, 'F');
		$pdf->SetFillColor($green[0], $green[1], $green[2]);
		$pdf->Rect(0, 4, $this->page_largeur * 0.68, 1.2, 'F');
		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->Line($this->marge_gauche, 37, $this->page_largeur - $this->marge_droite, 37);

		$this->farmevoDrawLogo($pdf, $object, $this->marge_gauche, 9.5, 58, 24, $outputlangs);

		$titleWidth = 98;
		$titleX = $this->page_largeur - $this->marge_droite - $titleWidth;
		$titleY = 9.5;

		$pdf->SetTextColor($darkgreen[0], $darkgreen[1], $darkgreen[2]);
		$pdf->SetFont('', 'B', $default_font_size + 6);
		$pdf->SetXY($titleX, $titleY);
		$pdf->MultiCell($titleWidth, 7, dol_strtoupper($outputlangs->transnoentities("ExpenseReport")), 0, 'R');

		$refText = $outputlangs->convToOutputCharset($object->ref);
		if ($refText !== '') {
			$refWidth = min(72, max(28, $pdf->GetStringWidth($refText) + 10));
			$refX = $this->page_largeur - $this->marge_droite - $refWidth;
			$refY = $pdf->GetY() + 1;
			$pdf->RoundedRect($refX, $refY, $refWidth, 7, 2, '1234', 'F', array(), $green);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetXY($refX, $refY + 1.3);
			$pdf->MultiCell($refWidth, 4, $refText, 0, 'C');
			$pdf->SetY($refY + 8);
		}

		$statusLabel = '';
		if (isset($object->labelStatusShort[$object->status])) {
			$statusLabel = $outputlangs->transnoentities($object->labelStatusShort[$object->status]);
		}

		$meta = array(
			array('label' => $outputlangs->transnoentities("DateStart"), 'value' => ($object->date_debut > 0 ? dol_print_date($object->date_debut, "day", false, $outputlangs, true) : '')),
			array('label' => $outputlangs->transnoentities("DateEnd"), 'value' => ($object->date_fin > 0 ? dol_print_date($object->date_fin, "day", false, $outputlangs, true) : '')),
			array('label' => $outputlangs->transnoentities("Status"), 'value' => $statusLabel),
			array('label' => $outputlangs->transnoentities("Amount"), 'value' => price($object->total_ttc, 0, $outputlangs, 1, -1, -1, $conf->currency)),
		);

		$pdf->SetFont('', '', $default_font_size - 2);
		$metaY = max(27, $pdf->GetY() + 1);
		foreach ($meta as $row) {
			if (empty($row['value'])) {
				continue;
			}
			$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
			$pdf->SetXY($titleX, $metaY);
			$pdf->MultiCell(34, 3.4, $outputlangs->convToOutputCharset($row['label']), 0, 'R');
			$pdf->SetTextColor($text[0], $text[1], $text[2]);
			$pdf->SetXY($titleX + 36, $metaY);
			$pdf->MultiCell($titleWidth - 36, 3.4, $outputlangs->convToOutputCharset($row['value']), 0, 'R');
			$metaY += 3.9;
		}

		if ($showaddress) {
			$availableWidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$gap = 7;
			$leftWidth = ($availableWidth - $gap) * 0.46;
			$rightWidth = $availableWidth - $gap - $leftWidth;
			$leftX = $this->marge_gauche;
			$rightX = $leftX + $leftWidth + $gap;
			$addressY = 44;
			$cardHeight = 39;

			$senderAddress = $this->buildCompanyAddressForExpenseReport($outputlangs);
			$employee = $this->buildEmployeeBlockForExpenseReport($object, $outputlangs);

			$this->farmevoAddressCard($pdf, $leftX, $addressY, $leftWidth, $cardHeight, 'Empresa', $outputlangs->convToOutputCharset($this->emetteur->name), $senderAddress, $green, true, 'L');
			$this->farmevoAddressCard($pdf, $rightX, $addressY, $rightWidth, $cardHeight, 'Colaborador', $employee['name'], $employee['address'], $orange, false, 'L');

			$workflow = $this->buildWorkflowBlockForExpenseReport($object, $outputlangs);
			if ($workflow !== '') {
				$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($rightX + 3, $addressY + 27.5);
				$pdf->MultiCell($rightWidth - 6, 3.2, $workflow, 0, 'L');
			}
		}

		$pdf->SetTextColor(0, 0, 0);
		return 0;
	}

	/**
	 * Show table for lines.
	 *
	 * @param TCPDF     $pdf         PDF object
	 * @param float|int $tab_top     Table top
	 * @param float|int $tab_height  Table height
	 * @param float|int $nexY        Next Y
	 * @param Translate $outputlangs Output language
	 * @param int<-1,1> $hidetop     Hide top
	 * @param int<0,1>  $hidebottom  Hide bottom
	 * @param string    $currency    Currency code
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
		$green = $this->farmevoColors['green'];
		$darkgreen = $this->farmevoColors['darkgreen'];
		$line = $this->farmevoColors['line'];
		$muted = $this->farmevoColors['muted'];

		if (empty($hidetop)) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre, 0, 'R');
		}

		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, 2, '1234', 'D');

		$titleHeight = 6.2;
		if (empty($hidetop)) {
			$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $titleHeight, 2, '1001', 'F', array(), $darkgreen);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->drawExpenseReportTableTitle($pdf, $tab_top + 1.3, $outputlangs);
		}

		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->SetLineWidth(0.15);

		$separatorTop = empty($hidetop) ? $tab_top + $titleHeight : $tab_top;
		if (empty($hidetop)) {
			$pdf->Line($this->marge_gauche, $separatorTop, $this->page_largeur - $this->marge_droite, $separatorTop);
		}

		$columns = array($this->posxcomment - 1, $this->posxtva - 1, $this->posxup - 1, $this->posxqty - 1, $this->postotalht - 1, $this->postotalttc);
		foreach ($columns as $x) {
			$pdf->Line($x, $separatorTop, $x, $tab_top + $tab_height);
		}

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetDrawColor($green[0], $green[1], $green[2]);
	}

	/**
	 * Show footer of page.
	 *
	 * @param TCPDF         $pdf          PDF object
	 * @param ExpenseReport $object       Object to show
	 * @param Translate     $outputlangs  Output language
	 * @param int           $hidefreetext Hide free text
	 * @return int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		return $this->farmevoPageFoot($pdf, $object, $outputlangs, 'EXPENSEREPORT_FREE_TEXT', $hidefreetext);
	}

	/**
	 * Build company address text.
	 *
	 * @param Translate $outputlangs Output language
	 * @return string
	 */
	private function buildCompanyAddressForExpenseReport($outputlangs)
	{
		$address = '';
		if (!empty($this->emetteur->address)) {
			$address .= $outputlangs->convToOutputCharset($this->emetteur->address);
		}
		$cityLine = trim($outputlangs->convToOutputCharset($this->emetteur->zip).' '.$outputlangs->convToOutputCharset($this->emetteur->town));
		if ($cityLine !== '') {
			$address .= ($address ? "\n" : '').$cityLine;
		}
		if (!empty($this->emetteur->phone)) {
			$address .= ($address ? "\n" : '').$outputlangs->transnoentities("Phone")." : ".$outputlangs->convToOutputCharset($this->emetteur->phone);
		}
		if (!empty($this->emetteur->email)) {
			$address .= ($address ? "\n" : '').$outputlangs->transnoentities("Email")." : ".$outputlangs->convToOutputCharset($this->emetteur->email);
		}
		if (!empty($this->emetteur->url)) {
			$address .= ($address ? "\n" : '').$outputlangs->transnoentities("Web")." : ".$outputlangs->convToOutputCharset($this->emetteur->url);
		}

		return $address;
	}

	/**
	 * Build employee card data.
	 *
	 * @param ExpenseReport $object      Object to show
	 * @param Translate     $outputlangs Output language
	 * @return array{name:string,address:string}
	 */
	private function buildEmployeeBlockForExpenseReport($object, $outputlangs)
	{
		$employee = new User($this->db);
		if ($object->fk_user_author > 0) {
			$employee->fetch($object->fk_user_author);
		}

		$bankAccount = new UserBankAccount($this->db);
		$bankAccount->fetch(0, '', $object->fk_user_author);

		$name = dolGetFirstLastname($employee->firstname, $employee->lastname);
		if ($name === '') {
			$name = $employee->login;
		}

		$address = '';
		if (!empty($employee->address)) {
			$address .= $outputlangs->convToOutputCharset($employee->address);
		}
		$cityLine = trim($outputlangs->convToOutputCharset($employee->zip).' '.$outputlangs->convToOutputCharset($employee->town));
		if ($cityLine !== '') {
			$address .= ($address ? "\n" : '').$cityLine;
		}
		if (!empty($employee->email)) {
			$address .= ($address ? "\n" : '').$outputlangs->transnoentities("Email")." : ".$outputlangs->convToOutputCharset($employee->email);
		}
		if (!empty($bankAccount->iban)) {
			$address .= ($address ? "\n" : '').$outputlangs->transnoentities("IBAN")." : ".$outputlangs->convToOutputCharset($bankAccount->iban);
		}

		return array(
			'name' => $outputlangs->convToOutputCharset($name),
			'address' => $address,
		);
	}

	/**
	 * Build workflow text for the employee card.
	 *
	 * @param ExpenseReport $object      Object to show
	 * @param Translate     $outputlangs Output language
	 * @return string
	 */
	private function buildWorkflowBlockForExpenseReport($object, $outputlangs)
	{
		$rows = array();
		if (!empty($object->date_create)) {
			$rows[] = $outputlangs->transnoentities("DateCreation")." : ".dol_print_date($object->date_create, "day", false, $outputlangs);
		}
		if (!empty($object->date_approve)) {
			$rows[] = $outputlangs->transnoentities("DateApprove")." : ".dol_print_date($object->date_approve, "day", false, $outputlangs);
		}
		if (!empty($object->date_paiement)) {
			$rows[] = $outputlangs->transnoentities("DATE_PAIEMENT")." : ".dol_print_date($object->date_paiement, "day", false, $outputlangs);
		}

		return implode("\n", $rows);
	}

	/**
	 * Draw custom table title row.
	 *
	 * @param TCPDF     $pdf         PDF object
	 * @param float     $y           Y position
	 * @param Translate $outputlangs Output language
	 * @return void
	 */
	private function drawExpenseReportTableTitle(&$pdf, $y, $outputlangs)
	{
		$pdf->SetXY($this->posxpiece - 1, $y);
		$pdf->MultiCell($this->posxcomment - $this->posxpiece - 0.8, 3, '#', 0, 'C');

		$pdf->SetXY($this->posxcomment, $y);
		$pdf->MultiCell($this->posxtva - $this->posxcomment - 1, 3, $outputlangs->transnoentities("Description"), 0, 'L');

		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
			$pdf->SetXY($this->posxtva, $y);
			$pdf->MultiCell($this->posxup - $this->posxtva - 1, 3, $outputlangs->transnoentities("VAT"), 0, 'C');
		}

		$pdf->SetXY($this->posxup, $y);
		$pdf->MultiCell($this->posxqty - $this->posxup - 1, 3, 'Preço Un.', 0, 'C');

		$pdf->SetXY($this->posxqty, $y);
		$pdf->MultiCell($this->postotalht - $this->posxqty - 1, 3, 'Qtd.', 0, 'C');

		$pdf->SetXY($this->postotalht, $y);
		$pdf->MultiCell($this->postotalttc - $this->postotalht - 1, 3, 'Total liq.', 0, 'C');

		$pdf->SetXY($this->postotalttc + 1, $y);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalttc - 1, 3, 'Total', 0, 'R');
	}
}
