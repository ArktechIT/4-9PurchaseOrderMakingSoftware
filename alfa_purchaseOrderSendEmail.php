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
		$email->SMTPDebug = 1;//1 = errors and messages, 2 = messages only, 0-dontshow
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
			return "Error no destination!";
		}
	}
	
	$poNumber = $_GET['poNumber'];
	
	$supplierId = $supplierType = $poIncharge = '';
	//$sql = "SELECT supplierId, supplierType, poIncharge FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' AND poStatus = 0 LIMIT 1";
	//$sql = "SELECT supplierId, supplierType, poIncharge, supplierAlias FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1"; // update code 2021-07-07
	$sql = "SELECT supplierId, supplierType, poIncharge, supplierAlias FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' and emailDate like '0000-00-00 00:00:00' LIMIT 1";
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
	$emailArray = array();
	$emailArray2 = array();
	if($supplierType==1)
	{
		$sql = "SELECT email,purchasingArkCC FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	}
	else if($supplierType==2)
	{
		$sql = "SELECT subconEmail,purchasingArkCC FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
	}
	if($sql!='')
	{
		$querySupplierEmail = $db->query($sql);
		if($querySupplierEmail AND $querySupplierEmail->num_rows > 0)
		{
			$resultSupplierEmail = $querySupplierEmail->fetch_array();
			$emailString = $resultSupplierEmail[0];
			$emailStringCC = $resultSupplierEmail[1];
			if(trim($emailString)!='')
			{
				$emailArray = explode(";",$emailString);
				
				$emailArray = array_map('trim',$emailArray);
				$emailArray = array_values(array_unique(array_filter($emailArray)));
				
				$emailArray2 = explode(";",$emailStringCC);
				
				$emailArray2 = array_map('trim',$emailArray2);
				$emailArray2 = array_values(array_unique(array_filter($emailArray2)));
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
		<br>Purchasing Department
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
	$fromName = "API Email";
	$subject = "Arktech Email: Purchase Order ".$poNumber." - ".$supplierAliasRose;
	$bodyText = $msg;
	//$destinationAddressArray[] = "ace@arktech.co.jp";
	//$destinationAddressArray[] = "rosemie@arktech.co.jp";
	$destinationAddressArray = $emailArray;
	
		$ccAddressArray = array();
		$ccAddressArray = $emailArray2;
		//$ccAddressArray[] = "kim@arktech.co.jp";
		$ccAddressArray[] = "jesicca-ramirez@arktech.co.jp";
		$ccAddressArray[] = "ariel@arktech.co.jp";
		$ccAddressArray[] = "m.sumaya@arktech.co.jp";
		//$ccAddressArray[] = "ace@arktech.co.jp";
		$ccAddressArray[] = "rosemie@arktech.co.jp";
		
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
	 //echo "<BR>".$supplierAliasRose."~~".$time."!!!!<BR>";
	if($checkError!='')
	{      
      echo "<BR>ERROR".$time."!!!!<BR>";
      echo $checkError;
      echo "<br>".$destinationAddressArray[0];
      echo "<br>".$destinationAddressArray[1];
      echo "<br>".$destinationAddressArray[2];
      echo "<br>".$destinationAddressArray[3];
      echo "<br>".$destinationAddressArray[4];
      echo "<br>".$destinationAddressArray[5];
		?>
		<!--
		<script>
			alert("<?php echo $checkError;?>");
			parent.location.href = 'gerald_purchaseOrderStatus.php?poNumber=<?php echo $poNumber;?>';
		</script>
		-->
		<?php
		//insert notification to head and IT start
			// $sqlAlarm = "INSERT INTO system_notificationdetails (notificationDetail,notificationLink,notificationKey,notificationType) 
			// VALUES ('Alarm! an error accured while sendinf email', '/".$ver."/4-9 Purchase Order Making Software/gerald_purchaseOrderConverterV2.php?poNumber=', '".$poNumber."', '34')";
			// $query1 = $db->query($sqlAlarm);
		//insert notification to head and IT start
		  $sql = "INSERT INTO system_notificationdetails (notificationDetail,notificationLink,notificationKey,notificationType) 
		  VALUES ('Alarm! an error accured while sending email', '/".$ver."/4-9 Purchase Order Making Software/gerald_purchaseOrderConverterV2.php?poNumber=', '".$poNumber."', '34')";
		  $query1 = $db->query($sql);
			$sqlNotif = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey LIKE '".$poNumber."' and notificationType = 34";
			$queryNotif = $db->query($sqlNotif);
			if($queryNotif AND $queryNotif->num_rows > 0)
			{
				$resultNotif = $queryNotif->fetch_assoc();
				$notificationId = $resultNotif['notificationId'];
			}
		  $sql = "INSERT INTO system_notification (notificationId, notificationTarget, notificationStatus, targetType)
		  VALUES (".$notificationId.", '0276', '0', '2')";
		  $queryNoftif = $db->query($sql);
		  $sql = "INSERT INTO system_notification (notificationId, notificationTarget, notificationStatus, targetType)
		  VALUES (".$notificationId.", '0280', '0', '2')";
		  $queryNoftif = $db->query($sql);
	}
	else
	{
		unlink($attachPathFile);
		unlink($attachPathFile2);
		
		if($supplierType==2)
		{
			//for($yy=1;$yy<10;$yy++)
			for($yy=1;$yy<30;$yy++)
			{
				$attachFileB2 = "".$poNumber."_".$yy.".zip";
				$attachPathFileB2 = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/AutoEmail/".$attachFileB2;
				
				if($supplierType==2 and file_exists($attachPathFileB2))
				{
					$subject2 = "Arktech Email (continuation-".($yy+1)."): Purchase Order ".$poNumber." - ".$supplierAliasRose;
					$bodyText2 ="Dear Sir/Madam,<br><br>	Good day.<br><p>Please see attached drawings.<br><br>Thank you</p><br>Regards<br>".$inCharge."<br>Purchasing Department<br>Arktech Philippines, Inc.<br>Lot 6B, Phase - 1A,<br>First Philippine Industrial Park,<br>Sto. Tomas, Batangas<br>Tel. No. : (043) 405-6140 - 6144<br>Cel. No.: 09176363768";
					$checkError2 = sendMail($from,$fromName,$subject2,$bodyText2,$destinationAddressArray,"","",$ccAddressArray,$attachPathFileB2,$attachFileB2);
					if($checkError2!='')
					{
						//insert notification to head and IT start
						  $sql = "INSERT INTO system_notificationdetails (notificationDetail,notificationLink,notificationKey,notificationType) 
						  VALUES ('Alarm! an error accured while sending email (continuation-".($yy+1).")', '/".$ver."/4-9 Purchase Order Making Software/gerald_purchaseOrderConverterV2.php?poNumber=', '".$poNumber."', '34')";
						  $query1 = $db->query($sql);
							$sqlNotif = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey LIKE '".$poNumber."' and notificationType = 34";
							$queryNotif = $db->query($sqlNotif);
							if($queryNotif AND $queryNotif->num_rows > 0)
							{
								$resultNotif = $queryNotif->fetch_assoc();
								$notificationId = $resultNotif['notificationId'];
							}
						  $sql = "INSERT INTO system_notification (notificationId, notificationTarget, notificationStatus, targetType)
						  VALUES (".$notificationId.", '0276', '0', '2')";
						  $queryNoftif = $db->query($sql);
						  $sql = "INSERT INTO system_notification (notificationId, notificationTarget, notificationStatus, targetType)
						  VALUES (".$notificationId.", '0280', '0', '2')";
						  $queryNoftif = $db->query($sql);
						//insert notification to head and IT start
					}
					unlink($attachPathFileB2);
					
				}
			}
			
		}
		
		//UPDATE purchasing_podetailsnew SET emailDate = now() WHERE poNumber LIKE '".$poNumber."';
		$sqlUpdate = "UPDATE purchasing_podetailsnew SET emailDate = now() WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
		$queryUpdateDate = $db->query($sqlUpdate);
		//echo "ok";
		header('location:alfa_purchaseAuto.php');
	}
?>
