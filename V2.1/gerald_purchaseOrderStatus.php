<?php
	include('rose_purchaseOrderMakingSql.php');
	include('rose_purchaseOrderSendEmail.php');
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include("PHP Modules/mysqliConnection.php");
	include("PHP Modules/gerald_functions.php");
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	if(isset($_GET['cancel']))
	{
		$sql = "UPDATE purchasing_forpurchaseorder SET idNumber = '' WHERE idNumber = '".$_SESSION['idNumber']."'";
		$queryUpdate = $db->query($sql);

		header('location:gerald_purchaseOrderMakingSummary.php');
		exit(0);
	}
	if(isset($_POST['editFlag']))
	{
		?>
		<form action=''></form>
		<table>
			<tr>
				<th><?php echo displayText('L636');?></th>
				<td><textarea name='remarks' rows='4' id='remarks' ></textarea></td>
			</tr>
			<tr>
				<th colspan='2'><input type='submit' style='width:100%;' value='UPDATE' form='selfFormId'></th>
			</tr>
		</table>
		<?php
		
		exit(0);
	}

	function generateCodeNew($textValue,$prefix,$textLength)
	{
		$zeroCount = $textLength - strlen($textValue);
		$text = $prefix;
		while($zeroCount > 0)
		{
			$text .= "0";
			$zeroCount--;
		}
		$text .= $textValue;
		
		return $text;
	}
	
	$poNumber = (isset($_POST['poNumber'])) ? $_POST['poNumber'] : '';
	$idNumber = $_SESSION['idNumber'];
	$formCheck = (isset($_POST['formCheck'])) ? $_POST['formCheck'] : "";
	$poRemarks = (isset($_POST['poRemarks'])) ? $_POST['poRemarks'] : "";
	
	//~ if($_SESSION['idNumber']=='0346')
	//~ {
		if($poRemarks!='')
		{
			$_SESSION['poRemarks'] = $poRemarks;
		}
		else
		{
			unset($_SESSION['poRemarks']);
		}
	//~ }
	
	if(isset($_POST['key']) AND $_POST['key']!='')
	{
		$updateKeyz = $_POST['key'];
		$updatePONumz = $poNumber;
		$emailValue = "";
		purchasingInsertOrders($updateKeyz,$updatePONumz,$emailValue,$poRemarks);
		//~ if($_SESSION['idNumber']=='0346') exit(0);
		purchasingInsertTMP($updatePONumz);
		
		$count = 0;
		echo "<form action='' method='post' id='selfFormId'></form>";
		if(count($formCheck) > 0)
		{
			foreach($formCheck as $value)
			{
				if($value!=$updateKeyz)
				{
					echo "<input type='hidden' name='formCheck[]' value='".$value."' form='selfFormId'>";
					$count++;
				}
			}
		}
		if($count > 0)
		{
			?>
			<script>
				document.getElementById("selfFormId").submit();
			</script>
			<?php
		}
		else
		{
			header('location:gerald_purchaseOrderMakingSummary.php');
		}
		exit(0);
	}
	
	$key = $formCheck[0];
	
	$dataExplode=explode("`",$key);
	$dataExplode2=explode("-",$dataExplode[1]);
	$supplierId=$dataExplode[0];			
	$supplierType=$dataExplode2[0];
	$currency=$dataExplode2[1];	
	$manualFlag=(isset($dataExplode2[2])) ? $dataExplode2[2] : 0;
	
	$supplierAlias = $shipment = $terms = '';
	$sql = "SELECT supplierAlias, shipment, terms FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	if($supplierType==2)	$sql = "SELECT subconAlias, shipment, terms FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
	$querySupplier = $db->query($sql);
	if($querySupplier AND $querySupplier->num_rows > 0)
	{
		$resultSupplier = $querySupplier->fetch_row();
		$supplierAlias = $resultSupplier[0];
		$shipment = $resultSupplier[1];
		$shipmentType = $resultSupplier[1];
		$terms = $resultSupplier[2];
	}	
	
	$latestPoNoOfficial = "";
	if($poNumber=='')
	{
		$latestPoNo = '';
		if($_GET['country']==2)
		{
			$supplierAliasLen = strlen($supplierAlias);
			$yNLen = strlen(date('yn'));
			
			$maxNumber = 0;
			//~ $sql = "SELECT  CAST(SUBSTRING(poNumber,".($supplierAliasLen+$yNLen)."+1) AS SIGNED) AS number FROM purchasing_podetailsnew WHERE poNumber LIKE '".$supplierAlias."%' AND poIssueDate >= '2017-10-01' ORDER BY `number` DESC LIMIT 1";
			$sql = "SELECT  CAST(SUBSTRING(poNumber,".($supplierAliasLen+$yNLen)."+1) AS SIGNED) AS number FROM purchasing_podetailsnew WHERE poNumber LIKE '".$supplierAlias."%' AND poIssueDate >= '".date('Y-m-01')."' ORDER BY `number` DESC LIMIT 1";
			$queryCheckMaxPo = $db->query($sql);
			if($queryCheckMaxPo AND $queryCheckMaxPo->num_rows > 0)
			{
				$resultCheckMaxPo = $queryCheckMaxPo->fetch_assoc();
				$maxNumber = $resultCheckMaxPo['number'];
			}
			$maxNumber++;
			$numberCount = (strlen($maxNumber) > 3) ? strlen($maxNumber) : 3;
			$latestPoNoOfficial = $latestPoNo = generateCodeNew($maxNumber,strtoupper($supplierAlias).date('yn'),$numberCount);
		}
		else
		{
			$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` ORDER BY `number` DESC LIMIT 1";
			$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` WHERE poNumber != '0010651' ORDER BY `number` DESC LIMIT 1";
			$queryCheckMaxPo = $db->query($sql);
			if($queryCheckMaxPo AND $queryCheckMaxPo->num_rows > 0)
			{
				$resultCheckMaxPo = $queryCheckMaxPo->fetch_assoc();
				$latestPoNoOfficial = $latestPoNo = generateCode(($resultCheckMaxPo['number']+1),'',7);
			}
			
			$sql = "SELECT SUBSTRING_INDEX(poNumber,'-',1) as mainPO, SUBSTRING_INDEX(poNumber,'-',-1) as subPO FROM purchasing_podetailsnew WHERE poNumber LIKE '%-%' AND supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$currency." AND poStatus!= 2 ORDER BY subPO DESC LIMIT 1";
			$queryNextPO = $db->query($sql);
			if($queryNextPO AND $queryNextPO->num_rows > 0)
			{
				$resultNextPO = $queryNextPO->fetch_assoc();
				$mainPO = $resultNextPO['mainPO'];
				$subPO = $resultNextPO['subPO']+1;
				$latestPoNo = $mainPO."-".$subPO;
			}
			else
			{
				$latestPoNo .= "-1";
			}
		}
		
		
		$poNumber=$latestPoNo;	
	}
	
	$converterLink = ($_GET['country']==2) ? "rose_purchaseOrderConverterJapan.php?key=".$key."&poNumber=".$poNumber."&saveFile=1" : "gerald_purchaseOrderConverterV2.php?key=".$key."&poNumber=".$poNumber."";
	//~ $converterLink = "gerald_purchaseOrderConverter.php";
	
	$statusName = displayText('L1198');
	
?>

	<!DOCTYPE html>
	<html>
	<?php
		if($_GET['country']==1)
		{
			?>
			<title><?php echo displayText('L1195');?> <?php echo $statusName;?></title>
			<?php
		}
		else if($_GET['country']==2)
		{
			?>
			<title><?php echo $statusName;?> <?php echo displayText('L1195');?></title>
			<?php
		}	
	?>

	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/w3css/w3.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
	<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>
	<body>
		<form action='' method='post' id='selfFormId'></form>
		<input type='hidden' id='poNumber' name='poNumber' value='<?php echo $poNumber;?>' form='selfFormId'>
		<input type='hidden' id='key' name='key' value='' form='selfFormId'>
		<textarea style='visibility:hidden;height:1px;' name='poRemarks' id='poRemarks' form='selfFormId'><?php echo $poRemarks;?></textarea>
		<?php
			if(is_array($formCheck) AND count($formCheck) > 0)
			{
				foreach($formCheck as $value)
				{
					echo "<input type='hidden' name='formCheck[]' value='".$value."' form='selfFormId'>";
				}
			}
		?>
		<div class="designForm w3-card-4" style='width:98%;font-size:2.28vh;margin: 1%;margin-top: 1%;'>
			<div class="w3-container w3-pale-red">
				<?php
					if($notificationId=='')
					{
						if($manualFlag==1)
						{
							?><button style='float:left;width:65px;height:60px;' onclick="location.href='gerald_purchaseOrderManualInput.php'"><img src='/Common Data/Templates/images/backIcon(Ge).png' style='max-width:100%;max-height:70%;'><?php echo displayText('L1072');?></button><?php
						}
						else
						{
							?><button style='float:left;width:65px;height:60px;' onclick="location.href='gerald_purchaseOrderMakingSummary.php'"><img src='/Common Data/Templates/images/backIcon(Ge).png' style='max-width:100%;max-height:70%;'><?php echo displayText('L1072');?></button><?php
						}
					}
					else
					{
						?><button style='float:left;width:65px;height:60px;' onclick="location.href='/<?php echo v; ?>/dashboard.php'"><img src='/<?php echo v; ?>/Common Data/Templates/systemImages/homeIcon.png' style='max-width:100%;max-height:70%;'>Home</button><?php
					}
					
					if($_GET['country']==1)
					{
						?>
						<center><h2><?php echo displayText('L1195');?> <?php echo $statusName;?></h2></center>
						<?php
					}
					else if($_GET['country']==2)
					{
						?>
						<center><h2><?php echo $statusName;?> <?php echo displayText('L1195');?></h2></center>
						<?php
					}					
				?>
			</div>

			<form action='<?php echo $converterLink;?>' method='post' target='iframeName' id='formId'></form>
			<input type='hidden' name='preview' value='0' form='formId' required>
			<input type='hidden' id='downloadId' name='download' value='0' form='formId' required>
			
			<div class="w3-container w3-padding-4" style="width:100%;">
				<iframe src='<?php echo $converterLink;?>' style='width:100%;height:78.5vh;'></iframe>
				<iframe src='' name='iframeName' style='width:100%;height:75vh;display:none;'></iframe>				
				<center>
				<?php

					$showButton = 1;
					$sql = "SELECT idNumber FROM purchasing_forpurchaseorder WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$currency." AND idNumber != '' LIMIT 1";
					$queryForPurchaseOrder = $db->query($sql);
					if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
					{
						$resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc();
						$idNumber = $resultForPurchaseOrder['idNumber'];
						
						$sql = "UPDATE purchasing_forpurchaseorder SET idNumber = '".$idNumber."' WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$currency." AND idNumber = ''";
						$queryUpdate = $db->query($sql);

						if($_SESSION['idNumber']!=$idNumber)
						{
							$employeeName = '';
							$sql = "SELECT CONCAT(firstName,' ',surName) as employeeName FROM hr_employee WHERE idNumber LIKE '".$idNumber."' LIMIT 1";
							$queryEmployee = $db->query($sql);
							if($queryEmployee AND $queryEmployee->num_rows > 0)
							{
								$resultEmployee = $queryEmployee->fetch_assoc();
								$employeeName = $resultEmployee['employeeName'];
							}
	
							echo "<h2>Already started by {$employeeName}</h2>";
	
							$showButton = 0;
						}
					}
					else
					{
						$sql = "UPDATE purchasing_forpurchaseorder SET idNumber = '".$_SESSION['idNumber']."' WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$currency."";
						$queryUpdate = $db->query($sql);
					}

					if($showButton==1)
					{
						if($_GET['country']==1)
						{
							?>
							<input id='editId' type='submit' name='edit' style='width:19%;' value='<?php echo displayText('L1387');//EDIT?>'>
							<!-- <input id='printId' type='submit' name='print' style='width:19%;' value='<?php echo displayText('L1201');//PRINT?>'> -->
							<input id='cancelId' type='submit' name='cancel' style='width:19%;' value='<?php echo displayText('L3189');//Cancel?>'>
							<input id='dlPrintId' type='submit' name='download' style='width:19%;' value='<?php echo displayText('L1202');//DOWNLOAD?>'>
			<!--
							<input id='newPOId' type='submit' name='newpo' style='width:19%;' value='<?php echo displayText('L1203');//NEW PO#?>'>
			-->
	<!--
							<input id='changePOId' type='button' name='changepo' style='width:19%;' value='<?php echo displayText('L1204');//CHANGE PO#?>'>
	-->
							<input id='changeToOfficialPoId' type='button' name='changepo' style='width:19%;' value='<?php echo (strstr($poNumber,'-')!==FALSE) ? "OFFICIAL PO" : "FORECAST PO";//displayText('L1204');//CHANGE PO#?>'>
							<?php
						}

						?>
						<input id='finishId' type='button' name='submit' style='width:19%;' value='<?php echo displayText('L1105');//FINISH?>'>
						<?php
					}
				?>
				</center>
			</div>
		</div>		
	</body>

<script src='/<?php echo v; ?>/Common Data/Templates/jquery.js'></script>
<script>	
	function checkFormValues(formSelector)
	{
		var errorFlag = 0;
		var fields = $(formSelector).serializeArray();
		jQuery.each( fields, function( i, field ) {
			if(field.value.trim()=='')
			{
				alert("Please fill up properly the form!");
				errorFlag = 1;
				return false;
			}
		});
		
		return errorFlag;
	}
	
	$(function(){
		$( "div.designForm" ).find("input:not([type=submit],[type=button],[type=image],[type=radio]),textarea,select").addClass("w3-input w3-border w3-hover-yellow").css({"background-color":"#ffffb3"});
		$( "div.designForm" ).find("input[type=submit],input[type=button]").addClass("w3-btn w3-blue");
		$( "div.designForm" ).find("label").addClass("w3-label w3-text-black");
		
		$("#formId").submit();
		
		$("#editId").click(function(){
			openTinyBox('auto','auto',"<?php echo $_SERVER['PHP_SELF'];?>","editFlag=1");
		});
		
		$(document).on('change','#remarks',function(){
			var remarks = $(this).val();
			$("#poRemarks").val(remarks);
		});
		
		$("#printId").click(function(){
			swal({
				title: '<?php echo displayText('L224');//PO Number?> : <?php echo $poNumber;?>',
				text: '<?php echo displayText('L1206');//Make sure to print this on correct PO?>',
				type: 'info',
				showCancelButton: false,
				allowOutsideClick: false
			}).then(function () {
				window.frames['iframeName'].focus();
				window.frames['iframeName'].print();
			})
		});

		$("#cancelId").click(function(){
			location.href='<?php echo $_SERVER['PHP_SELF'];?>?cancel=1';
		});
		
		$("#dlPrintId").click(function(){
			swal({
				title: '<?php echo displayText('L224');//PO Number?> : <?php echo $poNumber;?>',
				text: '<?php echo displayText('L1206');//Make sure to print this on correct PO?>',
				type: 'info',
				showCancelButton: false,
				allowOutsideClick: false
			}).then(function () {
				$("#downloadId").val(1);
				$("#formId").submit();
			})
		});
		
		$("#changeToOfficialPoId").click(function(){
			var poNumber = ($(this).val()=='OFFICIAL PO') ? "<?php echo $latestPoNoOfficial;?>" : "";
			$("#poNumber").val(poNumber);
			$("#selfFormId").submit();
		});
		
		$("#changePOId,#newPOId").click(function(){
			var changePOType = $(this).attr('id');
			var title = (changePOType=='newPOId') ? 'Cancel PO Number `<?php echo $poNumber;?>` then copy to' : 'Change PO Number `<?php echo $poNumber;?>` to';
			
			swal({
			title: title,
			input: 'text',
			showCancelButton: true,
			confirmButtonText: 'Submit',
			showLoaderOnConfirm: true,
			preConfirm: function (poNumber) {
				return new Promise(function (resolve, reject) {
				setTimeout(function() {
						if(poNumber.trim()!='')
						{
							$.ajax({
								url:'gerald_purchaseOrderMakingSql.php',
								type:'post',
								data:{
									ajaxType:'checkPONumber',
									poNumber:poNumber
								},
								success:function(data){
									if (data.trim() != '') {
										reject(data);
									} else {
										
										resolve();
									}
								}
							});
						}
						else
						{
							reject('<?php echo displayText('L1207');//Please input New PO Number?>');
						}
					}, 500)
				})
			},
			allowOutsideClick: false
			}).then(function (poNumber) {
				//~ location.href='gerald_purchaseOrderMakingSql.php?changePoNumber=1&currentPo=<?php echo $poNumber;?>&poNumber='+poNumber+'&changePOType='+changePOType;
				$("#poNumber").val(poNumber);
				$("#selfFormId").submit();
			})
		});
		
<?php
	//~ if($_SESSION['idNumber']=='0346')
	if($_SESSION['idNumber']==true)
	{
		?>
		$("#finishId").click(function(){
			
			/* Remove trapping 2021-07-29
			swal({
				title: 'Creating PO is not allowed for the meantime',//2021-07-28
				text: '',
				type: 'info',
				showCancelButton: false,
				allowOutsideClick: false,
			}).then(function(){
				
			});
			
			return false;
			*/
			
			$.ajax({
				url:'gerald_purchaseOrderMakingSql.php',
				type:'post',
				data:{
					ajaxType:'checkPONumber',
					poNumber:'<?php echo $poNumber;?>'
				},
				success:function(data){
					if (data.trim() != '') {
						console.log(data);
						swal({
							title: 'PO Number : <?php echo $poNumber;?> already use',
							text: 'Please click refresh button to change PO Number',
							type: 'info',
							showCancelButton: false,
							allowOutsideClick: false,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'REFRESH'
						}).then(function(){
							$("#poNumber").val('');
							$("#selfFormId").submit();
						})
						
					} else {
						swal({
							title: '<?php echo displayText('L224');//PO Number?> : <?php echo $poNumber;?>',
							text: '<?php echo displayText('L1208');//By clicking this button means that you have already printed the PO. Are you sure you want to finish this PO? ?>',
							type: 'info',
							showCancelButton: true,
							allowOutsideClick: false,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'Yes'
						}).then(function(){
							<?php
								if($_GET['country']==1) $emailArray = array();//Temporary
								if($_GET['country']==2) $emailArray = array();//Temporary deactivate 2019-07-06
								//if($_SESSION['idNumber']=='0280') $emailArray = array('ace@arktech.co.jp');//Temporary
								if(count($emailArray) > 0)
								{
									?>
										swal({
											title: "",
											//~ text: "This will automatically send an email to these following :"+emails,
											html:"This will automatically send an email to these following :<br><?php echo implode("<br>",$emailArray);?>",
											type: 'info',
											showCancelButton: true,
											confirmButtonColor: '#3085d6',
											cancelButtonColor: '#d33',
											confirmButtonText: 'Proceed',
											allowOutsideClick: false
										}).then(function () {
											location.href='<?php echo $converterLink;?>?saveFile=1&poNumber=<?php echo $poNumber;?>';
										}, function (dismiss) {
											if (dismiss === 'cancel') {
												
											}
										});
									<?php
								}
								else
								{
									?>
									swal({
										title: 'No Email was Set',
										text: 'This will not automatically send an email',
										type: 'info',
										showCancelButton: true,
										allowOutsideClick: false,
										confirmButtonColor: '#3085d6',
										cancelButtonColor: '#d33',
										confirmButtonText: 'Proceed'
									}).then(function(){
										//~ location.href='gerald_purchaseOrderMakingSql.php?finish=1&poNumber=<?php echo $poNumber;?>';
										//~ alert('Time First');
										
										$("#key").val('<?php echo $key;?>');
										$("#selfFormId").submit();
									})
									<?php
								}
							?>
						})
					}
				}
			});
		});		
		<?php
	}
	else
	{
		?>
		$("#finishId").click(function(){
			swal({
				title: '<?php echo displayText('L224');//PO Number?> : <?php echo $poNumber;?>',
				text: '<?php echo displayText('L1208');//By clicking this button means that you have already printed the PO. Are you sure you want to finish this PO? ?>',
				type: 'info',
				showCancelButton: true,
				allowOutsideClick: false,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes'
			}).then(function(){
				<?php
					if($_GET['country']==1) $emailArray = array();//Temporary
					if($_GET['country']==2) $emailArray = array();//Temporary deactivate 2019-07-06
					//if($_SESSION['idNumber']=='0280') $emailArray = array('ace@arktech.co.jp');//Temporary
					if(count($emailArray) > 0)
					{
						?>
							swal({
								title: "",
								//~ text: "This will automatically send an email to these following :"+emails,
								html:"This will automatically send an email to these following :<br><?php echo implode("<br>",$emailArray);?>",
								type: 'info',
								showCancelButton: true,
								confirmButtonColor: '#3085d6',
								cancelButtonColor: '#d33',
								confirmButtonText: 'Proceed',
								allowOutsideClick: false
							}).then(function () {
								location.href='<?php echo $converterLink;?>?saveFile=1&poNumber=<?php echo $poNumber;?>';
							}, function (dismiss) {
								if (dismiss === 'cancel') {
									
								}
							});
						<?php
					}
					else
					{
						?>
						swal({
							title: 'No Email was Set',
							text: 'This will not automatically send an email',
							type: 'info',
							showCancelButton: true,
							allowOutsideClick: false,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'Proceed'
						}).then(function(){
							//~ location.href='gerald_purchaseOrderMakingSql.php?finish=1&poNumber=<?php echo $poNumber;?>';
							//~ alert('Time First');
							
							$("#key").val('<?php echo $key;?>');
							$("#selfFormId").submit();
						})
						<?php
					}
				?>
			})
		});		
		<?php
	}
?>
	});
</script>
<!-- ---------------------------------------- Tiny Box Script ------------------------------------------------------------------ -->
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script src='/<?php echo v; ?>/Common Data/Libraries/Javascript/checkProgramCode.js'></script>
<script type="text/javascript">
function openTinyBox(w,h,url,post,iframe,html,left,top)
{
	var windowWidth = $(window).width();
	var windowHeight = $(window).height();
	TINY.box.show({
		url:url,width:w,height:h,post:post,html:html,opacity:20,topsplit:6,animate:false,close:true,iframe:iframe,left:left,top:top,
		boxid:'box',
		openjs:function(){
			$( "div.designForm" ).find("input:not([type=submit],[type=button]),textarea,select").addClass("w3-input w3-border w3-hover-yellow").css( "background-color", "#ffffb3" );
			$( "div.designForm" ).find("input[type=submit],input[type=button]").addClass("w3-btn w3-blue");
			$( "div.designForm" ).find("label").addClass("w3-label w3-text-black");
			
			$("#box iframe").attr('name','boxName');
			
			$("#remarks").val($("#poRemarks").val());
			
			if(!iframe)
			{
				var windowHeight = (55 / 100) * $(window).height();
				var tinyBoxHeight = $("#box").height();
				if(tinyBoxHeight > (windowHeight))
				{
					//~ $("div.designForm").css({'overflow-y':'scroll','overflow-x':'hidden','height':(windowHeight) + 'px'});
					//~ $("#box").css('height',(windowHeight+30) +'px');
					
					$("div.designForm").css({'overflow-y':'scroll','overflow-x':'hidden','height':'85vh'});
					$("#box").css('height','90vh');
				}
			}			
		}
	});
}
</script>
<!-- --------------------------------------------------------------------------------------------------------------------------- -->
</html>
