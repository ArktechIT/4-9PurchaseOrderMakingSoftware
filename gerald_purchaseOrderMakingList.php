<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$return = "<option value=''>".displayText('L490')." </option>";
		if($column=='supplyType')
		{
			$supplyNameArray = array('Material','Subcon','Item','Accessory');
			foreach($supplyNameArray as $key => $valueCaption)
			{
				$valueColumn = ($key+1);
				
				$selected = ($value==$valueColumn) ? 'selected' : '';
				
				$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
			}
		}
		else if($column=='supplierId')
		{
			//~ if(strstr($sqlFilter,'supplyType = 2')!==FALSE)
			if(strstr($sqlFilter,"'subcon' = 'subcon'")!==FALSE)
			{
				$sql = "SELECT subconId, subconAlias FROM purchasing_subcon ORDER BY subconAlias";
				$querySubcon = $db->query($sql);
				if($querySubcon AND $querySubcon->num_rows > 0)
				{
					while($resultSubcon = $querySubcon->fetch_assoc())
					{
						$valueColumn = $resultSubcon['subconId'];
						$valueCaption = $resultSubcon['subconAlias'];
						
						$selected = ($value==$valueColumn) ? 'selected' : '';
						
						$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
					}
				}
			}
			else
			{
				$sql = "SELECT supplierId, supplierAlias FROM purchasing_supplier ORDER BY supplierAlias";
				$querySupplier = $db->query($sql);
				if($querySupplier AND $querySupplier->num_rows > 0)
				{
					while($resultSupplier = $querySupplier->fetch_assoc())
					{
						$valueColumn = $resultSupplier['supplierId'];
						$valueCaption = $resultSupplier['supplierAlias'];
						
						$selected = ($value==$valueColumn) ? 'selected' : '';
						
						$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
					}
				}
			}
		}
		else
		{
			$sql = "SELECT DISTINCT ".$column." FROM view_workschedule ".$sqlFilter." ORDER BY ".$column."";
			$query = $db->query($sql);
			if($query->num_rows > 0)
			{
				while($result = $query->fetch_array())
				{
					$valueColumn = $valueCaption = $result[$column];
					
					$selected = ($value==$result[$column]) ? 'selected' : '';
					
					$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
				}
			}
		}
		return $return;
	}
	
	if(isset($_POST['ajaxType']) AND $_POST['ajaxType']=='editQuantity')
	{
		$lotNumber = $_POST['lotNumber'];
		$newValue = $_POST['newValue'];
		
		$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$newValue." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
		$queryUpdate = $db->query($sql);
		if(!$queryUpdate)
		{
			echo displayText('L1326');
		}
		exit(0);
	}	
	
	//rhay delete confirmedMaterialPO
	if(isset($_POST['submitReason']))
	{
		$lote = trim($_POST['lote']);
		$dateNeeded = trim($_POST['dateNeeded']);
		$reason = $_POST['reason'];

		if($dateNeeded != "")
		{
			$update = "UPDATE ppic_materialcomputation set lotNumber = '_".$lote."' WHERE lotNumber = '".$lote."'";
			$processUpdate = $db->query($update);
		}
		$sql = "SELECT * FROM system_confirmedmaterialpo WHERE lotNumber = '".$lote."'";
		$process = $db->query($sql);
		if($process->num_rows > 0)
		{
			$result = $process->fetch_assoc();
			$materialId = $result['materialId'];
			$sheetQuantity = $result['sheetQuantity'];
			$pvc = $result['pvc'];
			$poNumber = $result['poNumber'];
			$lotNumberFromdb = $result['lotNumber'];
			$dateAdded = $result['dateAdded'];
			$employeeId = $result['employeeId'];
			$remarks = $result['remarks'];

			$sql1 = "SELECT * FROM ppic_lotlist WHERE lotNumber = '".$lote."'";
			$process1 = $db->query($sql1);
			if($process1->num_rows > 0)
			{
				$result1 = $process1->fetch_assoc();
				$ppicQty = $result1['workingQuantity'];
				$ppicDateGenerated = $result1['dateGenerated'];
				$ppicPartId = $result1['partId'];

				//delete FROM confirmedMaterialPo

				$delete = "DELETE FROM system_confirmedmaterialpo WHERE lotNumber = '".$lote."'";
				$processDelete = $db->query($delete);
				if($processDelete)
				{
					 $insertLog = "INSERT INTO system_confirmedmaterialpohistory (materialId,sheetQuantity,pvc
					,poNumber,lotNumber,dateAdded,employeeId,remarks,ppic_qty,ppic_dateGenerated,ppic_partId,deleteRemarks)
					VALUES ('".$materialId."','".$sheetQuantity."','".$pvc."','".$poNumber."','".$lotNumberFromdb."',
					'".$dateAdded."','".$employeeId."','".$remarks."','".$ppicQty."','".$ppicDateGenerated."','".$ppicPartId."','".$reason."')";
					$processInsert = $db->query($insertLog);
					if($processInsert)
					{
						$deleteLotlist = "DELETE FROM ppic_lotlist WHERE lotNumber = '".$lote."'";
						$processDeleteLotList = $db->query($deleteLotlist);
						if($processDeleteLotList)
						{
							$deleteLotlist = "DELETE FROM ppic_workschedule WHERE lotNumber = '".$lote."'";
							$processDeleteLotList = $db->query($deleteLotlist);
							
							?>
								<script>
									alert("Data Deleted");
								</script>
							<?php
						}
					}
				}
				else
				{
					echo $delete;
				}
			}
			else
			{
				echo $sql1;
			}
		}
	}
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
	
	$dateFrom = (isset($_POST['dateFrom'])) ? $_POST['dateFrom'] : '';
	$dateTo = (isset($_POST['dateTo'])) ? $_POST['dateTo'] : '';
	$lotNumber = (isset($_POST['lotNumber'])) ? $_POST['lotNumber'] : '';
	$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
	$supplyType = (isset($_POST['supplyType'])) ? $_POST['supplyType'] : '';
	$supplierId = (isset($_POST['supplierId'])) ? $_POST['supplierId'] : '';
	
	$sqlFilter = "";
	$sqlFilterArray = $sqlFilterMaterialSpecsArray = array();
	
	if($lotNumber!='')	$sqlFilterArray[] = "lotNumber LIKE '".$lotNumber."'";
	if($customerAlias!='')	$sqlFilterArray[] = "customerAlias LIKE '".$customerAlias."'";
	if($dateFrom!='' AND $dateTo == '')	$sqlFilterArray[] = "targetFinish >= '".$dateFrom."'";
	if($dateFrom != '' AND $dateTo != '') $sqlFilterArray[] = "targetFinish BETWEEN '".$dateFrom."' AND '".$dateTo."'";
	
	$sqlFilter = "WHERE processCode = 461 AND processSection = 5";
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter .= " AND ".implode(" AND ",$sqlFilterArray)." ";
	}
	
	if($supplyType!='')
	{
		$lotNumberArray = array();
		$sql = "SELECT lotNumber FROM view_workschedule ".$sqlFilter;
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$lotNumberArray[] = $resultWorkSchedule['lotNumber'];
			}
		}
		
		$lotNambaArray = array();
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND identifier = 4 AND status = ".$supplyType."";
		if($supplyType==2)	$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND (identifier = 1 OR (identifier = 4 AND status = 2))";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNambaArray[] = $resultLotList['lotNumber'];
			}
		}
		
		$sqlFilter .= " AND lotNumber IN('".implode("','",$lotNambaArray)."')";
		
		if($supplyType==2)	$sqlFilter .= " AND 'subcon' = 'subcon'";
	}
	
	if($supplierId!='')
	{
		$productIdArray = array();
		$sql = "SELECT processRemarks FROM view_workschedule ".$sqlFilter." AND processRemarks !=''";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$productIdArray[] = $resultWorkSchedule['processRemarks'];
			}
		}
		
		$processRemarksArray = array();
		$sql = "SELECT productId FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplierId = ".$supplierId." AND supplierType = 1";
		if($supplyType==2)	$sql = "SELECT productId FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplierId = ".$supplierId." AND supplierType = 2";
		$querySupplierProducts = $db->query($sql);
		if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
		{
			while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
			{
				$processRemarksArray[] = $resultSupplierProducts['productId'];
			}
		}
		
		$sqlFilter .= " AND (processRemarks IN('".implode("','",$processRemarksArray)."') OR processRemarks LIKE '".implode(",",$processRemarksArray)."') AND processRemarks!=''";
	}
	
	$sql = "SELECT lotNumber FROM view_workschedule ".$sqlFilter;
	$queryWorkSchedule = $db->query($sql);
	if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
	{
		$lotNumberArray = array();
		while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
		{
			$lote = $resultWorkSchedule['lotNumber'];
			
			$poId = '';
			$sql = "SELECT poId FROM ppic_lotlist WHERE lotNumber LIKE '".$lote."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$poId = $resultLotList['poId'];
			}			
			
			$mainLot = '';
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1 LIMIT 1";
			$queryMainLot = $db->query($sql);
			if($queryMainLot AND $queryMainLot->num_rows > 0)
			{
				$resultMainLot = $queryMainLot->fetch_assoc();
				$mainLot = $resultMainLot['lotNumber'];
			}
			
			$sql = "SELECT id FROM view_workschedule WHERE lotNumber LIKE '".$mainLot."' AND processCode = 459 AND status = 0 LIMIT 1";//Dont display if RO review not finish yet
			$queryPOReview = $db->query($sql);
			if($queryPOReview AND $queryPOReview->num_rows > 0)
			{
				continue;
			}
			$lotNumberArray[] = $lote;
		}
		
		$sqlFilter .= " AND lotNumber IN('".implode("','",$lotNumberArray)."')";
	}
	
	$sql = "SELECT lotNumber FROM view_workschedule ".$sqlFilter;
	$queryParts = $db->query($sql);
	$totalRecords = ($queryParts AND $queryParts->num_rows > 0) ? $queryParts->num_rows : 0;
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L1168', 'utf8', 0, 1);?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v;?>//Common Data/Templates/api.css">
	<script src="/<?php echo v;?>//Common Data/Templates/api.js"></script>
	<link rel="stylesheet" href="/<?php echo v;?>//Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
	<script src="/<?php echo v;?>//Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>	
	<style>
		.dropdown {
			position: relative;
			display: inline-block;
		}

		.dropdown-content {
			display: none;
			position: absolute;
			z-index: 1;
		}

		.dropdown:hover .dropdown-content {
			display: block;
		}
		
		.internalTrClass td {
			font-size:<?php echo $fontSize;?>px!important;
			line-height:1em!important;
		}
		
		.w3-modal{z-index:3;display:none;padding-top:100px;position:fixed;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.4)}
		.w3-modal-content{margin:auto;background-color:#fff;position:relative;padding:0;outline:0;width:600px}.w3-closebtn{text-decoration:none;float:right;font-size:24px;font-weight:bold;color:inherit}
		@media (max-width:600px){.w3-modal-content{margin:0 10px;width:auto!important}.w3-modal{padding-top:30px}}
		@media (max-width:768px){.w3-modal-content{width:500px}.w3-modal{padding-top:50px}}
		@media (min-width:993px){.w3-modal-content{width:900px}}
		.w3-animate-zoom {-webkit-animation:animatezoom 0.6s;animation:animatezoom 0.6s}
		@-webkit-keyframes animatezoom{from{-webkit-transform:scale(0)} to{-webkit-transform:scale(1)}}
		@keyframes animatezoom{from{transform:scale(0)} to{transform:scale(1)}}
		.w3-round-large{border-radius:8px!important}.w3-round-xlarge{border-radius:16px!important}
		.w3-closebtn:hover,.w3-closebtn:focus{color:#000;text-decoration:none;cursor:pointer}
		.w3-display-topleft{position:absolute;left:0;top:0}.w3-display-topright{position:absolute;right:0;top:0}
		
		
	</style>
</head>
<body class='api-loading'>
<?php
	createHeader('4-9','','/'.v.'/4-9%20Purchase%20Order%20Making%20Software/V2.1/gerald_poPreparationList.php');
?>
	<form action='gerald_purchaseOrderListExport.php' method='post' id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
<!--
			<button class='api-btn api-btn-home' onclick="location.href='/'.v.'/dashboard.php';" data-api-title='<?php echo displayText('L434');?>' <?php echo toolTip('L434');?>></button>
-->
		</div>
		
		<div class="api-top api-col api-title" style='width:40%;'>
			<h2><?php //echo displayText('L1168');?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:30%'>
		<!--http://192.168.254.163/Others/Rose/Purchasing/lotsubconAuto.php-->
		<a href="#" onclick= "window.open('../Others/Rose/Purchasing/lotsubconAuto.php','FAI1D','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=700,height=500'); return false;"><center>BE_KAPCO</center></a>
			<?php
				if(in_array($_SESSION['departmentId'],array(4,5)) OR $_GET['country']==2 OR ($_SESSION['idNumber']=='0449' AND in_array(date('Y-m-d'),array('2017-10-18','2017-10-24','2017-10-30'))) OR ($_SESSION['idNumber']=='0048' AND in_array(date('Y-m-d'),array('2018-01-12'))) OR ($_SESSION['idNumber']=='0470' AND in_array(date('Y-m-d'),array('2018-01-11'))) OR $_SESSION['idNumber']=='0466')
				{
					?>
					<button onclick="location.href='/<?php echo v;?>//4-10 Purchase Request Software/gerald_prForm.php';" class='api-btn' style='width:33%' data-api-title='<?php echo displayText('L1169', 'utf8', 0, 1);?>' ></button>
					<button onclick="location.href='gerald_purchaseOrderMakingSummary.php';" class='api-btn' style='width:33%' data-api-title='<?php echo displayText('L1170', 'utf8', 0, 1);?>' ></button>
					<?php
				}
			?>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436', 'utf8', 0, 1);?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			<form action='' method='post' id='formFilter' autocomplete="off"></form>	
			<table cellpadding="0" cellspacing="0" border="0" style='width:100%;'>
				<tr style='font-size:12px;'>
					<td style='width:10%' align='center' ><?php echo displayText('L45'); ?>		<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>//Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($lotNumber!='') echo 'background-color:red';?>'></td>
					<?php
						if($supplyType==2)
						{
							?>
							<td style='width:10%' align='center' ><?php echo displayText('L24'); ?>		<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>//Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td>
							<?php
						}
					?>					
					<td style='width:10%' align='center' ><?php echo displayText('L111'); ?>		<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>//Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($supplierType!='') echo 'background-color:red';?>'></td>
					<td style='width:10%' align='center' ><?php echo displayText('L367'); ?>		<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>//Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($supplierId!='') echo 'background-color:red';?>'></td>
					<td style='width:10%' align='center' ><?php echo displayText('L342')." ".displayText('L134'); ?>
					<td style='width:10%' align='center' ><?php echo displayText('L342')." ".displayText('L135'); ?>		
					<td align='left' style=''></td>
					
				</tr>
				<tr>
					<td><input list='lotNumber' name='lotNumber' class='api-form' value='<?php echo $lotNumber;?>' form='formFilter'><datalist id='lotNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'lotNumber',$lotNumber);?></datalist></td>
					<?php
						if($supplyType==2)
						{
							?>
							<td><select name='customerAlias' class='api-form' value='<?php echo $customerAlias;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);?></select></td>
							<?php
						}
					?>
					<td><select name='supplyType' class='api-form' value='<?php echo $supplyType;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'supplyType',$supplyType);?></select></td>
					<td><select name='supplierId' class='api-form' value='<?php echo $supplierId;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'supplierId',$supplierId);?></select></td>
					<td><input type='date' name='dateFrom' class='api-form' value='<?php echo $dateFrom;?>' form='formFilter'></td>
					<td><input type='date' name='dateTo' class='api-form' value='<?php echo $dateTo;?>' form='formFilter'></td>
					<td><button type='submit' class='api-btn' onclick="location.href='';" data-api-title='<?php echo displayText('B7', 'utf8', 0, 1);?>' <?php echo toolTip('L437');?> form='formFilter'></button></td>
				</tr>
			</table>
			<!------------------ End Filters ------------------>
			
			<!-------------------- Contents -------------------->
			
			<?php echo displayText('L41'); ?> : <span><?php echo $totalRecords; ?></span>
			<div style='height: 89%;'><!-- Adjust height if browser had a vertical scroll -->
				<table id='mainTableId' class="api-table-fixedheader api-table-design2" data-counter='-1' data-detail-type='left'>
					<thead>
						<tr>
							<th style='width:vw;'></th>
							<th style='width:vw;'><?php echo displayText('L45');?></th>
							<th style='width:vw;'><?php echo displayText('L303');?></th>
							<th style='width:vw;'><?php echo displayText('L1171');?></th>
							<th style='width:vw;'><?php echo displayText('L613');?></th>
							<th style='width:vw;'><?php echo displayText('L111');?></th>
							<th style='width:vw;'><?php echo displayText('L1309');?></th>
							<th style='width:vw;'><?php echo displayText('L62');?></th>
							<th style='width:vw;'><?php echo displayText('L367');?></th>
							<th style='width:vw;'><?php echo displayText('L1172');?></th>
							<th style='width:vw;'><?php echo displayText('L1173');?></th>
							<th style='width:vw;'><?php echo displayText('L267');?></th>
							<th style='width:vw;'><?php echo displayText('L37');?></th>
							<th style='width:vw;'><?php echo displayText('L1120');?></th>
						</tr>
					</thead>
					<tbody>
						
					</tbody>
					<tfoot>
						<tr>
							<th><input type='checkbox' name='checkall' id='chkAll'></th>
							<th><label for='chkAll'><?php echo displayText('L326'); ?></label></th>
							<th></th>
							<th></th>
							<th></th>
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
			<!------------------ End Contents ------------------>			
			
		</div>
	</div>
	
	<div id="modal01" class="w3-modal" onclick="this.style.display='none'">
		<div class="w3-modal-content w3-animate-zoom w3-round-large">
			<div style='margin:auto;margin-right:1%;'>
				<span id='closeModalId' onclick="document.getElementById('id01').style.display='none'" class="w3-closebtn w3-display-topright">&times;</span>
				<iframe name='boxName' style='width:100%;height:75vh;border:none;'></iframe>
			</div>
		</div>
	</div>	
</body>
<!-- <script src="/<?php echo v;?>//Common Data/Templates/jquery.js"></script> -->
<script src="/<?php echo v;?>//Common Data/Templates/api.jquery.js"></script>
<script>
	function colorThis(obj)
	{
		$("td.tempClass").css('background-color','');
		$("td").removeClass("tempClass");
		$(obj).parents("td").prop('class','tempClass');
		$("td.tempClass").css('background-color','orange');
	}
	
	$(function(){
		$("#mainTableId").apiQuickTable({
			url:'gerald_purchaseOrderMakingListAjax.php',
			filterSql:"<?php echo $sqlFilter." ".$sqlSort; ?>",
			recordCount:parseFloat("<?php echo $totalRecords/50;?>"),
			customFunction:function(){
				$('img.linkingClass').click(function(){
					//~ var indexa = $("img.linkingClass").index(this);
					var index = $(this).parent().parent().data('index');
					//~ alert(index);
					$("iframe[name=boxName]").attr('src','gerald_searchProductList.php?lotNumber='+$(this).attr('name')+'&index='+index);
					document.getElementById('modal01').style.display='block';
				});
				
				$('img.unLinkingClass').click(function(){
					var thisObj = $(this);
					var index = $("img.unLinkingClass").index(this);

					$.ajax({
						url:'gerald_purchaseOrderMakingSql.php',
						type:'post',
						data:{
							ajaxType:'unLinkedProduct',
							lotNumber:thisObj.attr('name')
						},
						success:function(data){
							//~ $("#asd", window.parent.document).text(data);
							console.log(data);
							
							$("td.supplierAlias:eq("+index+")").html('');
							$("td.productName:eq("+index+")").html('');
							$("td.productDescription:eq("+index+")").html('');					
							$("td.price:eq("+index+")").html('');
							$("td.totalPrice:eq("+index+")").html('');
							
							thisObj.hide();
							$("img.linkingClass:eq("+index+")").show();
						}
					});
				});
				
				$('img.removeFromListClass').click(function(){
					var thisObj = $(this);
					var index = $("img.removeFromListClass").index(this);
					
					swal({
						title: 'Are you sure you want to remove this from the list?',
						type: 'info',
						showCancelButton: true,
						allowOutsideClick: false,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#d33',
						confirmButtonText: 'Yes'
					}).then(function(){
						$.ajax({
							url:'gerald_purchaseOrderMakingSql.php',
							type:'post',
							data:{
								ajaxType:'removeItem',
								lotNumber:thisObj.attr('name')
							},
							success:function(data){
								location.reload();
							}
						});						
					})
					
					//~ if(confirm('Are you sure you want to remove this from the list?'))
					//~ {
						//~ $.ajax({
							//~ url:'gerald_purchaseOrderMakingSql.php',
							//~ type:'post',
							//~ data:{
								//~ ajaxType:'removeItem',
								//~ lotNumber:thisObj.attr('name')
							//~ },
							//~ success:function(data){
								//~ location.reload();
							//~ }
						//~ });
					//~ }
				});
				
				$("td.editTableNumber").dblclick(function(e){
					var thisObj = $(this);
					if(!$("#inputNumberId").length)
					{
						var oldVal = thisObj.text();
						thisObj.text('');
						var thisWitdh = thisObj.width();
						//~ var inputNumber = $("<input type='number' id='inputNumberId' step='any' class='api-form' value='"+oldVal+"' style='position:relative;width:auto;max-width:"+thisWitdh+"'>");
						var inputNumber = $("<input type='number' id='inputNumberId' step='any' class='api-form' value='"+oldVal+"' style='position:relative;width:"+thisWitdh+"'>");
						inputNumber.appendTo(thisObj);
						$("#inputNumberId").focus();
						$("#inputNumberId").blur(function(){
							var newVal = $(this).val(),
								lote = thisObj.attr('data-lote');
							
							thisObj.text(newVal);
							if(newVal != oldVal)
							{
								$.ajax({
									url		: "<?php echo $_SERVER['PHP_SELF'];?>",
									type	: "POST",
									data	:{
										ajaxType:'editQuantity',
										lotNumber:lote,
										newValue:newVal
									},
									success	: function(data){
										if(data.trim()!='')
										{
											alert(data);
											thisObj.text(oldVal);
										}
										else
										{
											thisObj.text(newVal);
										}
									}
								});
							}
						});
					}
				});					
			}
		});
		
		$("select.api-form").change(function(){
			if($(this).val()=='')	this.form.submit();
		});
		
		$('body').removeClass('api-loading');
		$(window).bind('beforeunload',function(){
			$('body').addClass('api-loading');
		});
	});
	
	//  -------------------------------------------------- For Modal Box Javascript Code -------------------------------------------------- //
	function jsFunctions(){
		
	}	
	//  ------------------------------------------------ END For Modal Box Javascript Code ------------------------------------------------ //
</script>
<!-- -----------------------------------Tiny Box------------------------------------------------------------- -->
<script type="text/javascript" src="/<?php echo v;?>//Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v;?>//Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script type="text/javascript">
function openTinyBox(w,h,url,post,iframe,html,left,top)
{
	var windowWidth = $(window).width();
	var windowHeight = $(window).height();
	TINY.box.show({
		url:url,width:w,height:h,post:post,html:html,opacity:20,topsplit:6,animate:false,close:true,iframe:iframe,left:left,top:top,
		boxid:'box',
		openjs:function(){
			if($("#tableDiv").length != 0 )
			{
				var windowHeight = $(window).height() / 1.5;
				var tinyBoxHeight = $("#box").height();
				if(tinyBoxHeight > (windowHeight))
				{
					$("#tableDiv").css({'overflow-y':'scroll','overflow-x':'hidden','height':(windowHeight) + 'px'});
					$("#box").css('height',(windowHeight) +'px');
					$("#box").css('width',($("#box").width() + 20 ) +'px');
				}
			}
		}
	});
}
</script>   
<!-- -----------------------------------END SMALL BOX----------------------------------------------------------------> 
</html>
