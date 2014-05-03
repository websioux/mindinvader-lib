<?php
class CMVRSystemPay {

	function __construct($bVerify=true) {

		if($bVerify)
			if(!$this->verify())
				die;
		if(empty($_POST['vads_result'])
		|| empty($_POST['vads_auth_mode'])
		|| empty($_POST['vads_operation_type'])
		|| empty($_POST['vads_order_id'])
		|| empty($_POST['vads_effective_amount'])
		|| empty($_POST['vads_amount'])
		|| empty($_POST['vads_currency'])
		|| empty($_POST['vads_payment_certificate'])) return; // commande invalide
		if($_POST['vads_result'] != '00' || $_POST['vads_auth_mode'] != 'FULL') return; // commande échouée
		if($_POST['vads_operation_type'] == 'DEBIT')
			$this->sTransType = 'money in';
		if($_POST['vads_operation_type'] == 'CREDIT')
			$this->sTransType = 'money out';

		// caisse datas
		$this->sOrderId = $_POST['vads_order_id'];
		$this->sValidNum = $_POST['vads_payment_certificate'];
		if(empty($_POST['vads_change_rate']))
			$this->sAmount = $_POST['vads_effective_amount'];
		else 
			$this->sAmount = $_POST['vads_effective_amount'] * $_POST['vads_change_rate'];
		$this->sAmount = $this->sAmount / 100;
		$this->sCurrency = CMVRUtil::convertCurrencyCode($_POST['vads_currency'],'alphabet');

		// panier datas
		// not available with system pay

		// customer datas
		if(!empty($_POST['vads_cust_email']))
			$this->sCustomerEmail =  $_POST['vads_cust_email'];
		if(!empty($_POST['vads_cust_name']))
		$this->sCustomerName = $_POST['vads_cust_name'];
		if(!empty($_POST['vads_cust_address_number']))
		$this->sCustomerAddress = $_POST['vads_cust_address_number'];
		if(!empty($_POST['vads_cust_address']))
			if(!empty($this->sCustomerAddress))
				$this->sCustomerAddress .= $_POST['vads_cust_address'];
			else
				$this->sCustomerAddress = $_POST['vads_cust_address'];
		if(!empty($_POST['vads_cust_city']))
		$this->sCustomerCity = $_POST['vads_cust_city'];
		if(!empty($_POST['vads_cust_zip']))
		$this->sCustomerZipCode = $_POST['vads_cust_zip'];
		if(!empty($_POST['vads_cust_country']))
		$this->sCustomerCountry = $_POST['vads_cust_country'];
		if(!empty($_POST['vads_cust_phone']))
		$this->sCustomerPhone = $_POST['vads_cust_phone'];
		if(!empty($_POST['vads_cust_cellphone']))
		$this->sCustomerCellPhone = $_POST['vads_cust_cellphone'];
		if(!empty($_POST['vads_cust_title']))
		$this->sCustomerAlias = $_POST['vads_cust_title'];
	}


	function verify() {
		$bVerified = false;
		$sKey=SYSTEMPAY_KEY;
		$aParams = $_POST;
		ksort($aParams);
		$sContenuSignature = "";
		foreach ($aParams as $sNom => $sValeur) {
			if(substr($sNom,0,5) == 'vads_')
				$sContenuSignature .= $sValeur."+";
			}
		$sContenuSignature .= $sKey; // On ajoute le certificat à la fin
		$sCalcSignature = sha1($sContenuSignature);
		if($_POST['signature'] == $sCalcSignature && !empty($_POST['signature']))
			$bVerified = true;
		$this->log($aParams,$bVerified);	
		return $bVerified;
	}

	function log($sRequest) {
		$f = fopen('log/systempay.txt','a');
		fwrite($f,gmdate("d/m/Y - H:i:s", time()));
		fwrite($f,$bVerified ? 'VERIFIED' : 'INVALID');
		fwrite($f,json_encode($sRequest)."\n");
		fclose($f);
	}

}


	

?>
