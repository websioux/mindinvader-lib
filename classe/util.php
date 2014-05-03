<?php
class CMVRUtil {

	function convertCurrencyCode($sValue,$sConvert) {

		$aCurrencies = array(
			array('label'=>'Dollar australien',	'numeric'=>036, 'alphabet'=> 'AUD'),
			array('label'=>'Dollar canadien', 	'numeric'=>124, 'alphabet'=>'CAD'),
			array('label'=>'Yuan chinois', 		'numeric'=>156, 'alphabet'=>'CNY'),
			array('label'=>'Couronne danoise', 	'numeric'=>208, 'alphabet'=>'DKK'),
			array('label'=>'Yen japonais', 		'numeric'=>392, 'alphabet'=>'JPY'),
			array('label'=>'Couronne Norvégienne', 'numeric'=>578, 'alphabet'=>'NOK'),
			array('label'=>'Couronne suédoise', 	'numeric'=>752, 'alphabet'=>'SEK'),
			array('label'=>'Francsuisse', 		'numeric'=>756, 'alphabet'=>'CHF'),
			array('label'=>'Livre sterling', 		'numeric'=>826, 'alphabet'=>'GBP'),
			array('label'=>'Dollar américain', 	'numeric'=>840, 'alphabet'=>'USD'),
			array('label'=>'Euro', 				'numeric'=>978, 'alphabet'=>'EUR')
	    );

		switch($sConvert) {
			case 'alphabet' : // num given return alphabet
				foreach($aCurrencies as $aCurrency) {
					if($aCurrency['numeric'] == $sValue) return $aCurrency['alphabet'];
				}

			case 'numeric' : // alphabet given return num
				foreach($aCurrencies as $aCurrency) {
					if($aCurrency['alphabet'] == $sValue) return $aCurrency['numeric'];
				}

			case 'label' : // alphabet or num given return label
				foreach($aCurrencies as $aCurrency) {
					if($aCurrency['alphabet'] == $sValue) return $aCurrency['label'];
					if($aCurrency['numeric'] == $sValue) return $aCurrency['label'];
				}
		}
	
	}
	
}
?>
