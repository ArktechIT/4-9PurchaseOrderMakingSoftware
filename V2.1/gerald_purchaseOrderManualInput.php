<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);    
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	include("PHP Modules/rose_prodfunctions.php");
	ini_set("display_errors", "on");
	$ctrl = new PMSDatabase;
	$tpl = new PMSTemplates;
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='updateData')
		{
			$listId = $_POST['listId'];
			$newValue = $_POST['newValue'];
			$column = $_POST['column'];
			
			$sql = "UPDATE purchasing_forpurchaseorder SET `".$column."`='".$newValue."' WHERE listId=".$listId." LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		else if($_POST['ajaxType']=='removeData')
		{
			$listId = $_POST['listId'];
			
			$sql = "DELETE FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
			$queryDelete = $db->query($sql);
		}		
		else if($_POST['ajaxType']=='addItems')
		{
			$sql = "INSERT INTO purchasing_forpurchaseorder (supplierType,processRemarks) VALUES(1,'".$_SESSION['idNumber']."')";
			$queryInsert = $db->query($sql);
		}
		else if($_POST['ajaxType']=='submitManualInput')
		{
			$submitButton = $tpl->setDataValue("L489")
						   ->setAttribute([
								"name"  => "",
								"id"    => "",
								"type"  => "submit",
								"form"  => "selfFormId"
						   ])
						   ->addClass("") // Optional
						   ->createButton();
			 
			?>
			<div class='col-md-12'>
				<div class='row w3-padding-top'>
					<div class='col-md-12'>
						<label><?php echo displayText('L367');?></label>
						<select class='w3-select inputClass w3-border w3-pale-yellow' id='supplierInput'>
							<option value=''></option>
							<?php
								$sql = "SELECT supplierId, supplierAlias FROM purchasing_supplier ORDER BY supplierAlias";
								$querySupplier = $db->query($sql);
								if($querySupplier AND $querySupplier->num_rows > 0)
								{
									while($resultSupplier = $querySupplier->fetch_assoc())
									{
										$supplierId = $resultSupplier['supplierId'];
										$supplierAlias = $resultSupplier['supplierAlias'];
										
										echo "<option value='".$supplierId."'>".$supplierAlias."</option>";
									}
								}
							?>
						</select>
					</div>
				</div>
				<div class='row w3-padding-top'>
					<div class='col-md-12'>
						<label><?php echo displayText('L112');?></label>
						<select class='w3-select inputClass w3-border w3-pale-yellow' id='currencyInput'>
							<option value=''></option>
							<option value='1'><?php echo displayText('L786');?></option>
							<option value='2'><?php echo displayText('L787');?></option>
							<option value='3'><?php echo displayText('L788');?></option>
						</select>
					</div>
				</div>
				<div class='row w3-padding-top'>
					<div class='col-md-12 w3-center'>
						<?php echo $submitButton;?>
					</div>
				</div>
			</div>			
			<?php
		}
		exit(0);
	}
	
	$autoEmailType = 0;
	$identifier = $_GET['identifier'];
	$identifierId = $_GET['identifierId'];	
	
	$title = displayText('L1797');
	PMSTemplates::includeHeader($title);	
	
	$autoEmailTypeName = '';
	if($autoEmailType==0)
	{
		$autoEmailTypeName = 'Order Inquiry';
	}
	
	$name = '';
	if($identifier==0)
	{
		$nameType = 'Employee';
		$sql = "SELECT CONCAT(firstName,' ',surName) as employeeName FROM hr_employee WHERE employeeId = ".$identifierId." LIMIT 1";
		$queryEmployee = $db->query($sql);
		if($queryEmployee AND $queryEmployee->num_rows > 0)
		{
			$resultEmployee = $queryEmployee->fetch_assoc();
			$name = $resultEmployee['employeeName'];
		}
	}
	else if($identifier==1)
	{
		$nameType = 'Supplier';
		$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$identifierId." LIMIT 1";
		$querySupplier = $db->query($sql);
		if($querySupplier AND $querySupplier->num_rows > 0)
		{
			$resultSupplier = $querySupplier->fetch_assoc();
			$name = $resultSupplier['supplierAlias'];
		}
	}
	else if($identifier==2)
	{
		$nameType = 'Subcon';
		$sql = "SELECT subconAlias FROM purchasing_subcon WHERE subconId = ".$identifierId." LIMIT 1";
		$querySubcon = $db->query($sql);
		if($querySubcon AND $querySubcon->num_rows > 0)
		{
			$resultSubcon = $querySubcon->fetch_assoc();
			$name = $resultSubcon['subconAlias'];
		}
	}	
	
	$nameSpan = "<label class='w3-xlarge'>".$nameType." : ".$name."</label>";
	
	$autoEmailId = $sendTime = '';
	$sql = "SELECT autoEmailId, sendTime FROM system_autosendemaildetails WHERE identifier = ".$identifier." AND identifierId = ".$identifierId." AND autoEmailType = ".$autoEmailType." LIMIT 1";
	$queryAutoSendEmailDetails = $db->query($sql);
	if($queryAutoSendEmailDetails AND $queryAutoSendEmailDetails->num_rows > 0)
	{
		$resultAutoSendEmailDetails = $queryAutoSendEmailDetails->fetch_assoc();
		$autoEmailId = $resultAutoSendEmailDetails['autoEmailId'];
		$sendTime = $resultAutoSendEmailDetails['sendTime'];
	}
	
    $addItems = $tpl->setDataValue("L482")
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "addItemsId",
                        "type"  => "",
                        "value"  => "",
                        "form"  => ""
                   ])
                   ->addClass("") // Optional
                   ->createButton();

    $submitButton = $tpl->setDataValue("L489")
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "submitId"
                   ])
                   ->addClass("") // Optional
                   ->createButton();                   
    	
	$emailIdArray = array();
	$count = 0;
	$tbodyContent = '';
	$sql = "SELECT listId, itemRemarks, dateNeeded, itemName, itemDescription, itemQuantity, itemUnit, itemPrice FROM purchasing_forpurchaseorder WHERE lotNumber = '' AND processRemarks = '".$_SESSION['idNumber']."'";
	$queryForPurchase = $db->query($sql);
	if($queryForPurchase AND $queryForPurchase->num_rows > 0)
	{
		while($resultForPurchase = $queryForPurchase->fetch_assoc())
		{
			$listId = $resultForPurchase['listId'];
			$itemRemarks = $resultForPurchase['itemRemarks'];
			$dateNeeded = $resultForPurchase['dateNeeded'];
			$itemName = $resultForPurchase['itemName'];
			$itemDescription = $resultForPurchase['itemDescription'];
			$itemQuantity = $resultForPurchase['itemQuantity'];
			$itemUnit = $resultForPurchase['itemUnit'];
			$itemPrice = $resultForPurchase['itemPrice'];
			
			$itemNameInput = "<input class='inputClass w3-input w3-border w3-pale-yellow' type='input' value='".$itemName."' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemName'>";			
			$itemDescriptionInput = "<textarea class='w3-select w3-border w3-pale-yellow' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemDescription'>".$itemDescription."</textarea>";
			$quantityInput = "<input class='inputClass w3-input w3-border w3-pale-yellow' type='number' value='".$itemQuantity."' min='0.0001' step='any' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemQuantity'>";
			$dateNeededInput = "<input class='w3-input w3-border w3-pale-yellow' type='date' value='".$dateNeeded."' min='".date('Y-m-d')."' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='dateNeeded'>";
			$priceInput = "<input class='inputClass w3-input w3-border w3-pale-yellow' type='number' value='".$itemPrice."' min='0.0001' step='any' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemPrice'>";
			$remarksInput = "<textarea class='w3-select w3-border w3-pale-yellow' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemRemarks'>".$itemRemarks."</textarea>";		
			
			$itemUnitSelect = "<select class='inputClass w3-select w3-border w3-pale-yellow' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemUnit'>";
			$itemUnitSelect .= "<option value=''></option>";
			$sql = "SELECT unitId, unitName FROM purchasing_units ORDER BY unitName";
			$queryUnits = $db->query($sql);
			if($queryUnits AND $queryUnits->num_rows > 0)
			{
				while($resultUnits = $queryUnits->fetch_assoc())
				{
					$unitId = $resultUnits['unitId'];
					$unitName = $resultUnits['unitName'];
					$selected = ($unitId==$itemUnit) ? "selected" : "";
					$itemUnitSelect .= "<option value='".$unitId."' ".$selected.">".$unitName."</option>";
				}
			}
			$itemUnitSelect .= "</select>";
			
			$removeButton = $tpl->setDataValue("L609")
						   ->setAttribute([
								"name"  => "",
								"id"    => "",
								"type"  => "",
								"onclick"  => "removeData(".$listId.")"
						   ])
						   ->addClass("removeClass") // Optional
						   ->createButton(1);		
			
			$tbodyContent .= "
				<tr>
					<td>".++$count."</td>
					<td>".$itemNameInput."</td>
					<td>".$itemDescriptionInput."</td>
					<td>".$quantityInput."</td>
					<td>".$itemUnitSelect."</td>
					<td>".$priceInput."</td>
					<td>".$dateNeededInput."</td>
					<td>".$remarksInput."</td>
					<td>".$removeButton."</td>
				</tr>
			";
		}
	}
	
	$displayId = "L1797";
	$version = ": ".strtoupper(displayText('B3', 'utf8', 0, 1));
	$previousLink = "gerald_poPreparationList.php";
	createHeader($displayId, $version, $previousLink);
?>
<style>
	table.tableFixed tbody {
		display:block;
		height: 35vh;
		overflow:auto;
	}
	table.tableFixed thead, table.tableFixed tfoot, table.tableFixed tbody tr  {
		display:table;
		width:100%;
		table-layout:fixed;
	}
	table.tableFixed thead, table.tableFixed tfoot  {
		width: calc( 100% - 1.5em )
	}	
</style>
<form action='gerald_purchaseOrderStatus.php' method='post' id='selfFormId'></form>
<input type='hidden' name='formCheck[]' value='' id='keyId' form='selfFormId'>
<div class='container-fluid'>
    <div class='row w3-padding-top'> 
        <!--div class='col-md-3'>
			<div class='row w3-padding-top'>
				<div class='col-md-12'>
					<label>Supplier</label>
					<select class='w3-select inputClass' id='supplierInput'>
						<option value=''></option>
						<?php
							$sql = "SELECT supplierId, supplierAlias FROM purchasing_supplier ORDER BY supplierAlias";
							$querySupplier = $db->query($sql);
							if($querySupplier AND $querySupplier->num_rows > 0)
							{
								while($resultSupplier = $querySupplier->fetch_assoc())
								{
									$supplierId = $resultSupplier['supplierId'];
									$supplierAlias = $resultSupplier['supplierAlias'];
									
									echo "<option value='".$supplierId."'>".$supplierAlias."</option>";
								}
							}
						?>
					</select>
				</div>
			</div>
			<div class='row w3-padding-top'>
				<div class='col-md-12'>
					<label>Currency</label>
					<select class='w3-select inputClass' id='currencyInput'>
						<option value=''></option>
						<option value='1'>Dollar</option>
						<option value='2'>Peso</option>
						<option value='3'>Yen</option>
					</select>
				</div>
			</div>
			<div class='row w3-padding-top'>
				<div class='col-md-12'>
					<?php echo $submitButton;?>
				</div>
			</div>
        </div>
        <div class='col-md-9'-->
        <div class='col-md-12'>
			<div class='row w3-padding-top'>
				<div class='col-md-12'>
					<?php echo $addItems;?>
					<?php if($count > 0) echo $submitButton;?>
					<table class='table table-bordered table-condensed table-striped' id="mainTableId">
						<thead class='w3-indigo thead'>
							<tr>
								<th></th>
								<th><?php echo displayText('L1172');?></th>
								<th><?php echo displayText('L1173');?></th>
								<th><?php echo displayText('L1171');?></th>
								<th><?php echo displayText('L612');?></th>
								<th><?php echo displayText('L267');?></th>
								<th><?php echo displayText('L1309');?></th>
								<th><?php echo displayText('L242');?></th>
								<th></th>
							</tr>
						</thead>
						<tbody class='tbody'>
							<?php echo $tbodyContent;?>
						</tbody>
						<tfoot class='w3-indigo tfoot'>
							<tr>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>			
        </div>
    </div>
</div>
<div id='modal-izi'><span class='izimodal-content'></span></div>
<?php
	PMSTemplates::includeFooter();
?>
<script>
// script here
function updateData(obj)
{
	var newValue = $(obj).val();
	var listId = $(obj).data('listId');
	var column = $(obj).data('column');
	
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"post",
		data:{
			ajaxType:'updateData',
			listId:listId,
			newValue:newValue,
			column:column
		},
		success:function(data){
			if(data.trim()!='')
			{
				alert(data);
			}
		}
	});
}

function removeData(listId)
{
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"post",
		data:{
			ajaxType:'removeData',
			listId:listId
		},
		success:function(data){
			location.reload();
		}
	});		
}

$(document).ready(function(){
	var dataTable = $('#mainTableId').DataTable( {
		"processing"    : true,
		"ordering"      : true,
		"searching"     : false,
		"bInfo" 		: false,
		fixedColumns:   {
				leftColumns: 0
		},
		scrollY     	: 600,
		scrollX     	: false,
		scrollCollapse	: false,
		scroller    	: {
			loadingIndicator    : true
		},
		stateSave   	: false
	});    
	
	$("#addItemsId").click(function(){
		
		$.ajax({
			url:"<?php echo $_SERVER['PHP_SELF'];?>",
			type:"post",
			data:{
				ajaxType:'addItems'
			},
			success:function(data){
				location.reload();
			}
		});		
	});
	
	//~ $(".inputClass").change(function(){
	$(document).on("change",".inputClass",function(){
		var supplierId = $("#supplierInput").val();
		var currency = $("#currencyInput").val();
		var keyId = supplierId+'`'+'1-'+currency+'-1';
		$("#keyId").val(keyId);
	});
	
	$("#submitId").click(function(){
		var proceedFlag = 1;
		
		$(".inputClass").each(function(i){
			var thisObj = $(this);
			var thisVal = $(this).val();
			if(thisVal=='')
			{
				swal({
					title: 'Cannot proceed',
					text: 'Please complete the form',
					type: 'warning',
					showCancelButton: false,
					allowOutsideClick: false,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33'
				}).then(function(){
					thisObj.focus();
				})
				proceedFlag = 0;
				return false;
			}
		});
		
		if(proceedFlag==1)
		{
			$("#modal-izi").iziModal({
				title                   : ' ',
				headerColor             : '#1F4788',
				subtitle                : '<b>&nbsp;</b>',
				width                   : 400,
				fullscreen              : false,
				transitionIn            : 'comingIn',
				transitionOut           : 'comingOut',
				padding                 : 20,
				radius                  : 0,
				top                     : 10,
				restoreDefaultContent   : true,
				closeOnEscape           : true,
				closeButton             : true,
				overlayClose            : false,
				onOpening               : function(modal){
											modal.startLoading();
											// alert(assignedTo);
											$.ajax({
												url         : "<?php echo $_SERVER['PHP_SELF'];?>",
												type        : 'POST',
												data        : {
																ajaxType    : 'submitManualInput'
												},
												success     : function(data){
																$( ".izimodal-content" ).html(data);
																modal.stopLoading();
												}
											});
										},
				onClosed                : function(modal){
											$("#modal-izi").iziModal("destroy");
							} 
			});

			$("#modal-izi").iziModal("open");		
		}
	});
});
</script>
