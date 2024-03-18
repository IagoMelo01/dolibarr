<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modSafra_SafraTriggers.class.php
 * \ingroup safra
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modSafra_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/safra/class/cultura.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/safra/class/recomendacaoadubo.class.php';


/**
 *  Class of triggers for Safra module
 */
class InterfaceSafraTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Safra triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'safra@safra';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Função para retornar o token da api da embrapa
	 * 
	 * @return string token_api if ok, null if KO
	 */
	private function embrapa_api_token(){
		$url = "https://api.cnptia.embrapa.br/token";
		$headers = array(
			"Authorization: Basic YzRDUzNBWm5JbkJ4RFZKTzR1TGR4T2ljaTA0YTpCU0tYeElpaUR3OUhoaVlBWjNoRW81aU9COWth",
		);
		$data = array("grant_type" => "client_credentials");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($status_code == 200) {
			$token_data = json_decode($response, true);
			$access_token = $token_data["access_token"];
			echo "Access Token: " . $access_token;
			return $access_token;
		} else {
			$r = "Failed to retrieve access token. Status code: " . $status_code;
			echo $r;
			dol_syslog($r);
			return null;
		}

		return null;

		curl_close($ch);
	}

	/**
	 * Função para receber uma recomendação de adubação da embrapa
	 * 
	 * @param string $token recebe o token de autorização da api
	 * @param RecomendacaoAdubo $rec_adubo objeto onde será feita a alteração
	 * @param Cultura $obj_cultura objeto da cultura relacionada a recomendação de adubação
	 * @return int 0 if ok, <0 if KO
	 */
	private function embrapa_api_recomendacao_adubo(string $token, $rec_adubo, $obj_cultura){
		// Parâmetros da consulta
		$idCultura = 1;
		$expectativaProdutividade = 1;
		$nome_arquivo = 'rec_adubo_' . time();
		// $rec_adubo = null;

		echo $nome_arquivo;

		// URL base da API
		$url_base = "https://api.cnptia.embrapa.br/agritec/v1/adubacao/recomendacao?";
		
		
		// Preparação dos dados
		$idCultura = $obj_cultura->embrapaid;
		$expectativaProdutividade = $rec_adubo->expect_prod;
		
		
		
		// Construir a URL completa com os parâmetros
		$url = $url_base . "idCultura=$idCultura&expectativaProdutividade=$expectativaProdutividade&identificadorMetodoExtracaoFosforo=$rec_adubo->id_metodo_fosforo&identificadorClasseTexturalSolo=$rec_adubo->id_classe_textural_solo&capacidadeTrocaCation=$rec_adubo->ctc&fosforo=$rec_adubo->fosforo&potassio=$rec_adubo->potassio&materiaOrganica=$rec_adubo->materia_organica&teorArgila=$rec_adubo->teor_argila&saturacaoPorBases=$rec_adubo->saturacao_bases&prntCalcario=$rec_adubo->prnt_calcario";
		
		echo "<br>" . $url . "<br>"; 

		// Defina o caminho para salvar o arquivo JSON
		$caminho_arquivo = DOL_DOCUMENT_ROOT."/custom/safra/assets/json/adubacao/". $nome_arquivo .".json";
		
		// Configuração da solicitação CURL
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'accept: application/json',
			'Authorization: Bearer ' . $token
		));

		// Executar a solicitação
		$response = curl_exec($ch);
		
		// Verificar se ocorreu algum erro
		if(curl_errno($ch)){
			dol_syslog('Erro ao fazer a solicitação CURL: ' . curl_error($ch));
			return 1;
			// Trate o erro adequadamente
		}
		
		// Fechar a conexão CURL
		curl_close($ch);
		
		// Salvar a resposta JSON em um arquivo
		file_put_contents($caminho_arquivo, $response);
		
		// Verificar se o arquivo foi salvo com sucesso
		if (file_exists($caminho_arquivo)) {
			dol_syslog("Arquivo salvo com sucesso em: $caminho_arquivo");
			$prnt = $rec_adubo->prnt_calcario == NULL ? 50 : $rec_adubo->prnt_calcario;
			$v1 = 50;
			$v2 = $rec_adubo->saturacao_bases;
			$t = $rec_adubo->ctc;

			$dose = (($v1 - $v2) * $t)/$prnt;

			$json_decoded = json_decode($response, true);
			$data = $json_decoded['data'];

			$html = "Saturação por bases: <strong>" . $data['interpretacaoSaturacaoPorBases']. "</strong><br>";
			$html .= "Fósforo - P2O5: <strong>" . $data['interpretacaoFosforo']. "</strong><br>";
			$html .= "Potássio - K: <strong>" . $data['interpretacaoPotassio']. "</strong><br>";
			$html .= "Dose Calcário (ton/ha): <strong>" . $dose. "</strong><br>";
			$html .= "Observações calagem: <strong>" . $data['observacoesCalagem']. "</strong><br>";
			$html .= "<h4> Formulações de adubação sugeridas </h4>";
			foreach($data['formulacoesSugeridas'] as $key){
				$html .= "<div style=\"border: 1px solid black; margin: 5px;\"> Composição NPK: <strong>". $key['composicaoNPK'] ."</strong><br> Dose: <strong>".$key['dose']."kg/ha</strong><br></div>";
			}
			$html .= "<br> <h4> Observação de Adubação <h4>" . $data['observacoesAdubacao'];

			include DOL_DOCUMENT_ROOT."/custom/safra/assets/phpassets/tabela_compatiblidade.php";

			$rec_adubo->description = $html;
			$rec_adubo->tab_compatibilidade = $tab_comp;
			$rec_adubo->arquivo_json = $caminho_arquivo;
			$rec_adubo->dose_calc_rec = $dose;
			
			// $u = $rec_adubo->update($user);
			// $return = null;
			// $u > 0 ? $return = 0 : $return = 3;

			return 0;

		} else {
			dol_syslog("Erro ao salvar o arquivo.");
			return 2;
			// Trate o erro adequadamente
		}

		return 0;
		
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('safra')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		if (is_callable($callback)) {
			dol_syslog(
				"Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
			);

			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		}

		if($action=="RECOMENDACAOADUBO_CREATE"){
			$rec_adubo = new RecomendacaoAdubo($object->db);
			$rec_adubo->fetch($object->id);
			
			$cultura = new Cultura($object->db);
			$cultura->fetch($rec_adubo->fk_cultura);

			$new_ref = "REC_ADUBO_".$cultura->ref." - ". date("d-m-Y", time())." - ".$object->id;
			$rec_adubo->ref = $new_ref;
			$r = $rec_adubo->update($user);
			dol_syslog("Update do ref em recomendação adubo retornou $r, >0 é ok, consulte a documentação.");


			$tk = $this->embrapa_api_token();
			$r = $this->embrapa_api_recomendacao_adubo($tk, $rec_adubo, $cultura);
			$u = $rec_adubo->update($user);
			dol_syslog("recomendação adubo retornou $r como resposta e $u como resposta da query, 0/>0 é ok , outros códigos, consulte documentação.");

		}

		// Or you can execute some code here
		switch ($action) {
			// Users
			//case 'USER_CREATE':
			//case 'USER_MODIFY':
			//case 'USER_NEW_PASSWORD':
			//case 'USER_ENABLEDISABLE':
			//case 'USER_DELETE':

			// Actions
			//case 'ACTION_MODIFY':
			//case 'ACTION_CREATE':
			//case 'ACTION_DELETE':

			// Groups
			//case 'USERGROUP_CREATE':
			//case 'USERGROUP_MODIFY':
			//case 'USERGROUP_DELETE':

			// Companies
			//case 'COMPANY_CREATE':
			//case 'COMPANY_MODIFY':
			//case 'COMPANY_DELETE':

			// Contacts
			//case 'CONTACT_CREATE':
			//case 'CONTACT_MODIFY':
			//case 'CONTACT_DELETE':
			//case 'CONTACT_ENABLEDISABLE':

			// Products
			//case 'PRODUCT_CREATE':
			//case 'PRODUCT_MODIFY':
			//case 'PRODUCT_DELETE':
			//case 'PRODUCT_PRICE_MODIFY':
			//case 'PRODUCT_SET_MULTILANGS':
			//case 'PRODUCT_DEL_MULTILANGS':

			//Stock mouvement
			//case 'STOCK_MOVEMENT':

			//MYECMDIR
			//case 'MYECMDIR_CREATE':
			//case 'MYECMDIR_MODIFY':
			//case 'MYECMDIR_DELETE':

			// Sales orders
			//case 'ORDER_CREATE':
			//case 'ORDER_MODIFY':
			//case 'ORDER_VALIDATE':
			//case 'ORDER_DELETE':
			//case 'ORDER_CANCEL':
			//case 'ORDER_SENTBYMAIL':
			//case 'ORDER_CLASSIFY_BILLED':
			//case 'ORDER_SETDRAFT':
			//case 'LINEORDER_INSERT':
			//case 'LINEORDER_UPDATE':
			//case 'LINEORDER_DELETE':

			// Supplier orders
			//case 'ORDER_SUPPLIER_CREATE':
			//case 'ORDER_SUPPLIER_MODIFY':
			//case 'ORDER_SUPPLIER_VALIDATE':
			//case 'ORDER_SUPPLIER_DELETE':
			//case 'ORDER_SUPPLIER_APPROVE':
			//case 'ORDER_SUPPLIER_REFUSE':
			//case 'ORDER_SUPPLIER_CANCEL':
			//case 'ORDER_SUPPLIER_SENTBYMAIL':
			//case 'ORDER_SUPPLIER_RECEIVE':
			//case 'LINEORDER_SUPPLIER_DISPATCH':
			//case 'LINEORDER_SUPPLIER_CREATE':
			//case 'LINEORDER_SUPPLIER_UPDATE':
			//case 'LINEORDER_SUPPLIER_DELETE':

			// Proposals
			//case 'PROPAL_CREATE':
			//case 'PROPAL_MODIFY':
			//case 'PROPAL_VALIDATE':
			//case 'PROPAL_SENTBYMAIL':
			//case 'PROPAL_CLOSE_SIGNED':
			//case 'PROPAL_CLOSE_REFUSED':
			//case 'PROPAL_DELETE':
			//case 'LINEPROPAL_INSERT':
			//case 'LINEPROPAL_UPDATE':
			//case 'LINEPROPAL_DELETE':

			// SupplierProposal
			//case 'SUPPLIER_PROPOSAL_CREATE':
			//case 'SUPPLIER_PROPOSAL_MODIFY':
			//case 'SUPPLIER_PROPOSAL_VALIDATE':
			//case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
			//case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
			//case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
			//case 'SUPPLIER_PROPOSAL_DELETE':
			//case 'LINESUPPLIER_PROPOSAL_INSERT':
			//case 'LINESUPPLIER_PROPOSAL_UPDATE':
			//case 'LINESUPPLIER_PROPOSAL_DELETE':

			// Contracts
			//case 'CONTRACT_CREATE':
			//case 'CONTRACT_MODIFY':
			//case 'CONTRACT_ACTIVATE':
			//case 'CONTRACT_CANCEL':
			//case 'CONTRACT_CLOSE':
			//case 'CONTRACT_DELETE':
			//case 'LINECONTRACT_INSERT':
			//case 'LINECONTRACT_UPDATE':
			//case 'LINECONTRACT_DELETE':

			// Bills
			//case 'BILL_CREATE':
			//case 'BILL_MODIFY':
			//case 'BILL_VALIDATE':
			//case 'BILL_UNVALIDATE':
			//case 'BILL_SENTBYMAIL':
			//case 'BILL_CANCEL':
			//case 'BILL_DELETE':
			//case 'BILL_PAYED':
			//case 'LINEBILL_INSERT':
			//case 'LINEBILL_UPDATE':
			//case 'LINEBILL_DELETE':

			//Supplier Bill
			//case 'BILL_SUPPLIER_CREATE':
			//case 'BILL_SUPPLIER_UPDATE':
			//case 'BILL_SUPPLIER_DELETE':
			//case 'BILL_SUPPLIER_PAYED':
			//case 'BILL_SUPPLIER_UNPAYED':
			//case 'BILL_SUPPLIER_VALIDATE':
			//case 'BILL_SUPPLIER_UNVALIDATE':
			//case 'LINEBILL_SUPPLIER_CREATE':
			//case 'LINEBILL_SUPPLIER_UPDATE':
			//case 'LINEBILL_SUPPLIER_DELETE':

			// Payments
			//case 'PAYMENT_CUSTOMER_CREATE':
			//case 'PAYMENT_SUPPLIER_CREATE':
			//case 'PAYMENT_ADD_TO_BANK':
			//case 'PAYMENT_DELETE':

			// Online
			//case 'PAYMENT_PAYBOX_OK':
			//case 'PAYMENT_PAYPAL_OK':
			//case 'PAYMENT_STRIPE_OK':

			// Donation
			//case 'DON_CREATE':
			//case 'DON_UPDATE':
			//case 'DON_DELETE':

			// Interventions
			//case 'FICHINTER_CREATE':
			//case 'FICHINTER_MODIFY':
			//case 'FICHINTER_VALIDATE':
			//case 'FICHINTER_DELETE':
			//case 'LINEFICHINTER_CREATE':
			//case 'LINEFICHINTER_UPDATE':
			//case 'LINEFICHINTER_DELETE':

			// Members
			//case 'MEMBER_CREATE':
			//case 'MEMBER_VALIDATE':
			//case 'MEMBER_SUBSCRIPTION':
			//case 'MEMBER_MODIFY':
			//case 'MEMBER_NEW_PASSWORD':
			//case 'MEMBER_RESILIATE':
			//case 'MEMBER_DELETE':

			// Categories
			//case 'CATEGORY_CREATE':
			//case 'CATEGORY_MODIFY':
			//case 'CATEGORY_DELETE':
			//case 'CATEGORY_SET_MULTILANGS':

			// Projects
			//case 'PROJECT_CREATE':
			//case 'PROJECT_MODIFY':
			//case 'PROJECT_DELETE':

			// Project tasks
			//case 'TASK_CREATE':
			//case 'TASK_MODIFY':
			//case 'TASK_DELETE':

			// Task time spent
			//case 'TASK_TIMESPENT_CREATE':
			//case 'TASK_TIMESPENT_MODIFY':
			//case 'TASK_TIMESPENT_DELETE':
			//case 'PROJECT_ADD_CONTACT':
			//case 'PROJECT_DELETE_CONTACT':
			//case 'PROJECT_DELETE_RESOURCE':

			// Shipping
			//case 'SHIPPING_CREATE':
			//case 'SHIPPING_MODIFY':
			//case 'SHIPPING_VALIDATE':
			//case 'SHIPPING_SENTBYMAIL':
			//case 'SHIPPING_BILLED':
			//case 'SHIPPING_CLOSED':
			//case 'SHIPPING_REOPEN':
			//case 'SHIPPING_DELETE':

			// and more...

			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
