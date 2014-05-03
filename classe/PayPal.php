<?php
class CMVRPayPal {
	
	function __construct($bVerify=true) {

		if($bVerify)
			if(!$this->verify())
				die;
		if(empty($_POST['txn_type'])
		|| empty($_POST['payment_status'])
		|| empty($_POST['mc_gross'])
		|| empty($_POST['txn_id'])) return; // commande invalide
		if(!in_array($_POST['txn_type'],array('web_accept','virtual_terminal', 'subscr_payment', 'send_money', 'recurring_payment', 'express_checkout', 'cart')))
			$this->sTransType = 'money in';
		if(!in_array($_POST['payment_status'],array('Canceled_Reversal','Completed'))) {
			if(in_array($_POST['payment_status'],array('Refunded','Reversed')))
				$this->sTransType = 'money out';
			else
				$this->sTransType = 'money do not move';
			}	

		// caisse datas
		if(!empty($_POST['mc_gross']))
			$this->sAmount = $_POST['mc_gross'];
		if(!empty($_POST['tax']))
			$this->sTax = $_POST['tax'];
		if(!empty($_POST['shipping']))
			$this->sShipping = $_POST['shipping'];
		if(!empty($_POST['mc_fee']))
			$this->sBankFee = $_POST['mc_fee'];
		if(!empty($_POST['mc_currency']))
			$this->sCurrency = $_POST['mc_currency'];
		if(!empty($_POST['txn_id']))
			$this->sValidNum = $_POST['txn_id'];

		if(!empty($_POST['invoice']))
			$this->sOrderId = $_POST['invoice'];
		if(!empty($_POST['custom']))
			$this->sCustom = $_POST['custom'];

		// panier datas
		if($_POST['txn_type'] != 'cart') {
			if(!empty($_POST['item_name']))
				$this->sItemName[1] = $_POST['item_name'];
			if(!empty($_POST['quantity']))
				$this->sItemQty[1] = $_POST['quantity'];
			if(!empty($_POST['item_number']))
				$this->sItemId[1] = $_POST['item_number'];
		} else { // paypal shopping card
			$i=1;
			while(!empty($_POST['item_name'.$i])) {
				$this->sItemName[$i] = $_POST['item_name'.$i];
				if(!empty($_POST['quantity'.$i]))
				$this->sItemQty[$i] = $_POST['quantity'.$i];
				if(!empty($_POST['option_name1'.$i]))
				$this->sItemId[$i] = $_POST['option_name1'.$i];
				if(!empty($_POST['option_name2'.$i]))
				$this->sItemRef[$i] = $_POST['option_name2'.$i];
				if(!empty($_POST['option_selection1'.$i]))
				$this->sItemRef[$i] .= ' / '.$_POST['option_selection1'.$i];
				if(!empty($_POST['option_selection2'.$i]))
				$this->sItemRef[$i] .= ' / '.$_POST['option_selection2'.$i];
				$this->sItemAmountHt[$i] = $_POST['payment_gross_'.$i] - $_POST['tax'.$i];
				$i++;
			}

		}
		// customer datas
		if(!empty($_POST['payer_email']))
			$this->sCustomerEmail =  $_POST['payer_email'];
		if(!empty($_POST['first_name']))
			$this->sCustomerFirstName = $_POST['first_name'];
		if(!empty($_POST['last_name']))
			$this->sCustomerName = $_POST['last_name'];
		if(!empty($_POST['address_street']))
			$this->sCustomerAddress = $_POST['address_street'];
		if(!empty($_POST['address_city']))
			$this->sCustomerCity = $_POST['address_city'];
		if(!empty($_POST['address_zip']))
			$this->sCustomerZipCode = $_POST['address_zip'];
		if(!empty($_POST['address_country']))
			$this->sCustomerCountry = $_POST['address_country'];
	}


	function verify() {

		$bVerified = false;
		$sChaine = 'Paramètres:';
		$sRequest = 'cmd=_notify-validate';
		foreach($_POST as $sKey => $sData)
		{
			$sChaine .= " $sKey=$sData,";
			$sData = urlencode(stripslashes($sData));
			$sRequest .= "&$sKey=$sData";
		}
		if(isset($ref_id) && $sTransType != 'subscr_cancel' ){
							if($ref_id != $receiver_id ) {if(!empty($_POST['test_ipn']) && $_POST['test_ipn'] != 1) die("something wrong!");}
						  }
		$sUrl = 'www.paypal.com';
		if(!empty($_POST['test_ipn']) && $_POST['test_ipn'] == 1) 	$sUrl = 'www.sandbox.paypal.com';
		$sHeader .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$sHeader .= "host: $sUrl\r\n";
		$sHeader .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$sHeader .= "Content-Length: " . strlen($sRequest) . "\r\n\r\n";
		$oFp = fsockopen ($sUrl, 80, $errno, $errstr, 30);
		if (!$oFp) 
			die;
		fputs ($oFp, $sHeader.$sRequest);
		while (!feof($oFp))	{
			$sResult = fgets ($oFp, 1024);
			if (strcmp ($sResult, 'VERIFIED') == 0)	
				$bVerified = true;
			else if  (strcmp ($sResult, "INVALID") == 0)
				$bVerified = false;
		}
		$this->log($sRequest.' '.($bVerified ? 'VERIFIED' : 'INVALID'));
		return $bVerified;
	}

	function log($sRequest) {
		$pf = fopen('log/paypal-communication.txt', 'a');	
		fwrite($pf, "$sRequest\n");
		fclose($pf);
	}

/*
		$this->sSubscrId = $_POST['subscr_id'];
		$this->sReceiverId = $_POST['receiver_id'];
*/

	
}
?>
