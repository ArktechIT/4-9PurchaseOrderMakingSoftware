<?php
	$path = $_SERVER['DOCUMENT_ROOT']."//V3/Common Data/";
	set_include_path($path);
	include("PHP Modules/mysqliConnection.php");
	include("PHP Modules/gerald_functions.php");
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
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
	
	$uniqueSupplier = $_GET['uniqueSupplier'];
	
	$supplierExplode = explode("-",$uniqueSupplier);
	$supplierId = $supplierExplode[0];
	$supplierType = $supplierExplode[1];
	$currency = $supplierExplode[2];
	
	$poRemarks = ($supplierType == 2 AND $_GET['country']=='1') ? "NOTE: Please include inspection data, Certificate of conformance and Test Report upon delivery and via email." : "";
	
	$supplierAlias = $shipment = '';
	$sql = "SELECT supplierAlias, shipment FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	if($supplierType==2)	$sql = "SELECT subconAlias, shipment FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
	$querySupplier = $db->query($sql);
	if($querySupplier AND $querySupplier->num_rows > 0)
	{
		$resultSupplier = $querySupplier->fetch_row();
		$supplierAlias = $resultSupplier[0];
		$shipment = $resultSupplier[1];
	}
	
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
		$latestPoNo = generateCodeNew($maxNumber,strtoupper($supplierAlias).date('yn'),$numberCount);
	}
	else
	{
		$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` ORDER BY `number` DESC LIMIT 1";
		$sql = "SELECT CAST(poNumber as unsigned) as number FROM `purchasing_podetailsnew` WHERE poNumber != '0010651' ORDER BY `number` DESC LIMIT 1";
		$queryCheckMaxPo = $db->query($sql);
		if($queryCheckMaxPo AND $queryCheckMaxPo->num_rows > 0)
		{
			$resultCheckMaxPo = $queryCheckMaxPo->fetch_assoc();
			$latestPoNo = generateCode(($resultCheckMaxPo['number']+1),'',7);
		}
	}
?>

	<!DOCTYPE html>
	<html>
	<title><?php echo displayText('L1176');?></title>

	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/w3css/w3.css">
	<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
	<script src="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>
	<body>
		<div class="designForm w3-card-4" style='width:95%;font-size:2.28vh;margin: auto;margin-top: 2%;'>
			<div class="w3-container w3-pale-red" >
				<center><h2><?php echo displayText('L1176');?></h2></center>
			</div>
			
<!--
			<form action='gerald_purchaseOrderConverter.php?preview=1' method='post' id='formId'></form>
-->
			<form action='gerald_purchaseOrderMakingSql.php' method='post' id='formId'></form>
			<input type='hidden' name='supplierId' value='<?php echo $supplierId;?>' form='formId' required>
			<input type='hidden' name='supplierType' value='<?php echo $supplierType;?>' form='formId' required>
			<input type='hidden' name='poCurrency' value='<?php echo $currency;?>' form='formId' required>
			<div class="w3-row">
				<div class="w3-col w3-container w3-padding-4" style="width:30%">
					<?php
						if($supplierId==11 AND $supplierType==2)
						{
							?>
							<label><b><?php echo displayText('L24');?></b></label>
							<select name='customerAlias' form='formId' required>
								<option value='others'>Other Customer</option>
								<option value='B/E Phils.'>B/E Phils.</option>
								<option value='JAMCO PHILS'>JAMCO PHILS</option>
							</select>
							<?php
						}
					?>
					<p>
						<label><b><?php echo displayText('L224');?></b></label>
						<input type='text' id='poNumber' name='poNumber' form='formId' placeholder='<?php echo "Latest PO Number is ".$latestPoNo;?>' value='<?php echo $latestPoNo;?>' autocomplete='off' required>
					</p>
					<p>
						<label><b><?php echo displayText('L614');?></b></label>
						<select name='shipmentType' form='formId' required>
							<option value=''></option>
							<option value='1' <?php if(in_array($shipment,array(0,1,''))) echo "selected";?>><?php echo displayText('L1081');?></option>
							<option value='2' <?php if($shipment==2) echo "selected";?>><?php echo displayText('L1082');?></option>
							<option value='3' <?php if($shipment==2) echo "selected";?>><?php echo displayText('L1083');?></option>
						</select>
					</p>
					<?php
						if($supplierType!=2)
						{
							?>
							<p>
								<label><b><?php echo displayText('L1177');?></b></label>
								<input type='date' name='poTargetReceiveDate' value='<?php echo date('Y-m-d',strtotime('+30 days'));?>' form='formId' required>
							</p>
							<?php
						}
					?>
					<p>
						<label><b><?php echo displayText('L636');?></b></label>
						<textarea name='poRemarks' form='formId' rows='5' required><?php echo $poRemarks;?></textarea>
					</p>
					<p>
						<label><b><?php echo displayText('L1192');?></b></label>
						<input type='number' name='poDiscount' value='0' form='formId' step='any' required>
					</p>
					<center>
						<input id='finalizeButton' type='submit' name='finalize' value='<?php echo displayText('L1076');?>' form='formId'>
						<input id='previewButton' type='button' name='preview' value='<?php echo displayText('L1193');?>'>
					</center>
				</div>
				<div class="w3-col w3-container w3-padding-4" style="width:70%">
					<p>
						<label><b><?php echo displayText('L1194');?> <button id='addId'><img src='/V3/Common Data/Templates/systemImages/addIcon.png' height='20'></button></b></label>
						<div class="w3-row w3-card">
							<div class="w3-col w3-white w3-container" style="width:18%;font-weight:bold;"><?php echo displayText('L31');?></div>
							<div class="w3-col w3-white w3-container" style="width:18%;font-weight:bold;"><?php echo displayText('L612');?></div>
							<div class="w3-col w3-white w3-container" style="width:38%;font-weight:bold;"><?php echo displayText('L799');?></div>
							<div class="w3-col w3-white w3-container" style="width:18%;font-weight:bold;"><?php echo displayText('L267');?></div>
							<div class="w3-col w3-white w3-container" style="width:8%;font-weight:bold;"></div>
						</div>
						<div id='containerId'></div>
					</p>					
				</div>
			</div>
		</div>
	</body>
<script src='/V3/Common Data/Templates/jquery.js'></script>
<script>	
	function checkFormValues(formSelector)
	{
		var errorFlag = 0;
		var fields = $(formSelector).serializeArray();
		jQuery.each( fields, function( i, field ) {
			if(field.value.trim()=='' && field.name!='poRemarks' )
			{
				swal({
					title: "Please fill up the form properly!",
					timer: 4000,
					allowOutsideClick: false
				}).then(function () {
					thisObj.val('').focus();
				}, function (dismiss) {
					if (dismiss === 'timer') {
						
					}
				})
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

		<?php
			$unitInput = "<select name='chargeUnit[]' required form='formId'>";
			$unitInput .= "<option></option>";
					$sql = "SELECT unitId, unitName FROM purchasing_units ORDER BY unitName";
					$queryUnits = $db->query($sql);
					if($queryUnits->num_rows > 0)
					{
						while($resultUnits = $queryUnits->fetch_array())
						{
							$unitId = $resultUnits['unitId'];
							$unitName = $resultUnits['unitName'];
							$unitInput .= "<option value='".$unitId."'>".$unitName."</option>";
						}
					}
			$unitInput .= "</select>";
			
			$inputs = "
				<div class='w3-row w3-card'>
					<div class='w3-col w3-white w3-container' style='width:18%;'><input type='number' name='chargeQuantity[]' required form='formId'></div>
					<div class='w3-col w3-white w3-container' style='width:18%;'>".$unitInput."</div>
					<div class='w3-col w3-white w3-container' style='width:38%;'><textarea name='chargeDescription[]' required form='formId'></textarea></div>
					<div class='w3-col w3-white w3-container' style='width:18%;'><input type='number' name='chargeUnitPrice[]' required form='formId'></div>
					<div class='w3-col w3-white w3-container' style='width:8%;'><span class='w3-closebtn w3-medium' title='Remove' onclick=' this.parentElement.parentElement.remove()' >&times;</span></div>
				</div>";
			$inputs = trim(preg_replace('/\s+/', ' ', $inputs));
		?>
		
		$("#addId").click(function(){
			//~ $("#containerId").append("<div class='w3-row w3-card'><div class='w3-col w3-white w3-container' style='width:18%;'><input type='number'></div><div class='w3-col w3-white w3-container' style='width:18%;'><input type='number'></div><div class='w3-col w3-white w3-container' style='width:38%;'><textarea></textarea></div><div class='w3-col w3-white w3-container' style='width:18%;'><input type='number'></div><div class='w3-col w3-white w3-container' style='width:8%;'><span class='w3-closebtn w3-medium' title='Remove' onclick=\" this.parentElement.parentElement.remove() \">&times;</span></div>");
			$("#containerId").append("<?php echo $inputs;?>");
			$( "div.designForm" ).find("input:not([type=submit],[type=button],[type=image]),textarea,select").addClass("w3-input w3-border w3-hover-yellow").css({"background-color":"#ffffb3"});
			$( "div.designForm" ).find("input[type=submit],input[type=button]").addClass("w3-btn w3-blue");
			$( "div.designForm" ).find("label").addClass("w3-label w3-text-black");			
		});
		
		$("#poNumber").blur(function(){
			var thisObj = $(this);
			var poNumber = thisObj.val();
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
							swal({
								title: data,
								timer: 4000,
								allowOutsideClick: false
							}).then(function () {
								thisObj.val('').focus();
							}, function (dismiss) {
								if (dismiss === 'timer') {
									
								}
							})
						}
					}
				});
			}
		});
		
		$("#poNumber").keypress(function(e){
			if(e.which==13)	e.preventDefault();
		});
		
		$("#finalizeButton").click(function(){
			if(checkFormValues("#formId")==1)	return false;
			
			//~ $("#formId")
				//~ .attr('target','')
				//~ .attr('action','gerald_purchaseOrderMakingSql.php')
				//~ .submit();
			$("#formId")
				.attr('target','')
				.attr('action','gerald_purchaseOrderMakingSql.php');
		});
		
		$("#previewButton").click(function(){
			if(checkFormValues("#formId")==1)	return false;
			
			$("#formId")
				.attr('target','previewWindow')
				.attr('action','<?php echo ($_GET['country']==2) ? 'gerald_purchaseOrderConverterJapan.php' : 'gerald_purchaseOrderConverter.php';?>')
				.submit();
			//~ $("#formId")
				//~ .attr('target','previewWindow')
				//~ .attr('action','gerald_purchaseOrderConverter.php')
				//~ .submit();
		});
		
		$("#formId").submit(function(){
			if($(this).attr('action')=='gerald_purchaseOrderConverter.php' || $(this).attr('action')=='gerald_purchaseOrderConverterJapan.php')
			{
				window.open('','previewWindow','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');
			}
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
