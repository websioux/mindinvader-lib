<?php
class CMVRTransaction {

	private $_oMind;
	private $_oPayDatas;
	
	function __construct($oMind) {
		$this->_oMind = $oMind;
	}

	function _init($sPayService) {
		require_once($sPayService.'.php');
		$sPayService = 'CMVR'.$sPayService;
		$this->_oPayDatas = new $sPayService(false);
	}

	function actions() {
		$this->_init($this->_oMind->sPayService);
		$aCaisse = $this->getCaisse();
		$aPanier = $this->getPanier();
		$aCustomer = $this->getCustomer();
		$aAddress = $this->getAddress();
		$this->_oMind->sendTransaction($aCaisse, $aCustomer, $aPanier, $aAddress);
		if(!empty($aAddress))
			$this->_oMind->sendContactAddress($aCustomer, $aAddress);
	}

	/* this should only be used if there is are no better datas about customer and order in local database
		(paypal email is mot likelly different from the tracked email)
	*/
	function getCustomer(){
		$aCustomer = array();
		if(!empty($this->_oPayDatas->sCustomerEmail))	
			$aCustomer['email'] = $this->_oPayDatas->sCustomerEmail;
		if(!empty($this->_oPayDatas->sCustomerFirstname))	
			$aCustomer['firstname'] = $this->_oPayDatas->sCustomerFirstName;
		if(!empty($this->_oPayDatas->sCustomerName))	
			$aCustomer['name'] = $this->_oPayDatas->sCustomerName;
		if(!empty($this->_oPayDatas->sCustomerCompany))	
		$aCustomer['company'] = $this->_oPayDatas->sCustomerCompany;
		// CUSTOMIZATION : OverWrite whith database datas based on $this->_oPayDatas->sOrderId
		// .....

		return $aCustomer;
	}

	/* this should only be used if there is are no better datas about customer and order in local database
	*/
	function getAddress(){
		$aAddress = array();
		if(!empty($this->_oPayDatas->sCustomerAddress))	
			$aAddress['address1'] = $this->_oPayDatas->sCustomerAddress;
		if(!empty($this->_oPayDatas->sCustomerCity))	
			$aAddress['city'] = $this->_oPayDatas->sCustomerCity;
		if(!empty($this->_oPayDatas->sCustomerZipCode))	
			$aAddress['postcode'] = $this->_oPayDatas->sCustomerZipCode;
		if(!empty($this->_oPayDatas->sCustomerIdState))	
			$aAddress['id_state'] = $this->_oPayDatas->sCustomeridState;
		if(!empty($this->_oPayDatas->sCustomerCountry))	
			$aAddress['country'] = $this->_oPayDatas->sCustomerCountry;
		if(!empty($this->_oPayDatas->sCustomerAlias))	
			$aAddress['alias'] = $this->_oPayDatas->sCustomerAlias;
		if(!empty($this->_oPayDatas->sCustomerCompany))	
			$aAddress['company'] = $this->_oPayDatas->sCustomerCompany;
		if(!empty($this->_oPayDatas->sCustomerName))	
			$aAddress['lastname'] = $this->_oPayDatas->sCustomerName;
		if(!empty($this->_oPayDatas->sCustomerFirstname))	
			$aAddress['firstname'] = $this->_oPayDatas->sCustomerFirstName;
		if(!empty($this->_oPayDatas->sCustomerPhone))	
			$aAddress['phone'] = $this->_oPayDatas->sCustomerPhone;
		if(!empty($this->_oPayDatas->sCustomerCellPhone))	
			$aAddress['phone_mobile'] = $this->_oPayDatas->sCustomerCellPhone;


		// CUSTOMIZATION : OverWrite whith database datas based on $this->_oPayDatas->sOrderId
		// .....

		
		return $aAddress;
	}


	/* Fonction personalisable qui consiste à récupérer les informations dites de "caisse" à partir de
	 * $this->_oPayDatas et données en base de donnée locale
	*/
	function getCaisse(){
	
		$aCaisse = $this->getCustomer();
		$aCaisse['mode'] = strtolower($this->_oMind->sPayService);
		if(!empty($this->_oPayDatas->sValidNum))
			$aCaisse['validationID'] = $this->_oPayDatas->sValidNum;

		// this should work most of the time
		$aCaisse['customID'] = !empty($this->_oPayDatas->sOrderId) ? $this->_oPayDatas->sOrderId : date('YmdGis');

		// this should only be used if there is are no better datas about customer and order in local database
		if(!empty( $this->_oPayDatas->sAmount))
			$aCaisse['amount'] = $this->_oPayDatas->sAmount;
		if(!empty($this->_oPayDatas->sTax))
			$aCaisse['taxes'] = $this->_oPayDatas->sTax;
		if(!empty($this->_oPayDatas->sBankFee))
			$aCaisse['frais_banc'] = $this->_oPayDatas->sBankFee;
		if(!empty($this->_oPayDatas->sShipping))
			$aCaisse['frais_port'] = $this->_oPayDatas->sShipping;
		if(!empty($this->_oPayDatas->sCurrency))
			$aCaisse['currency'] = 	$this->_oPayDatas->sCurrency;

		// CUSTOMIZATION : OverWrite whith database datas based on $this->_oPayDatas->sOrderId
		// .....

		
		
		return $aCaisse;	
	}

	/* Fonction personalisable qui consiste à récupérer les informations dites de "panier" à partir de
	 * $this->oPayDatas et données en base de donnée locale
	*/
	function getPanier(){

		// this should only be used if there is are no better datas about basket in local database
		if(!empty($this->_oPayDatas->sItemName)) {
			for($i=1;$i<=count($this->_oPayDatas->sItemName);$i++){
				if(!empty($this->_oPayDatas->sItemId[$i]))
					$aPanier[$i]['articleID'] = $this->_oPayDatas->sItemId[$i];
				if(!empty($this->_oPayDatas->sItemName[$i]))
					$aPanier[$i]['label'] = $this->_oPayDatas->sItemName[$i];
				if(!empty($this->_oPayDatas->sItemQty[$i]))
					$aPanier[$i]['qty'] = $this->_oPayDatas->sItemQty[$i];
				if(!empty($this->_oPayDatas->sItemRef[$i]))
					$aPanier[$i]['reference'] = $this->_oPayDatas->sItemRef[$i];
				if(count($this->_oPayDatas->sItemName) == 1) {
					$aPanier[1]['prix_ht_unit'] = $this->_oPayDatas->sAmount - $this->_oPayDatas->sTax;
				} else {
					$aPanier[1]['prix_ht_unit'] = $this->_oPayDatas->sItemAmountHt[$i];
				}
			}
		}
		// CUSTOMIZATION : OverWrite whith database datas based on $this->_oPayDatas->sOrderId
		// .....



	return $aPanier;	
	}
}
?>
