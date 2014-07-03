<?php
// for debugging
// ?MVR_dbug=3DFISMSLSKD (Mindinvader secret key)


if(!defined('URL_MINDINVADER_API'))
	define('URL_MINDINVADER_API','http://track.mindinvader.com');

class CSettings {
	static function notificationUrlPaths() {
		global $aNotificationUrlPaths;
		if(empty($aNotificationUrlPaths))
			return array();
		else return $aNotificationUrlPaths;
	}
	static function doNotTrackVisitPaths() {
		global $aDoNotTrackVisitPaths;
		if(empty($aDoNotTrackVisitPaths))
			return array();
		else return $aDoNotTrackVisitPaths;
	}
}

class CMindInvader {	

	public function __construct() {
		$this->_sDbug ='';
		$this->_sFunction = 'visit';
		$this->_nClientId = MVR_CLIENT_ID;
		$this->_sSiteKey = MVR_SITE_KEY;
		if(empty($this->_nClientId) || empty($this->_sSiteKey))
			return;
		$this->_sUrl = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') 
			$this->_sUrl = 'https';	
		$this->_sUrl .= '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$this->_aUrl = parse_url($this->_sUrl);
		$aDoNotTrackPaths = CSettings::doNotTrackVisitPaths();
		if(!empty($aDoNotTrackPaths)) {
			foreach($aDoNotTrackPaths as $sDoNotTrackPath) {
				if(strpos($this->_aUrl['path'],$sDoNotTrackPath) !== false)
					return;
			}
		}	
		if(in_array($this->_aUrl['path'],CSettings::notificationUrlPaths())) { // case transaction
			$this->sPayService = array_search($this->_aUrl['path'],CSettings::NotificationUrlPaths());
			$oTrans = new CMVRTransaction($this);
			$oTrans->actions();
		} else {	
			if(!empty($_GET[CBM_RETURN_FIELD])) // case cybermailing return subscribe
				$this->cybermailingLanding();
			else
				$this->_HttpTracker(); // case visit
		}
	}

	/****************************************************************************************************
	 *                                  FONCTIONS PUBLIQUES
	 ****************************************************************************************************/ 


	/* Envoi les données lors de la conversion d'un nouveau contact
	 * exemple de $aCustomer = array(
	 *					gender => 'M.', // M. Mme ou Mlle
	 * 					firstname => 'Luc',
	 * 					name => 'Lucky',
	 * 					email => 'luckyluc@hotmail.com',
	 * 					company => 'Sherifs Online Ltd'
	 * 					);

	 *	$aCustomer['email'] est obligatoire, les autres valeurs sont recommandées
	 * 
	 */

	public function sendContact($aCustomer,$date_add='',$type='new') {
			$this->_sFunction = 'contact';
			$sQuery = 'type=='.$this->_sFunction;
			if($type == 'synchronize')
				$sQuery .= '|level==admin|date_add=='.str_replace(' ','%20',$date_add);
			$sQuery .= '|MVR_email=='.$aCustomer['email'];
			if(!empty($aCustomer['genre'])) $sQuery .= '|MVR_gender=='.$aCustomer['genre'];
			if(!empty($aCustomer['prenom'])) $sQuery .= '|MVR_firstname=='.rawurlencode($aCustomer['prenom']);
			if(!empty($aCustomer['nom'])) $sQuery .= '|MVR_name=='.rawurlencode($aCustomer['nom']);
			if(!empty($aCustomer['entreprise'])) $sQuery .= '|MVR_company=='.rawurlencode($aCustomer['entreprise']);		
			if(!empty($aCustomer['gender'])) $sQuery .= '|MVR_gender=='.$aCustomer['gender'];
			if(!empty($aCustomer['firstname'])) $sQuery .= '|MVR_firstname=='.rawurlencode($aCustomer['firstname']);
			if(!empty($aCustomer['name'])) $sQuery .= '|MVR_name=='.rawurlencode($aCustomer['name']);
			if(!empty($aCustomer['company'])) $sQuery .= '|MVR_company=='.rawurlencode($aCustomer['company']);		
			$this->_HttpTracker($sQuery);	
	}	

	/* Envoi les données lors de la conversion d'un objectif arbitraire
	 * $nId est l'identifiant de l'objectif donné dans l'interface
	 */

	public function sendGoal($nId) {
			$this->_sFunction = 'goal';
			$sQuery = 'type=='.$this->_sFunction .'|MVR_goal_id=='.$nId;
			$this->_HttpTracker($sQuery);	
	}	


	/* Transmet les données correspondant au passage d'une commande.
	 * 
	 *
	 * Champs obligatoires :
	 * $aCaisse['customID']
	 * $aCaisse['amount']
	 * $aCustomer['email'];
	 *
	 * Les autres champs sont recommandés - voir les instructions détaillées
	 * 
	 * Exemple 
	 *	$aCaisse = Array(
	 *				'customOrderID' => 645674,
	 *				'amount'=> 596.3,
	 *				'currency' => 'EUR', 
	 *				);
	 *	$aCustomer = Array(
	 *				'email' => martin.dupont@gmail.com,
	 *				'gender' => 'Mlle', // M. Mme ou Mlle
	 *				'firstname' => 'Martine',
	 *				'name' => 'Dupont',
	 *				'company' => 'MD Services SARL',
	 *				);	 
     *
	 * 	$aPanier = Array(
	 * 				1 => array(
	 * 					'articleID' => 7895, 
	 *					'label' = 'Service no 1',
	 *					'prix_ht_unit' = 350, 
	 *					'qty' = 1,
	 *					'frais_prod_unit' = 120,
	 *					'reference' = 'S1-80PX-UFZ'
	 * 					),						
	 * 				2 => array(
	 * 					'articleID' => 45, 
	 *					'label' = 'Boites en plastique 100x30x30', 
	 *					'prix_ht_unit' = 5, 
	 *					'qty' = 15,
	 *					'frais_prod_unit' = 2, 
	 *					'reference' = 'S1-80PX-UFZ'
	 * 					),
	 * 				);						
	 */ 

	public function sendOrder($aCaisse, $aCustomer, $aPanier=array()) {
		if(empty($aPanier))
			$aPanier[0] = Array ('label' => 'empty', 'articleID' => 0, 'prix_ht_unit' => 0,'qty' => 0);
		$aOrder = $this->_flatten_GP_array(Array('caisse' => array_merge($aCaisse,$aCustomer), 'panier' => $aPanier));
		$this->sendContact($aCustomer);
		$this->_sFunction = 'order';
		$this->_HttpPost($aOrder);
	}

	/* Transmet les données correspondant à une transaction financière validée.
	 * 
	 * Champs obligatoires :
	 * $aCcaisse['amount']
	 * $aCaisse['customOrderID']
	 * $aCustomer['email'] sauf si celle ci a déjà été transmise avec sendOrder
	 * 
	 * 
	 *
	 * Les autres champs sont recommandés  - voir les instructions détaillées
	 * 
	 * Exemple 
	 * 	$aCaisse = Array(
	 *				'customOrderID' => 645674,
	 *				'mode' => 'paypal',  
	 *				'validationID' => '2013hyx9d54gdz6obs654gjkwngh987',
	 *				'amount'=> 596.3,
	 *				'currency' => 'EUR',
	 *				'frais_banc' => 8,
	 *				'frais_port' => 0,
	 *				'frais_emb' => 0,
	 *				'frais_prod' => 0,
	 *				'frais_adm' => 2
	 *				);
	 *
	 * 	$aCustomer = Array(
	 *				'email' => martin.dupont@gmail.com,
	 *				'gender' => 'Mlle', // M. Mme ou Mlle
	 *				'firstname' => 'Martine',
	 *				'name' => 'Dupont',
	 *				'company' => 'MD Services SARL',
	 *				);	
	 *	
	 *	$aPanier = Array(
	 * 				1 => array(
	 * 					'articleID' => 7895, 
	 *					'label' = 'Service no 1',
	 *					'prix_ht_unit' = 350, 
	 *					'qty' = 1,
	 *					'reference' = 'S1-80PX-UFZ',
	 *					'frais_banc_unit' => 0,
	 *					'frais_port_unit' => 0,
	 *					'frais_emb_unit' => 0,
	 *					'frais_prod_unit' => 120,
	 *					'frais_adm_unit' => 0
	 *					),						
	 * 				2 => array(
	 * 					'articleID' => 45, 
	 *					'label' = 'Boites en plastique 100x30x30', 
	 *					'prix_ht_unit' = 5, 
	 *					'qty' = 15,
	 *					'frais_banc_unit' => 0,
	 *					'frais_port_unit' => 1,
	 *					'frais_emb_unit' => 0,
	 *					'frais_prod_unit' => 2,
	 *	 				'frais_adm_unit' => 0
	 *					'reference' = 'S1-80PX-UFZ'
	 *					),
	 *				);
	 */
	 
	public function sendTransaction($aCaisse, $aCustomer=array(), $aPanier=array()) {
		$this->_sFunction = 'transaction';
		$this->_sendTransaction($aCaisse, $aCustomer, $aPanier);
	}
	public function sendOrderValid($aCaisse, $aCustomer=array(), $aPanier=array()) {
		$this->_sFunction = 'ordervalid';
		$this->_sendTransaction($aCaisse, $aCustomer, $aPanier);
	}

	public function sendRefund($aCaisse, $aCustomer=array(), $aPanier=array()) {
		$this->_sFunction = 'refund';
		$this->_sendTransaction($aCaisse, $aCustomer, $aPanier);
	}

	/* Envoi l'adresse postale d'un contact
	 * $aCustomer définit comme dans sendContact()
	 *
	 * Exemple de $aAddress :
	 *
	 *  $aAddress['address'] = '12 rue des petits champs';
     *	$aAddress['city']  = 'Toulon';
	 *	$aAddress['postcode'] = '83000';
	 *	$aAddress['id_state'] = '';
	 *	$aAddress['country'] = 'FR';
	 *	$aAddress['alias'] = 'Bureau';
	 *	$aAddress['company'] = 'e-Genèse';
	 *	$aAddress['lastname'] = 'Palazzi';
	 *	$aAddress['firstname'] = 'Lionel';
	 *	$aAddress['phone'] = '0494506285';
	 *	$aAddress['phone_mobile'] = '0625854265';
	 *
	 * */
	public function sendContactAddress($aCustomer, $aAddress) {
		if(empty($aAddress)) return;
		$aCustomAddress = $this->_flatten_GP_array(Array('customer' => $aCustomer, 'address' => $aAddress));
		$this->_sFunction = 'address';
		$this->_HttpPost($aCustomAddress);	
	}		


	/* Envoi les données de l'acquisition d'un nouveau contact via le code de Tracking CyberMailing
	 * lorsque celui-ci est parâmetré pour fonctionner avec mindinvader.
	 * A utiliser sur les pages de reception
	 */
	public function cybermailingLanding() {
			$this->_sFunction = 'cybermailinglanding';
			$sQuery = 'type==contact';
			$sQuery .= '|cbm_return=='.$_GET[CBM_RETURN_FIELD];
			$this->_HttpTracker($sQuery);
	}



	/****************************************************************************************************
	 *                                  FONCTIONS PRIVEES
	 ****************************************************************************************************/ 

	protected function _sendTransaction($aCaisse, $aCustomer, $aPanier) {
		if(!empty($aCustomer) && empty($aPanier))
			if(!empty($aCustomer['articleID'])) { // on a pas passé $aCustomer mais $aPanier
				$aPanier = $aCustomer;
				$aCustomer = array();
		}	
		if(empty($aPanier))
			$aPanier[0] = Array ('label' => 'empty', 'articleID' => 0, 'prix_ht_unit' => 0,'qty' => 0);
		if(empty($aCaisse['validationID']))
			$aCaisse['validationID'] = md5(MVR_CLIENT_KEY . time());
		$aTransaction = $this->_flatten_GP_array(Array('caisse' => array_merge($aCaisse,$aCustomer), 'panier' => $aPanier));
		$this->_HttpPost($aTransaction);
	}

	/* Tracking lors du surf du visiteur, envois les données et reçoit en retour les instructions
	 * pour mise à jour de cookies, et affichages eventuels interprétés par _callBack
	 */ 
	protected function _HttpTracker($sQuery='type==visit') {
		if(empty($_COOKIE[MVR_COOKIE_NAME .'-session']) || $this->_sFunction != 'visit') {
			$sUrl = URL_MINDINVADER_API .'?'.$sQuery.'|'.$this->_MakeInput().'|version==1.5';
			$this->_sDbug .= '<div class="dbug section action">Query sent to MindIvader => </div><div class="dbug">'.$sUrl.'</div>';
			$sJson = file_get_contents($sUrl); 
			$oMvr = json_decode($sJson);
			$this->_callBack($oMvr);
		} else {
			$this->_sDbug .= '<div class="dbug section action">MindIvader SESSION active => </div><div class="dbug">'.$_COOKIE[MVR_COOKIE_NAME].'</div>';
			$this->_callBack();
		}
	}

	/* Transmet des données à MindInvader en POST
	 */	
	protected function _HttpPost($aPost=array()) {
		$sQuery = 'type=='.$this->_sFunction;
		if(!empty($aPost)) 
			foreach($aPost as $key=>$value) {
				if(!is_array($value))
					$aPost[$key] = rawurlencode($value);
				else {
					foreach($value as $k => $v) {
						if(!is_array($v)) $value[$k] = rawurlencode($v);
						else{
							foreach($v as $ke => $va) {
							$v[$ke] = rawurlencode($va);
							}
						}
					}
				}
			} 
		$bAdmin = true;
		$sUrl = URL_MINDINVADER_API .'?'.$sQuery.'|'.$this->_MakeInput($bAdmin).'|version==1.5';
		$this->_sDbug = '<div class="dbug section action">Query sent to MindIvader => </div><div class="dbug body">'.$sUrl.'
		POST DATA: <pre>'.var_export($aPost,true).'</pre></div>';
		$ch = curl_init($sUrl);
//		print_r($aPost);
		if(!empty($aPost)) {	
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $aPost);
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		$sJson = curl_exec($ch);
		//print_r($sJson);
		$oMvr = json_decode($sJson);
		if(empty($oMvr)) echo $sJson;
		$this->_callBack($oMvr);
	}

	/* creer la chaine des informations qui permet l'identification du client
	 * et éventuellement les paramêtres du visiteur cookie, agent, ip, referer
	 */
	protected function _MakeInput($bAdmin=false) {
		$str = 'MVR_clid=='.$this->_nClientId;
		$str .= '|call_type==server';
		$str .= '|MVR_key=='.$this->_sSiteKey;
		$str .= '|URI=='.urlencode($this->_sUrl);
		if(isset($_GET['MVR_dbug']))
			if(strpos($this->_sUrl,'?') !==false)
				$str .= '&trackv=1';
			else
				$str .= '?trackv=1';
		if(!$bAdmin) {	
			$str .= '|IP=='.$_SERVER['REMOTE_ADDR'];
			$str .= '|MVR_trackid=='.(empty($_COOKIE[MVR_COOKIE_NAME]) ? '' : $_COOKIE[MVR_COOKIE_NAME]);
			$str .= '|MVR_affid=='.(empty($_COOKIE['MVR_affid']) ? '' : $_COOKIE['MVR_affid']);
			$str .= '|REFERER=='.(empty($_SERVER['HTTP_REFERER']) ? '' : urlencode(urldecode($_SERVER['HTTP_REFERER'])));
			$str .= '|USER_AGENT=='.(empty($_SERVER['HTTP_USER_AGENT']) ? '' : urlencode($_SERVER['HTTP_USER_AGENT']));
		} else {
			$str .= '|level==admin';	
		}	
		return $str;
	}
	
	/* Efface le cookie MindInvader
	 */
	protected function _deleteCook() {
		setcookie(MVR_COOKIE_NAME,0);
	} 


	/* Interprete la réponse de mindinvader
	 * Met à jour les cookies
	 * Génère l'affichage de la plateforme d'affiliation
	 */

	protected function _callBack($oMvr=null) {
		$bDbugMode = false;
		if(!empty($this->_sDbug) && isset($_GET['MVR_dbug']) && $_GET['MVR_dbug'] == $this->_sSiteKey)
			$bDbugMode = true;

		if(!empty($oMvr->error) && !$bDbugMode)
			echo 'MindInvader Error: '.$oMvr->error;

		if($bDbugMode && !empty($oMvr)) {
			$aExport = array();
			foreach($oMvr as $sKey => $sValue) {
				if($sKey != 'trackinfo_display')
					$aExport[$sKey] = $sValue;
			}
			$this->_sDbug .= '<div class="dbug section response"> <= Json Response from MindIvader </div><div class="dbug"><pre>'.var_export($aExport,true).'</pre></div>';
		}

		if(!empty($oMvr->cookie_value)) {
			if($bDbugMode)
				$this->_sDbug .= '<div class="dbug section action">Sending tracking cookie</div><div class="dbug">';
			if($_SERVER['SERVER_NAME']=='localhost') {
				$aExp = explode('/',str_replace('/web/','',$this->_aUrl['path']));
				$sCookieFolder='web/'.$aExp[0];
				$bSet = setcookie(MVR_COOKIE_NAME,$oMvr->cookie_value,(time()+3600*24*$oMvr->cookie_days),'/'.$sCookieFolder);
				$this->_sDbug .= 'Name: '.MVR_COOKIE_NAME .' | Domain: localhost | Value: '.$oMvr->cookie_value.' | Validity: '.$oMvr->cookie_days .' days | Folder: /'.$sCookieFolder.' => '. ($bSet ? '<span class="success">Success</span>' : '<span class="fail">FAIL</span>');
				if(isset($oMvr->DPS_actif) && $oMvr->DPS_actif == 0) {
					$bSet1 = setcookie(MVR_COOKIE_NAME .'-session',1,(time()+1800),'/'.$sCookieFolder);
					$this->_sDbug .= '<br/>Name: '.MVR_COOKIE_NAME .'-session | Domain: localhost | Value: 1 | Validity: 1800s | Folder: /'.$sCookieFolder.' => '. ($bSet1 ? '<span class="success">Success</span>' : '<span class="fail">FAIL</span>');
				}
			}
			else
				foreach($oMvr->cookie_domain as $domain) {
					$bSet = setcookie(MVR_COOKIE_NAME,$oMvr->cookie_value,(time()+3600*24*$oMvr->cookie_days),'/',$domain);
					$this->_sDbug .= 'Name: '.MVR_COOKIE_NAME .' | Domain: '.$domain.' | Value: '.$oMvr->cookie_value.' | Validity: '.$oMvr->cookie_days .' days | Folder: / => '. ($bSet ? '<span class="success">Success</span>' : '<span class="fail">FAIL</span>');
					if(isset($oMvr->DPS_actif) && $oMvr->DPS_actif == 0) {
						$bSet1 = setcookie(MVR_COOKIE_NAME .'-session',1,(time()+1800),'/',$domain);
					$this->_sDbug .= '<br/>Name: '.MVR_COOKIE_NAME .'-session | Domain: '.$domain.' | Value: 1 | Validity: 1800s | Folder: / => '. ($bSet1 ? '<span class="success">Success</span>' : '<span class="fail">FAIL</span>');
					}
				}
			$this->_sDbug .= '</div>';
		}
		
		if(!empty($oMvr->egn_affid)) {
			foreach($oMvr->cookie_domain as $domain) {
				setcookie('MVR_affid',$oMvr->egn_affid,(time()+3600*24),'/'.$oMvr->aff_folder,$domain);
			}		
		}
		if(!empty($oMvr->egn_aff_disconnect)) {
			foreach($oMvr->cookie_domain as $domain) {
				setcookie('MVR_affid',$oMvr->egn_affid,0,'/'.$oMvr->aff_folder,$domain);
			}		
		}

		if(!empty($oMvr->trackinfo_display) && $bDbugMode)
			$this->_sDbug .= '<div class="dbug section info">MindInvader Logic Reporting : </div><div class="dbug">'.nl2br($oMvr->trackinfo_display).'</div>';

		if($bDbugMode)
			$this->_echoDBug();

		if(!empty($oMvr->aff_content)) {
				include($_SERVER['DOCUMENT_ROOT'].'/'.$oMvr->aff_folder.'/template/index.php');
			}

		if(!empty($oMvr->DPS))
			foreach($oMvr->DPS as $oDps) {
				if($oDps->type == 'redirect')
					header('Location:'.$oDps->url);
				if($oDps->type == 'include')
					include($oDps->url);
				if($oDps->PHPvar == 1)
					$this->DpsVar = $oDps->php_variables;
		}
		if(!empty($oMvr->redirect))
			header('Location:'.$oMvr->redirect);
		if(!empty($oMvr->htm))
			die($oMvr->htm);		
	}	

	/* Transforme un tableau multidimmensionnel en un tableau à une dimension
	 */	
	protected function _flatten_GP_array(array $var,$prefix = false) {
		$return = array();
		foreach($var as $idx => $value) {
			if(!is_array($value)) {
				if($prefix) 
					$return[$prefix.'['.$idx.']'] = $value;
				else 
					$return[$idx] = $value;
			} else 
				$return = array_merge($return,$this->_flatten_GP_array($value,$prefix ? $prefix.'['.$idx.']' : $idx));
		}
		return $return;
	}

	protected function _echoDBug() {
	echo '<style>	.dbug{padding:5px; font-family: "monospace","sans-serif"; font-size: 80%;  padding:2px;}
					.section{background-color:#000B2C;}
					.body{margin-bottom:10px; background-color:#F1F1F1;}
					.action{color:rgb(195, 195, 255);}
					.response{color:rgb(144, 255, 144);}
					.info{color: #CCC9C9; background-color: #2C0B00;}
					.success{color:green; font-weight:bold;}
					.fail{color:red; font-weight:bold;}
			</style>
			'.$this->_sDbug.'
			<div style="text-align:right"><a target="_blank" href="http://app.mindinvader.com/index.php/site/login"><img src="http://mindinvader.com/images/logo.png"></a></div>';
	}
}
?>
