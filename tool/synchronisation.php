<?
/* Ce script est conçu pour être utilisé une seule fois
 * son role est de récupérer la table customer (et eventuellement la table prospect)
 * pour la transmettre à mindinvader afin que ce dernier sache reconnaitre les anciens
 * contacts des nouveaux.
 *
 * Les requêtes sql devront probablement être modifiées pour récupérer les bonnes données.
 * En modifiant les requêtes conservez les aliases des données (as...) afin que les données
 * extraites soient stockées dans la bons tableaux.
 *
 * */

if(!defined('URL_MINDINVADER_API'))
	require_once('../setting.php');
require_once('../classe/util.php');
require_once('../classe/mindinvader.php');
require_once('../classe/transaction.php');
$aDoNotTrackVisitPaths = array($_SERVER['PHP_SELF']);
$oMindInvader = new CMindInvader(); 	

$bAddress = false; // true pour transmettre aussi l'adresse postale

// connection client database
$sMysqlHost = '127.0.0.1';
$sMysqlDatabase = 'clientdatabase';
$sMysqlUser = 'user';
$sMysqlPwd = 'password';
try {
	$pdo = new PDO("mysql:host=$sMysqlHost;dbname=$sMysqlDatabase", $sMysqlUser, $sMysqlPwd);
}
catch (PDOException $e) {
    die('Erreur !: ' . $e->getMessage());
}

// check that we can read / write on file
$oFile = fopen('test.txt','w');
if(!$oFile)
	die('ERROR : can not create file in folder');
if(!fwrite($oFile,'ok'))
	die('ERROR : can not write to file in folder');
fclose($oFile);
$sStr = file_get_contents('test.txt');
if($sStr != 'ok')	
	die('ERROR : can not read file in folder');
echo $sStr;
unlink('test.txt');
	
do {
	/* gestion du déroulement du processus de synchronisation.
	 * permet de savoir "où on en est" à chaque itération ou en cas de crash
	 * à l'aide d'un sauvegarde de l'état du déroulement dans un fichier.
	 */
	
	if(file_exists('syncstatus.txt'))
		$sSyncStatus = file_get_contents('syncstatus.txt');
	else {
		$sSyncStatus = 'start';
	}
	$oFile = fopen('syncstatus.txt','w');
	if ($sSyncStatus == 'start') {	
		$nStartId = 0;
		$sProcessName = 'client'; 
		$sQuery = "SELECT MAX(customer_id) as id_client_max FROM customer_table"; // récupère le id max des clients
		$oRes = $pdo->query($sQuery);
		$aRow = $oRes->fetch(PDO::FETCH_ASSOC);
		$nMaxId = $aRow['id_client_max'];
	} else {
		list($sProcessName, $nStartId,$nMaxId) = explode('-', $sSyncStatus);
			if($nStartId >= $nMaxId) {
				if($sProcessName == 'prospect')
					$sSyncStatus = 'completed';
				if($sProcessName == 'client') {
					$nStartId = 0;
					$sProcessName = 'prospect'; 
					$sQuery = "SELECT MAX(prospect_id) as id_prospect_max FROM prospect_table"; // récupère le id max des commandes traitées
					$oRes = $pdo->query($sQuery);
					$aRow = $oRes->fetch(PDO::FETCH_ASSOC);
					$nMaxId = $aRow['id_prospect_max'];
				}
			}	
		
	}

	/* Execution du Processus de  Syncrhonisation
	 *
	 */

	switch($sProcessName) {

		case  'client' :
			$sQuery = "SELECT customer_id as id,
							 customer_date_add as date_add,
							 customer_gender as gender,
							 customer_email as email,
							 customer_lastname as name,
							 customer_firstnale as firstname,
							 customer_company as company
					  FROM customer_table
					  WHERE customer_id > $nStartId
					  ORDER BY customer_id ASC
					  LIMIT 0,100";
			$oRes = $pdo->query($sQuery);
			if($oRes->rowCount()>0)
				while($aCustomer = $oRes->fetch(PDO::FETCH_ASSOC)){
					$oMindInvader->sendContact($aCustomer,$aCustomer['date_add'],'synchronize'); // envoi du contact à mindivader
					echo '<br/>Processing Client : '.$aCustomer['id'].'('.$nMaxId.') '.$aCustomer['email'];
					if($bAddress) { // OPTION pour passer aussi l'adresse postale et le tel si on utilise $bAddresss = true;
						$sQuery = "SELECT customer_address_alias as alias,
										customer_address_firstname as firstname
										customer_address_lastname as lastname
										customer_address_company as company
										customer_address_address as address
										customer_address_postcode as postcode
										customer_address_town as city
										customer_address_country as country
										customer_address_phone as phone
										customer_address_phone2 as phone_modbile
						FROM customer_addresses
						WHERE customer_id = ".$aCustomer['id'];
						$oRes = $pdo->query($sQuery);
						while($aAddress = $oRes->fetch(PDO::FETCH_ASSOC)){
							$oMindInvader->sendContactAddress($aCustomer,$aAddress);
						}
					}
					fwrite($oFile,'client-'.$aCustomer['id'].$nMaxId);
					usleep(100000); // temporise la boucle pendant 100 ms
				}
			break;

		case  'prospect' :
			$sQuery = "SELECT prospect_id as id,
							 prospect_date_add as date_add,
							 prospect_gender as gender,
							 prospect_email as email,
							 prospect_lastname as name,
							 prospect_firstnale as firstname,
							 prospect_company as company
					  FROM prospect_table
					  WHERE prospect_id > $nStartId
					  ORDER BY prospect_id ASC
					  LIMIT 0,100";
			$oRes = $pdo->query($sQuery);
			if($oRes->rowCount()>0)
				while($aCustomer = $oRes->fetch(PDO::FETCH_ASSOC)){
					$oMindInvader->sendContact($aCustomer,$aCustomer['date_add'],'synchronize'); // envoi du contact à mindinvader
					echo '<br/>Processing Prospect : '.$aCustomer['id'].'('.$nMaxId.') '.$aCustomer['email'];
					if($bAddress) { // OPTION pour passer aussi l'adresse postale et le tel si on utilise $bAddresss = true;
						$sQuery = "SELECT prospect_address_alias as alias,
									prospect_address_firstname as firstname
									prospect_address_lastname as lastname
									prospect_address_company as company
									prospect_address_address as address
									prospect_address_postcode as postcode
									prospect_address_town as city
									prospect_address_country as country
									prospect_address_phone as phone
									prospect_address_phone2 as phone_modbile
						FROM prospect_addresses
						WHERE prospect_id = ".$aCustomer['id'];
						$oRes = $pdo->query($sQuery);
						if($oRes->rowCount()>0)
							while($aAddress = $oRes->fetch(PDO::FETCH_ASSOC)){
								$oMindInvader->sendContactAddress($aCustomer,$aAddress);
							}
				}
				fwrite($oFile,'prospect-'.$aCustomer['id'].$nMaxId);
				usleep(100000); // temporise la boucle pendant 100 ms
				}
			break;
		}
	fclose($oFile);		
} while($nSyncStatus != 'completed');
echo 'SUCCESS Syncronisation Completed'
?>


