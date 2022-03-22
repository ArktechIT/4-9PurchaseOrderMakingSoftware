<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include("Templates/mysqliConnection.php");
	ini_set("display_errors","on");
	
	function sendMail($from,$fromName,$subject,$bodyText,$destinationAddressArray = array(),$attachPathFile = '',$attachFile = '',$ccAddressArray = array(),$attachPathFile2 = '',$attachFile2 = '')
	{
		require_once("PHP Modules/phpmailer/class.phpmailer.php");
		
		$email = new PHPMailer();
		
		// $account = "purchasing2@arktech.co.jp";
		// $password = "p6183819";
		$account = 'jesicca-ramirez@arktech.co.jp';
		$password = 'j0599331';
		
		$email->IsSMTP();
		$email->CharSet = 'UTF-8';
		$email->Host = gethostbyname('arktech.co.jp');
		$email->SMTPDebug = 0;//1 = errors and messages, 2 = messages only, 0-dontshow
		$email->SMTPAuth= true;
		$email->Port = 587;
		$email->Username= $account;
		$email->Password= $password;
		$email->SMTPSecure = 'tls';
		
		$email->From      = $from;//'you@example.com'
		$email->FromName  = $fromName;//'Your Name'
		$email->Subject   = $subject;//'Message Subject'
		$email->isHTML(true);
		$email->Body      = $bodyText;
		
		if(count($destinationAddressArray) > 0)
		{
			foreach($destinationAddressArray as $eAddress)
			{
				$email->AddAddress($eAddress);
			}
			
			if($attachPathFile!='' AND $attachFile!='')
			{
				$email->AddAttachment($attachPathFile,$attachFile);
			}
			if($attachPathFile2!='' AND $attachFile2!='')
			{
				$email->AddAttachment($attachPathFile2,$attachFile2);
			}
			if(count($ccAddressArray) > 0)
			{
				foreach($ccAddressArray as $ccEAddress)
				{
					$email->AddCC($ccEAddress);
				}
			}
			
			if(!$email->Send())
			{
				return "Error sending: " . $mail->ErrorInfo;
			}
		}
		else
		{
			return "Error!";
		}
	}
	
	$poNumber = $_GET['poNumber'];
	
	$supplierId = $supplierType = $poIncharge = '';
	//$sql = "SELECT supplierId, supplierType, poIncharge FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' AND poStatus = 0 LIMIT 1";
	$sql = "SELECT supplierId, supplierType, poIncharge, supplierAlias FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
		$supplierId = $resultPodetailsNew['supplierId'];
		$supplierType = $resultPodetailsNew['supplierType'];
		$poIncharge = $resultPodetailsNew['poIncharge'];
      	$supplierAliasRose = $resultPodetailsNew['supplierAlias'];
	}
	
	$inCharge = 'Arktech Philippines Inc.';
	$sql = "SELECT firstName, surName FROM hr_employee WHERE idNumber LIKE '".$poIncharge."' LIMIT 1";
	$queryEmployee = $db->query($sql);
	if($queryEmployee AND $queryEmployee->num_rows > 0)
	{
		$resultEmployee = $queryEmployee->fetch_assoc();
		$inCharge = $resultEmployee['firstName']." ".$resultEmployee['surName'];
	}
	
	$sql = '';
	if($supplierType==1)
	{
		$sql = "SELECT email FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	}
	else if($supplierType==2)
	{
		$sql = "SELECT subconEmail FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
	}
	if($sql!='')
	{
		$querySupplierEmail = $db->query($sql);
		if($querySupplierEmail AND $querySupplierEmail->num_rows > 0)
		{
			$resultSupplierEmail = $querySupplierEmail->fetch_array();
			$emailString = $resultSupplierEmail[0];
			
			if(trim($emailString)!='')
			{
				$emailArray = explode(";",$emailString);
				
				$emailArray = array_map('trim',$emailArray);
				$emailArray = array_values(array_unique(array_filter($emailArray)));
			}
		}
	}
	
	$attachFile = "PO".$poNumber.".pdf";
	
	echo "<br>".$msg = "
		Dear Sir/Madam,<br><br>
		Good day.<br>
		<p>
			Please see attached PO".$poNumber." and its Tag.<br>
			Kindly confirm upon receipt.<br>
			Thank you
		</p>
		<br>
		Regards<br>
		".$inCharge."		
		<br>Purchasing Departmentxx
		<br>Arktech Philippines, Inc.
		<br>Lot 6B, Phase - 1A,
		<br>First Philippine Industrial Park,
		<br>Sto. Tomas, Batangas
		<br>Tel. No. : (043) 405-6140 - 6144
		<br>Cel. No.: 09176363768
		";
	// for($x=0;$x<count($emailArray);$x++)
	// {
	// echo "<br>Email:".$emailArray[$x]."<br>";
	// }
	$from = "jesicca-ramirez@arktech.co.jp";
	$fromName = "API PMS Automatic Email";
	$subject = "Automated Email: Purchase Order ".$poNumber." - ".$supplierAliasRose;
	$bodyText = $msg;
	$destinationAddressArray[] = "ace@arktech.co.jp";
	$destinationAddressArray[] = "rosemie@arktech.co.jp";
	
	
		$ccAddressArray = array();
		$ccAddressArray[] = "kim@arktech.co.jp";
		$ccAddressArray[] = "jesicca-ramirez@arktech.co.jp";
		//$ccAddressArray[] = "ariel@arktech.co.jp";
		
	//kim@arktech.co.jp; jesicca-ramirez@arktech.co.jp
	// if($_GET['country']==2) $destinationAddressArray = $emailArray;
	// if($_SESSION['idNumber']=='0280') $destinationAddressArray = $emailArray;//Temporary
	//~ $destinationAddressArray[] = "purchasing2@arktech.co.jp";
	$attachPathFile = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/AutoEmail/".$poNumber.".pdf";
	
	if($supplierType==2)
	{
	$attachPathFile2 = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/AutoEmail/".$poNumber.".zip";
	$attachFile2 = "PO".$poNumber.".zip";
	}
	$checkError = sendMail($from,$fromName,$subject,$bodyText,$destinationAddressArray,$attachPathFile,$attachFile,$ccAddressArray,$attachPathFile2,$attachFile2);
	
	 $time = date('G:i');
	 echo "<BR>".$supplierAliasRose."~~".$time."!!!!<BR>";
	if($checkError!='')
	{      
      echo "<BR>ERROR".$time."!!!!<BR>";
		?>
		<!--
		<script>
			alert("<?php echo $checkError;?>");
			parent.location.href = 'gerald_purchaseOrderStatus.php?poNumber=<?php echo $poNumber;?>';
		</script>
		-->
		<?php
	}
	else
	{
		unlink($attachPathFile);
		//UPDATE purchasing_podetailsnew SET emailDate = now() WHERE poNumber LIKE '".$poNumber."';
		$sqlUpdate = "UPDATE purchasing_podetailsnew SET emailDate = now() WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
		$queryUpdateDate = $db->query($sqlUpdate);
		//echo "ok";
		header('location:alfa_purchaseAuto.php');
	}
?>
