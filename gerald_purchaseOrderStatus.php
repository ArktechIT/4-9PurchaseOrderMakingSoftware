<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include("PHP Modules/mysqliConnection.php");
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	$poNumber = $_GET['poNumber'];
	$idNumber = $_SESSION['idNumber'];
	
	$notificationId = '';
	if(isset($_GET['notificationId']))
	{
		$notificationId = $_GET['notificationId'];
		
		$sql = "SELECT notificationDetail, notificationKey FROM system_notificationdetails WHERE notificationId = ".$notificationId." LIMIT 1";
		$queryNotificationIdDetails = $db->query($sql);
		if($queryNotificationIdDetails AND $queryNotificationIdDetails->num_rows > 0)
		{
			$resultNotificationIdDetails = $queryNotificationIdDetails->fetch_assoc();
			$notificationDetail = $resultNotificationIdDetails['notificationDetail'];
			$poNumber = $resultNotificationIdDetails['notificationKey'];
		}
		
		$departmentId = $sectionId = '';
		$sql = "SELECT departmentId, sectionId FROM hr_employee WHERE idNumber LIKE '".$idNumber."' LIMIT 1";
		$queryEmployee = $db->query($sql);
		if($queryEmployee AND $queryEmployee->num_rows > 0)
		{
			$resultEmployee = $queryEmployee->fetch_assoc();
			$departmentId = $resultEmployee['departmentId'];
			$sectionId = $resultEmployee['sectionId'];
		}
		
		$sql = "SELECT notificationId FROM system_notification WHERE notificationId = ".$notificationId." AND ((notificationTarget LIKE '".$departmentId."' AND targetType == 0) OR (notificationTarget LIKE '".$sectionId."' AND targetType == 1) OR (notificationTarget LIKE '".$idNumber."' AND targetType == 2)) LIMIT 1";
		$queryNotification = $db->query($sql);
		if($queryNotification AND $queryNotification->num_rows == 0)
		{
			echo "You do not have permission to view this content.";
			exit(0);
		}
	}
	
	$poStatus = $supplierId = $supplierType = $poRemarks = $checkedBy = $approvedBy = '';
	$sql = "SELECT poNumber, supplierId, supplierType, poShipmentType, poRemarks, poStatus, poDiscount, checkedBy, approvedBy FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
		$poNumber = $resultPodetailsNew['poNumber'];
		$supplierId = $resultPodetailsNew['supplierId'];
		$supplierType = $resultPodetailsNew['supplierType'];
		$poShipmentType = $resultPodetailsNew['poShipmentType'];
		$poRemarks = $resultPodetailsNew['poRemarks'];
		$poStatus = $resultPodetailsNew['poStatus'];
		$poDiscount = $resultPodetailsNew['poDiscount'];
		$checkedBy = $resultPodetailsNew['checkedBy'];
		$approvedBy = $resultPodetailsNew['approvedBy'];
	}
	if($poNumber=='0015249' AND $_SESSION['idNumber']=='0346')	$poStatus = 0;
	$emailArray = array();
	$statusName = $purchaseOrderStatus = '';
	if($poStatus==0)//Ongoing
	{
		if($checkedBy=='')
		{
			$permissionIds = "105,205,305";//Purchasing Head (305 Temporary)
			//~ $statusName = "Checking";
			$statusName = displayText('L1199');
			$purchaseOrderStatus = 1;
		}
		else if($approvedBy=='')
		{
			$permissionIds = "105,205";//Purchasing Head (205 Temporary)
			//~ $permissionIds = "135";//Top Management
			//~ $statusName = "Approval";
			$statusName = displayText('L1200');
			$purchaseOrderStatus = 3;
			if($_GET['country']==2)
			{
				$purchaseOrderStatus = 1;
			}
		}
		else
		{
			//~ $statusName = "Printing";
			$statusName = displayText('L1198');
			$purchaseOrderStatus = 2;
			
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
		}
		
		if($notificationId!='')
		{
			if($_GET['country']==2)
			{
				//~ $idNumberArray = array('0352','J014','J026');
				$idNumberArray = array('0458','J014');
				if(!in_array($idNumber,$idNumberArray))
				{
					echo "You do not have permission to view this content.";
					exit(0);
				}
			}
			else
			{
				$sql = "SELECT permissionId FROM system_userpermission WHERE idNumber LIKE '".$idNumber."' AND permissionId IN(".$permissionIds.")";
				$queryUserPermission = $db->query($sql);
				if($queryUserPermission AND $queryUserPermission->num_rows == 0)
				{
					echo "You do not have permission to view this content.";
					exit(0);
				}
			}
		}
	}
	else
	{
		echo "Already finish";
		exit(0);
	}
	
	$converterLink = ($_GET['country']==2) ? "gerald_purchaseOrderConverterJapan.php" : "gerald_purchaseOrderConverter.php";
	//~ $converterLink = "gerald_purchaseOrderConverter.php";
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
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/w3css/w3.css">
	<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
	<script src="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>
	<body>
		<div class="designForm w3-card-4" style='width:98%;font-size:2.28vh;margin: 1%;margin-top: 1%;'>
			<div class="w3-container w3-pale-red">
				<?php
					if($notificationId=='')
					{
						?><button style='float:left;width:65px;height:60px;' onclick="location.href='gerald_purchaseOrderMakingSummary.php'"><img src='/Common Data/Templates/images/backIcon(Ge).png' style='max-width:100%;max-height:70%;'><?php echo displayText('L1072');?></button><?php
					}
					else
					{
						?><button style='float:left;width:65px;height:60px;' onclick="location.href='/V3/dashboard.php'"><img src='/V3/Common Data/Templates/systemImages/homeIcon.png' style='max-width:100%;max-height:70%;'>Home</button><?php
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
			<form action='<?php echo $converterLink;?>?poNumber=<?php echo $poNumber;?>' method='post' target='iframeName' id='formId'></form>
			<input type='hidden' name='preview' value='0' form='formId' required>
			<input type='hidden' id='downloadId' name='download' value='0' form='formId' required>
			
			<?php
				//~ if($statusName == 'Printing')
				if($purchaseOrderStatus == 2)
				{
					?>
					<div class="w3-container w3-padding-4" style="width:100%">
						<iframe src='<?php echo $converterLink;?>?poNumber=<?php echo $poNumber;?>' style='width:100%;height:78.5vh;'></iframe>
						<iframe src='' name='iframeName' style='width:100%;height:75vh;display:none;'></iframe>				
						<center>
						<input id='printId' type='submit' name='print' style='width:19%;' value='<?php echo displayText('L1201');//PRINT?>'>
						<input id='dlPrintId' type='submit' name='download' style='width:19%;' value='<?php echo displayText('L1202');//DOWNLOAD?>'>
						<input id='newPOId' type='submit' name='newpo' style='width:19%;' value='<?php echo displayText('L1203');//NEW PO#?>'>
						<input id='changePOId' type='button' name='changepo' style='width:19%;' value='<?php echo displayText('L1204');//CHANGE PO#?>'>
						<input id='finishId' type='button' name='submit' style='width:19%;' value='<?php echo displayText('L1105');//FINISH?>'>
						</center>
					</div>
					<?php
				}
				else
				{
					//~ $buttonValue = ($statusName == 'Checking') ? 'CHECK' : 'APPROVE';
					$buttonValue = ($purchaseOrderStatus == 1) ? 'CHECK' : 'APPROVE';
					
					if($notificationId=='')
					{
						?>
						<form action='gerald_purchaseOrderMakingSql.php?editPO=1&poNumber=<?php echo $poNumber;?>' method='post' id='formEditId'></form>
						<input type='hidden' name='preview' value='0' form='formId' required>
						<div class="w3-row">
							<div class="w3-col w3-container w3-padding-4" style="width:30%">
								<p>
									<label><b><?php echo displayText('L614');?></b></label>
									<select name='shipmentType' form='formEditId' required>
										<option value=''></option>
										<option value='1' <?php if($poShipmentType==1)	echo 'selected';?>>.displayText('L1081').</option>
										<option value='2' <?php if($poShipmentType==2)	echo 'selected';?>>.displayText('L1082').</option>
										<option value='3' <?php if($poShipmentType==3)	echo 'selected';?>>.displayText('L1083').</option>
									</select>
								</p>
								<p>
									<label><b><?php echo displayText('L636');?></b></label>
									<textarea name='poRemarks' rows='4' id='poRemarks' form='formEditId' required><?php echo $poRemarks;?></textarea>
								</p>
								<p>
									<label><b><?php echo displayText('L1192');?></b></label>
									<input type='number' name='poDiscount' value='<?php echo $poDiscount;?>' form='formEditId' required>
								</p>
								<p>
									<input type='submit' style='width:100%;' value='UPDATE' form='formEditId'>
								</p>
							</div>
							<div class="w3-col w3-container w3-padding-4" style="width:70%">
								<iframe src='<?php echo $converterLink;?>?poNumber=<?php echo $poNumber;?>' style='width:100%;height:83vh;'></iframe>
								<iframe src='' name='iframeName' style='width:100%;height:75vh;display:none;'></iframe>				
							</div>
						</div>
						<?php
					}
					else
					{
						?>
						<div class="w3-container w3-padding-4" style="width:100%">
						<iframe src='<?php echo $converterLink;?>?poNumber=<?php echo $poNumber;?>' style='width:100%;height:78.5vh;'></iframe>
						<iframe src='' name='iframeName' style='width:100%;height:75vh;display:none;'></iframe>				
						<center>
						<input type='button' onclick="location.href='gerald_purchaseOrderMakingSql.php?<?php echo "notificationId=".$notificationId."&purchaseOrderStatus=".$purchaseOrderStatus;?>'" style='width:100%;' value='<?php echo $buttonValue;?>'>
						</center>
					</div>
						<?php
					}
				}
			?>			
		</div>		
	</body>

<script src='/V3/Common Data/Templates/jquery.js'></script>
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
				location.href='gerald_purchaseOrderMakingSql.php?changePoNumber=1&currentPo=<?php echo $poNumber;?>&poNumber='+poNumber+'&changePOType='+changePOType;
			})
		});
		
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
							location.href='gerald_purchaseOrderMakingSql.php?finish=1&poNumber=<?php echo $poNumber;?>';
							//~ alert('Time First');
						})
						<?php
					}
				?>
			})
		});
	});
</script>
<!-- ---------------------------------------- Tiny Box Script ------------------------------------------------------------------ -->
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script src='/V3/Common Data/Libraries/Javascript/checkProgramCode.js'></script>
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
