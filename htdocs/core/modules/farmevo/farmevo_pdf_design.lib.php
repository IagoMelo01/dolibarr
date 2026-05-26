<?php
/* Copyright (C) 2026 Farmevo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Shared visual helpers for Farmevo PDF document models.
 *
 * @file htdocs/core/modules/farmevo/farmevo_pdf_design.lib.php
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Modern Farmevo PDF design helpers.
 */
trait FarmevoPdfDesignTrait
{
	/**
	 * Main brand colors.
	 *
	 * @var array<string, array<int, int>>
	 */
	protected $farmevoColors = array(
		'green' => array(45, 147, 49),
		'darkgreen' => array(22, 74, 32),
		'orange' => array(247, 126, 28),
		'softgreen' => array(245, 250, 245),
		'line' => array(214, 229, 213),
		'text' => array(34, 45, 38),
		'muted' => array(92, 108, 96),
	);

	/**
	 * Draw a modern Farmevo header.
	 *
	 * @param TCPDF          $pdf         PDF object
	 * @param CommonObject   $object      Source object
	 * @param int<0,1>       $showaddress Show sender/recipient blocks
	 * @param Translate      $outputlangs Output language
	 * @param array<string,mixed> $options Header options
	 * @return array{top_shift: float, shipp_shift: float}
	 */
	protected function farmevoDrawHeader(&$pdf, $object, $showaddress, $outputlangs, array $options)
	{
		$ltrdirection = ($outputlangs->trans("DIRECTION") == 'rtl') ? 'R' : 'L';
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$green = $this->farmevoColors['green'];
		$darkgreen = $this->farmevoColors['darkgreen'];
		$orange = $this->farmevoColors['orange'];
		$line = $this->farmevoColors['line'];
		$text = $this->farmevoColors['text'];
		$muted = $this->farmevoColors['muted'];

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Brand accents.
		$pdf->SetFillColor($orange[0], $orange[1], $orange[2]);
		$pdf->Rect(0, 0, $this->page_largeur, 4, 'F');
		$pdf->SetFillColor($green[0], $green[1], $green[2]);
		$pdf->Rect(0, 4, $this->page_largeur * 0.68, 1.2, 'F');
		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->Line($this->marge_gauche, 37, $this->page_largeur - $this->marge_droite, 37);

		$this->farmevoDrawLogo($pdf, $object, $this->marge_gauche, 9.5, 58, 24, $outputlangs);

		$title = !empty($options['title']) ? (string) $options['title'] : '';
		$ref = !empty($options['ref']) ? (string) $options['ref'] : '';
		$isDraft = !empty($options['draft']);
		$meta = !empty($options['meta']) && is_array($options['meta']) ? $options['meta'] : array();

		$titleWidth = 98;
		$titleX = $this->page_largeur - $this->marge_droite - $titleWidth;
		$titleY = 9.5;

		$pdf->SetTextColor($darkgreen[0], $darkgreen[1], $darkgreen[2]);
		$pdf->SetFont('', 'B', $default_font_size + 6);
		$pdf->SetXY($titleX, $titleY);
		$pdf->MultiCell($titleWidth, 7, dol_strtoupper($outputlangs->convToOutputCharset($title)), 0, 'R');

		if ($ref !== '') {
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$refText = $outputlangs->convToOutputCharset($ref);
			$refWidth = min(72, max(28, $pdf->GetStringWidth($refText) + 10));
			$refX = $this->page_largeur - $this->marge_droite - $refWidth;
			$refY = $pdf->GetY() + 1;
			$pdf->RoundedRect($refX, $refY, $refWidth, 7, 2, '1234', 'F', array(), $green);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetXY($refX, $refY + 1.3);
			$pdf->MultiCell($refWidth, 4, $refText, 0, 'C');
			$pdf->SetY($refY + 8);
		}

		if ($isDraft) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetTextColor($orange[0], $orange[1], $orange[2]);
			$pdf->SetXY($titleX, $pdf->GetY() + 0.5);
			$pdf->MultiCell($titleWidth, 3, $outputlangs->transnoentities("NotValidated"), 0, 'R');
		}

		$pdf->SetFont('', '', $default_font_size - 2);
		$metaY = max(28, $pdf->GetY() + 1);
		foreach ($meta as $row) {
			if (empty($row['value'])) {
				continue;
			}
			$label = !empty($row['label']) ? (string) $row['label'] : '';
			$value = (string) $row['value'];

			$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
			$pdf->SetXY($titleX, $metaY);
			$pdf->MultiCell(34, 3.4, $outputlangs->convToOutputCharset($label), 0, 'R');
			$pdf->SetTextColor($text[0], $text[1], $text[2]);
			$pdf->SetXY($titleX + 36, $metaY);
			$pdf->MultiCell($titleWidth - 36, 3.4, dol_trunc($outputlangs->convToOutputCharset($value), 62), 0, 'R');
			$metaY += 3.9;
		}

		if (!empty($options['blocked_log_signature'])) {
			pdfWriteBlockedLogSignature($pdf, $outputlangs, $this->page_hauteur, $object, $titleWidth, $titleX, $titleY);
		}

		$top_shift = max(0, $metaY - 42);
		$shipp_shift = 0;
		$addressY = 44 + $top_shift;

		if ($showaddress) {
			$availableWidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$gap = 7;
			$leftWidth = ($availableWidth - $gap) * 0.46;
			$rightWidth = $availableWidth - $gap - $leftWidth;
			$leftX = $this->marge_gauche;
			$rightX = $leftX + $leftWidth + $gap;
			$cardHeight = 39;

			$sender = $this->farmevoBuildSenderBlock($object, $outputlangs, (string) ($options['internal_contact_type'] ?? ''));
			$recipient = $this->farmevoBuildRecipientBlock($object, $outputlangs, (string) ($options['external_contact_type'] ?? 'CUSTOMER'));

			$this->farmevoAddressCard($pdf, $leftX, $addressY, $leftWidth, $cardHeight, (string) ($options['source_label'] ?? $outputlangs->transnoentities("BillFrom")), $sender['name'], $sender['address'], $green, true, $ltrdirection);
			$this->farmevoAddressCard($pdf, $rightX, $addressY, $rightWidth, $cardHeight, (string) ($options['target_label'] ?? $outputlangs->transnoentities("BillTo")), $recipient['name'], $recipient['address'], $orange, false, $ltrdirection);

			if (!empty($options['show_shipping'])) {
				$shipping = $this->farmevoBuildShippingBlock($object, $outputlangs);
				if (!empty($shipping['address'])) {
					$shipY = $addressY + $cardHeight + 6;
					$this->farmevoAddressCard($pdf, $rightX, $shipY, $rightWidth, 28, $outputlangs->transnoentities('ShippingTo'), $shipping['name'], $shipping['address'], $green, false, $ltrdirection);
					$shipp_shift = 34;
				}
			}
		}

		$pdf->SetTextColor(0, 0, 0);
		return array('top_shift' => $top_shift, 'shipp_shift' => $shipp_shift);
	}

	/**
	 * Draw the document lines table shell.
	 *
	 * @param TCPDF      $pdf            PDF object
	 * @param float      $tab_top        Top position
	 * @param float      $tab_height     Height
	 * @param float      $nexY           Next Y
	 * @param Translate  $outputlangs    Output language
	 * @param int<-1,1>  $hidetop        Hide top
	 * @param int<0,1>   $hidebottom     Hide bottom
	 * @param string     $currency       Currency
	 * @param ?Translate $outputlangsbis Secondary output language
	 * @param string     $leftLabel      Optional left label
	 * @return void
	 */
	protected function farmevoTableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null, $leftLabel = '')
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

		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
		if (empty($hidetop)) {
			if ($leftLabel !== '') {
				$pdf->SetXY($this->marge_gauche, $tab_top - 4);
				$pdf->MultiCell(95, 2, $leftLabel, 0, 'L');
			}

			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
				$titre .= ' - '.$outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency".$currency));
			}
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre, 0, 'R');
		}

		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$this->printRoundedRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, 2, $hidetop, $hidebottom, 'D');

		if (empty($hidetop)) {
			if (!empty($this->cols) && is_array($this->cols)) {
				if (isset($this->cols['totalexcltax']['title'])) {
					$this->cols['totalexcltax']['title']['label'] = $outputlangs->transnoentities("TotalHTShort");
				}
				if (isset($this->cols['totalincltax']['title'])) {
					$this->cols['totalincltax']['title']['label'] = $outputlangs->transnoentities("TotalTTC");
				}
				if (isset($this->cols['subprice']['title'])) {
					$this->cols['subprice']['title']['label'] = $outputlangs->transnoentities("PriceUHT");
				}
			}

			$titleHeight = max(6.2, (float) $this->tabTitleHeight);
			$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $titleHeight, 2, '1001', 'F', array(), $darkgreen);
			$pdf->SetDrawColor(255, 255, 255);
			$pdf->SetTextColor(255, 255, 255);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$this->pdfTabTitles($pdf, $tab_top + 0.7, $tab_height, $outputlangs, $hidetop);
			$pdf->SetDrawColor($line[0], $line[1], $line[2]);
			$pdf->Line($this->marge_gauche, $tab_top + $titleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $titleHeight);
		}

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
	}

	/**
	 * Draw a colored accent on top of the standard Dolibarr footer.
	 *
	 * @param TCPDF        $pdf              PDF object
	 * @param CommonObject $object           Source object
	 * @param Translate    $outputlangs      Output language
	 * @param string       $freeTextConstant Free text constant
	 * @param int<0,1>     $hidefreetext     Hide free text
	 * @param float        $extraBottom      Extra bottom space
	 * @return int
	 */
	protected function farmevoPageFoot(&$pdf, $object, $outputlangs, $freeTextConstant, $hidefreetext = 0, $extraBottom = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		$height = pdf_pagefoot($pdf, $outputlangs, $freeTextConstant, $this->emetteur, $extraBottom + $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);

		$green = $this->farmevoColors['green'];
		$orange = $this->farmevoColors['orange'];
		$lineY = $this->page_hauteur - $height + 1.2;
		if ($lineY > $this->page_hauteur - 36 && $lineY < $this->page_hauteur - 5) {
			$pdf->SetDrawColor($orange[0], $orange[1], $orange[2]);
			$pdf->SetLineWidth(0.7);
			$pdf->Line($this->marge_gauche, $lineY, $this->marge_gauche + 35, $lineY);
			$pdf->SetDrawColor($green[0], $green[1], $green[2]);
			$pdf->Line($this->marge_gauche + 37, $lineY, $this->page_largeur - $this->marge_droite, $lineY);
			$pdf->SetLineWidth(0.2);
		}

		return $height;
	}

	/**
	 * Add common meta rows for project and customer code.
	 *
	 * @param CommonObject $object      Source object
	 * @param Translate    $outputlangs Output language
	 * @param array<int,array{label:string,value:string}> $meta Existing rows
	 * @return array<int,array{label:string,value:string}>
	 */
	protected function farmevoAddCommonMetaRows($object, $outputlangs, array $meta)
	{
		if (getDolGlobalString('PDF_SHOW_PROJECT_TITLE')) {
			$object->fetchProject();
			if (!empty($object->project->ref)) {
				$meta[] = array('label' => $outputlangs->transnoentities("Project"), 'value' => empty($object->project->title) ? '' : $object->project->title);
			}
		}
		if (getDolGlobalString('PDF_SHOW_PROJECT')) {
			$object->fetchProject();
			if (!empty($object->project->ref)) {
				$outputlangs->load("projects");
				$meta[] = array('label' => $outputlangs->transnoentities("RefProject"), 'value' => empty($object->project->ref) ? '' : $object->project->ref);
			}
		}
		if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && !empty($object->thirdparty->code_client)) {
			$meta[] = array('label' => $outputlangs->transnoentities("CustomerCode"), 'value' => $object->thirdparty->code_client);
		}
		if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_ACCOUNTING_CODE') && !empty($object->thirdparty->code_compta_client)) {
			$meta[] = array('label' => $outputlangs->transnoentities("CustomerAccountancyCode"), 'value' => $object->thirdparty->code_compta_client);
		}

		return $meta;
	}

	/**
	 * Build sender name and address.
	 *
	 * @param CommonObject $object              Source object
	 * @param Translate    $outputlangs         Output language
	 * @param string       $internalContactType Internal contact type
	 * @return array{name:string,address:string}
	 */
	protected function farmevoBuildSenderBlock($object, $outputlangs, $internalContactType)
	{
		$carac_emetteur = '';
		if ($internalContactType !== '') {
			$arrayidcontact = $object->getIdContact('internal', $internalContactType);
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
				$carac_emetteur .= $labelbeforecontactname." ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') || getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ' (' : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') && !empty($object->user->office_phone)) ? $object->user->office_phone : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') && getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ', ' : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT') && !empty($object->user->email)) ? $object->user->email : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') || getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ')' : '';
				$carac_emetteur .= "\n";
			}
		}
		$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

		return array(
			'name' => $this->emetteur->name,
			'address' => $carac_emetteur,
		);
	}

	/**
	 * Build recipient name and address.
	 *
	 * @param CommonObject $object              Source object
	 * @param Translate    $outputlangs         Output language
	 * @param string       $externalContactType External contact type
	 * @return array{name:string,address:string}
	 */
	protected function farmevoBuildRecipientBlock($object, $outputlangs, $externalContactType)
	{
		global $conf;

		$usecontact = false;
		$arrayidcontact = $object->getIdContact('external', $externalContactType);
		if (count($arrayidcontact) > 0) {
			$usecontact = true;
			$object->fetch_contact($arrayidcontact[0]);
		}

		if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT')))) {
			$thirdparty = $object->contact;
		} else {
			$thirdparty = $object->thirdparty;
		}

		return array(
			'name' => is_object($thirdparty) ? pdfBuildThirdpartyName($thirdparty, $outputlangs) : '',
			'address' => pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), ($usecontact ? 1 : 0), 'target', $object),
		);
	}

	/**
	 * Build shipping name and address.
	 *
	 * @param CommonObject $object      Source object
	 * @param Translate    $outputlangs Output language
	 * @return array{name:string,address:string}
	 */
	protected function farmevoBuildShippingBlock($object, $outputlangs)
	{
		$idaddressshipping = $object->getIdContact('external', 'SHIPPING');
		if (!empty($idaddressshipping)) {
			$object->fetch_contact($idaddressshipping[0]);
			$companystatic = new Societe($this->db);
			$companystatic->fetch($object->contact->fk_soc);

			return array(
				'name' => pdfBuildThirdpartyName($object->contact, $outputlangs),
				'address' => pdf_build_address($outputlangs, $this->emetteur, $companystatic, $object->contact, 1, 'target', $object),
			);
		}

		return array(
			'name' => pdfBuildThirdpartyName($object->thirdparty, $outputlangs),
			'address' => pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'target', $object),
		);
	}

	/**
	 * Draw a sender/recipient block.
	 *
	 * @param TCPDF        $pdf     PDF object
	 * @param float        $x       X
	 * @param float        $y       Y
	 * @param float        $w       Width
	 * @param float        $h       Height
	 * @param string       $label   Label
	 * @param string       $name    Name
	 * @param string       $address Address
	 * @param array<int,int> $accent Accent color
	 * @param bool         $filled  Filled background
	 * @param string       $direction Text direction
	 * @return void
	 */
	protected function farmevoAddressCard(&$pdf, $x, $y, $w, $h, $label, $name, $address, array $accent, $filled = false, $direction = 'L')
	{
		$default_font_size = pdf_getPDFFontSize($GLOBALS['langs']);
		$line = $this->farmevoColors['line'];
		$text = $this->farmevoColors['text'];
		$muted = $this->farmevoColors['muted'];

		$pdf->SetFillColor($filled ? 245 : 255, $filled ? 250 : 255, $filled ? 245 : 255);
		$pdf->SetDrawColor($line[0], $line[1], $line[2]);
		$pdf->RoundedRect($x, $y, $w, $h, 2, '1234', 'DF');
		$pdf->SetFillColor($accent[0], $accent[1], $accent[2]);
		$pdf->RoundedRect($x, $y, $w, 2.3, 2, '1001', 'F');

		$pdf->SetTextColor($accent[0], $accent[1], $accent[2]);
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($x + 3, $y + 4);
		$pdf->MultiCell($w - 6, 3, dol_strtoupper($label), 0, $direction);

		$pdf->SetTextColor($text[0], $text[1], $text[2]);
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetXY($x + 3, $y + 8.2);
		$pdf->MultiCell($w - 6, 4, dol_trunc($name, 86), 0, $direction);

		$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($x + 3, min($pdf->GetY() + 1, $y + 14));
		$pdf->MultiCell($w - 6, 3.4, $address, 0, $direction);
	}

	/**
	 * Draw company logo or fallback Farmevo logo.
	 *
	 * @param TCPDF        $pdf         PDF object
	 * @param CommonObject $object      Source object
	 * @param float        $x           X
	 * @param float        $y           Y
	 * @param float        $maxWidth    Max width
	 * @param float        $maxHeight   Max height
	 * @param Translate    $outputlangs Output language
	 * @return void
	 */
	protected function farmevoDrawLogo(&$pdf, $object, $x, $y, $maxWidth, $maxHeight, $outputlangs)
	{
		$logo = $this->farmevoGetLogoPath($object);
		if ($logo && is_readable($logo)) {
			$size = @getimagesize($logo);
			if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
				$ratio = $size[0] / $size[1];
				$drawWidth = min($maxWidth, $maxHeight * $ratio);
				$drawHeight = $drawWidth / $ratio;
				if ($drawHeight > $maxHeight) {
					$drawHeight = $maxHeight;
					$drawWidth = $drawHeight * $ratio;
				}
				$pdf->Image($logo, $x, $y, $drawWidth, $drawHeight);
				return;
			}
		}

		$darkgreen = $this->farmevoColors['darkgreen'];
		$pdf->SetTextColor($darkgreen[0], $darkgreen[1], $darkgreen[2]);
		$pdf->SetFont('', 'B', pdf_getPDFFontSize($outputlangs) + 4);
		$pdf->SetXY($x, $y + 3);
		$pdf->MultiCell($maxWidth, 6, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
	}

	/**
	 * Resolve company logo path with Farmevo fallback asset.
	 *
	 * @param CommonObject $object Source object
	 * @return string
	 */
	protected function farmevoGetLogoPath($object)
	{
		global $conf;

		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && !empty($this->emetteur->logo)) {
			$logodir = $conf->mycompany->dir_output;
			if (!empty($conf->mycompany->multidir_output[$object->entity ?? $conf->entity])) {
				$logodir = $conf->mycompany->multidir_output[$object->entity ?? $conf->entity];
			}

			$candidates = array();
			if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') && !empty($this->emetteur->logo_small)) {
				$candidates[] = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
			}
			$candidates[] = $logodir.'/logos/'.$this->emetteur->logo;

			foreach ($candidates as $candidate) {
				if (is_readable($candidate)) {
					return $candidate;
				}
			}
		}

		$fallback = DOL_DOCUMENT_ROOT.'/core/modules/farmevo/farmevo_logo.png';
		return is_readable($fallback) ? $fallback : '';
	}
}
