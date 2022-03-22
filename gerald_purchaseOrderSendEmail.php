<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include("PHP Modules/mysqliConnection.php");
	ini_set("display_errors","on");
	
	function sendMail($from,$fromName,$subject,$bodyText,$destinationAddressArray = array(),$attachPathFile = '',$attachFile = '')
	{
		require_once("PHP Modules/phpmailer/class.phpmailer.php");
		
		$email = new PHPMailer();

		$account = "purchasing2@arktech.co.jp";
		$password = "p6183819";
		
		$email->IsSMTP();
		$email->CharSet = 'UTF-8';
		$email->Host = gethostbyname('arktech.co.jp');
		$email->SMTPDebug = 2;
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
	$sql = "SELECT supplierId, supplierType, poIncharge FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' AND poStatus = 0 LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
		$supplierId = $resultPodetailsNew['supplierId'];
		$supplierType = $resultPodetailsNew['supplierType'];
		$poIncharge = $resultPodetailsNew['poIncharge'];
	}
	
	$inCharge = 'Arktech Philippines Inc.';
	$sql = "SELECT firstName FROM hr_employee WHERE idNumber LIKE '".$poIncharge."' LIMIT 1";
	$queryEmployee = $db->query($sql);
	if($queryEmployee AND $queryEmployee->num_rows > 0)
	{
		$resultEmployee = $queryEmployee->fetch_assoc();
		$inCharge = $resultEmployee['firstName'];
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
			Please see attached PO".$poNumber."<br>
			Kindly confirm upon receipt.<br>
			Thank you
		</p>
		<br>
		Regards<br>
		".$inCharge."
		";
	
	$from = "purchasing2@arktech.co.jp";
	$fromName = "API PMS Automatic Email";
	$subject = "Automated Email: Purchase Order ".$poNumber;
	$bodyText = $msg;
	//~ $destinationAddressArray[] = "ace@arktech.co.jp";
	//~ $destinationAddressArray[] = "rosemie@arktech.co.jp";
	if($_GET['country']==2) $destinationAddressArray = $emailArray;
	if($_SESSION['idNumber']=='0280') $destinationAddressArray = $emailArray;//Temporary
	$destinationAddressArray[] = "purchasing2@arktech.co.jp";
	$attachPathFile = $_SERVER['DOCUMENT_ROOT']."/V3/4-9 Purchase Order Making Software/Email Attachment/".$poNumber.".pdf";
	
	$checkError = sendMail($from,$fromName,$subject,$bodyText,$destinationAddressArray,$attachPathFile,$attachFile);
	
	if($checkError!='')
	{
		?>
		<script>
			alert("<?php echo $checkError;?>");
			parent.location.href = 'gerald_purchaseOrderStatus.php?poNumber=<?php echo $poNumber;?>';
		</script>
		<?php
	}
	else
	{
		unlink($attachPathFile);
		header('location:gerald_purchaseOrderMakingSql.php?finish=1&poNumber='.$poNumber);
	}
?>
