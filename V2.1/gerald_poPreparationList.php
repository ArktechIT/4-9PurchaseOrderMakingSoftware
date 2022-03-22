<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/rose_prodfunctions.php');
	ini_set("display_errors", "on");

	$tpl = new PMSResponsive;

	$tpl->setDataValue("L437"); // Filter
	$tpl->setAttribute("id","filterData");
	$tpl->setAttribute("type","button");
	$buttonFilter = $tpl->createButton();

	$tpl->setDataValue("L436"); // Refresh
	$tpl->setAttribute("onclick","location.href=''");
	$tpl->setAttribute("type","button");
	$buttonRefresh = $tpl->createButton();
	
	//~ if($_SESSION['idNumber']!='0346')
	//~ {
		//~ header('location:../gerald_purchaseOrderMakingList.php');
		//~ exit(0);
	//~ }
	
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
	if(isset($_POST['ajaxType']) AND $_POST['ajaxType']=='updateRemarks')
	{
		$listId = $_POST['listId'];
		$remarks = $_POST['remarks'];
		
		echo $sql = "UPDATE purchasing_forpurchaseorder SET itemRemarks = '".$remarks."' WHERE listId = ".$listId." LIMIT 1";
		$queryUpdate = $db->query($sql);
		
		exit(0);
	}
	if(isset($_POST['ajaxType']) AND $_POST['ajaxType']=='updateData')
	{
		$listId = $_POST['listId'];
		$newValue = $_POST['newValue'];
		$column = $_POST['column'];
		
		$dateNeededOld = '0000-00-00';
		
		if($column=='itemQuantity' OR $column=='itemPrice')
		{
			if($newValue <= 0)
			{
				echo "Invalid Input";
				exit(0);
			}
		}
		else if($column=='dateNeeded')
		{
			if(strtotime($newValue) < date('Y-m-d'))
			{
				echo "Invalid Input";
				exit(0);
			}
			
			$sql = "SELECT dateNeeded FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
			$queryDateNeeded = $db->query($sql);
			if($queryDateNeeded AND $queryDateNeeded->num_rows > 0)
			{
				$resultDateNeeded = $queryDateNeeded->fetch_assoc();
				$dateNeededOld = $resultDateNeeded['dateNeeded'];
			}
		}		
		
		$sql = "UPDATE purchasing_forpurchaseorder SET ".$column." = '".$newValue."' WHERE listId = ".$listId." LIMIT 1";
		$queryUpdate = $db->query($sql);
		
		if($column=='itemQuantity')
		{
			$sql = "SELECT lotNumber FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
			$queryForPurchaseOrder = $db->query($sql);
			if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
			{
				$resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc();
				$lotNumber = $resultForPurchaseOrder['lotNumber'];
				
				$sql = "UPDATE ppic_lotlist SET workingQuantity = '".$newValue."' WHERE lotNumber LIKE '".$lotNumber."' AND identifier = 4 LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
		}
		
		if($column=='dateNeeded')
		{
			$lotNumber = '';
			$sql = "SELECT lotNumber FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
			$queryDateNeeded = $db->query($sql);
			if($queryDateNeeded AND $queryDateNeeded->num_rows > 0)
			{
				$resultDateNeeded = $queryDateNeeded->fetch_assoc();
				$lotNumber = $resultDateNeeded['lotNumber'];
			}				
			
			$materialComputationId = '';
			$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' ORDER BY materialComputationId DESC LIMIT 1";
			$queryMaterialComputation = $db->query($sql);
			if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
			{
				$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
				$materialComputationId = $resultMaterialComputation['materialComputationId'];
			}
			
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId;
			$queryMaterialComputationDetails = $db->query($sql);
			if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
			{
				while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
				{
					$lotNumberArray[] = $resultMaterialComputationDetails['lotNumber'];
				}
			}
			
			$loteArray = array();
			$sql = "SELECT DISTINCT lotNumber FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode IN(372,381,382,401,403,499,314,378) AND targetFinish <= '".$newValue."'";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$loteArray[] = $resultWorkSchedule['lotNumber'];
				}
			}
			
			if(count($loteArray) > 0)
			{
				foreach($loteArray as $lotNumber)
				{
					$notificationIdArray = array();
					$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey = '".$lotNumber."' AND notificationType = 28";
					$queryNotificationDetails = $db->query($sql);
					if($queryNotificationDetails AND $queryNotificationDetails->num_rows > 0)
					{
						while($resultNotificationDetails = $queryNotificationDetails->fetch_assoc())
						{
							$notificationIdArray[] = $resultNotificationDetails['notificationId'];
						}
					}
					
					$sql = "SELECT listId FROM system_notification WHERE notificationId IN(".implode(",",$notificationIdArray).") AND notificationStatus = 0 LIMIT 1";
					$queryNotification = $db->query($sql);
					if(($queryNotification AND $queryNotification->num_rows == 0) OR count($notificationIdArray)==0)
					{
						$notificationDetail = 'PO Receiving Date of this item has been moved';
						//~ $notificationLink = '/V3/16%20Lot%20Details%20Management%20Software%20V4/ace_lotDetails.php?barcode2='.$lotNumber.'&formDoor%5B%5D=1&formDoor%5B%5D=2&formDoor%5B%5D=3';
						$notificationLink = '/'.v.'/16%20Lot%20Details%20Management%20Software/ace_lotDetails.php?submitButton=Submit&inputLot='.$lotNumber;
						
						$sql = "INSERT INTO `system_notificationdetails`
										(	`notificationDetail`,		`notificationKey`,	`notificationLink`,			`notificationType`)
								VALUES	(	'".$notificationDetail."',	'".$lotNumber."',	'".$notificationLink."',	'28')";
						$queryInsert = $db->query($sql);
						
						$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
						$query = $db->query($sql);
						$result = $query->fetch_array();
						$notificationId = $result['max'];
						
						$notificationLink .= '&notificationId='.$notificationId;
						
						$sql = "UPDATE system_notificationdetails SET notificationLink = '".$notificationLink."' WHERE notificationId = ".$notificationId." LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						if($_GET['country']==2)
						{
							$sql = "INSERT INTO `system_notification`
											(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
									VALUES	(	'".$notificationId."',	'J014',						'0',					'2')";
							$queryInsert = $db->query($sql);	
						}
						else
						{
							$sql = "INSERT INTO `system_notification`
											(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
									VALUES	(	'".$notificationId."',	'0458',						'0',					'2')";
							$queryInsert = $db->query($sql);	
						}
					}
				}
			}
		}	
		
		exit(0);
	}
	
	if(isset($_GET['finishPoPreparation']))
	{
		$purchaseReviewFlag = 0;
		$sql = "
			SELECT a.id, a.lotNumber, a.processRemarks, b.supplierType FROM view_workschedule as a
			INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
			WHERE a.processCode = 597 AND a.status = 0
		";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				if($resultWorkSchedule['supplierType']==2 OR $_GET['country']==2)
				{
					if($resultWorkSchedule['supplierType']==2 AND $_GET['country']==1)
					{
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$processRemarks = $resultWorkSchedule['processRemarks'];
						
						$sql = "
							SELECT a.id FROM view_workschedule as a
							LEFT JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
							WHERE a.lotNumber LIKE '".$lotNumber."' AND processCode = 597 AND a.status = 0 AND IFNULL(b.processRemarks,'asd') = 'asd'
						";
						$queryWorkSched = $db->query($sql);
						if($queryWorkSched AND $queryWorkSched->num_rows > 0)
						{
							continue;
						}
					}
					
					finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],$resultWorkSchedule['processRemarks']);
				}
				else
				{
					$purchaseReviewFlag = 1;
				}
			}
		}
		
		if($purchaseReviewFlag==0)
		{
			//~ header('location:'.$_SERVER['PHP_SELF']);
			header('location:gerald_purchaseOrderMakingSummary.php');
		}
		else
		{
			header('location:gerald_purchaseOrderReview.php');
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
									location.href='';
									parent.location.reload();
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
	
    $prNumber = isset($_GET['prNumber']) ? $_GET['prNumber'] : "";
    $lotNumber = isset($_POST['lotNumber']) ? $_POST['lotNumber'] : "";
	$supplyType = (isset($_POST['supplyType'])) ? $_POST['supplyType'] : '';
	$supplierId = (isset($_POST['supplierId'])) ? $_POST['supplierId'] : '';
	$itemName = (isset($_POST['itemName'])) ? $_POST['itemName'] : '';
	$itemDescription = (isset($_POST['itemDescription'])) ? $_POST['itemDescription'] : '';
	$desc = (isset($_POST['desc'])) ? $_POST['desc'] : '';
	
	$sqlFilterArray = array();
	if($lotNumber!='')	$sqlFilterArray[] = "lotNumber LIKE '".$lotNumber."'";
	if($desc!='')	$sqlFilterArray[] = "processRemarks LIKE '%".$desc."%'";
	
	$sqlFilterForPurchaseArray = array();
	if($supplierType!='')		$sqlFilterForPurchaseArray[] = "supplierType = ".$supplierType."";
	if($supplierId!='')			$sqlFilterForPurchaseArray[] = "supplierId = ".$supplierId."";
	if($itemName!='')			$sqlFilterForPurchaseArray[] = "itemName LIKE '".$itemName."'";
	if($itemDescription!='')	$sqlFilterForPurchaseArray[] = "itemDescription LIKE '".$itemDescription."'";

    if(count($sqlFilterForPurchaseArray) > 0)
    {
        $sqlFilterForPurchase = "WHERE ".implode(' AND ',$sqlFilterForPurchaseArray );
        
        $workSchedIdArray = array();
        $sql = "SELECT lotNumber, processRemarks FROM purchasing_forpurchaseorder ".$sqlFilterForPurchase;
        $queryForPurchaseOrder = $db->query($sql);
        if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
        {
			while($resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc())
			{
				$lotNumber = $resultForPurchaseOrder['lotNumber'];
				$processRemarks = $resultForPurchaseOrder['processRemarks'];
				
				$sql = "SELECT id FROM view_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processRemarks LIKE '".$processRemarks."' AND processCode = 597 AND status = 0 LIMIT 1";
				$queryWorkSched = $db->query($sql);
				if($queryWorkSched AND $queryWorkSched->num_rows > 0)
				{
					$resultWorkSched = $queryWorkSched->fetch_assoc();
					$workSchedIdArray[] = $resultWorkSched['id'];
				}
			}
		}
		
		$sqlFilterArray[] = "id IN('".implode("','",$workSchedIdArray)."')";
    }
	
	$sql = "SELECT lotNumber FROM ppic_lotlist WHERE identifier IN(1,4)";
	if($supplyType!='')
	{
		if($supplyType==2)
		{
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE identifier = 1";
		}
		else
		{
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE identifier = 4 AND status = ".$supplyType."";
		}
	}	
	$sqlFilterArray[] = "lotNumber IN(".$sql.")";
	
	if($prNumber!='')
	{
		$lotNumberArray = array();
		$sql = "SELECT lotNumber FROM purchasing_prcontent WHERE prNumber LIKE '".$prNumber."'";
		$queryPrContent = $db->query($sql);
		if($queryPrContent AND $queryPrContent->num_rows > 0)
		{
			while($resultPrContent = $queryPrContent->fetch_assoc())
			{
				$lotNumberArray[] = $resultPrContent['lotNumber'];
			}
		}
		
		$sqlFilterArray[] = "lotNumber IN('".implode("','",$lotNumberArray)."')";
	}	
	
	$orderBy = "ORDER BY targetFinish, lotNumber";
	$sqlFilter = " WHERE processCode = 597 AND processSection = 5";
    if(count($sqlFilterArray) > 0)
    {
        $sqlFilter .= " AND ".implode(' AND ',$sqlFilterArray );
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

		$checkedShow = "";
		if(isset($_POST['showNoSupplier']) AND $_POST['showNoSupplier'] == "on")
		{
			$checkedShow = "checked";
			if($lotNumberArray != NULL)
			{	
				$lotArray = [];
				foreach ($lotNumberArray as $lotValue) 
				{
					$sql = "SELECT lotNumber FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotValue."'";
					$queryCheck = $db->query($sql);
					if($queryCheck AND $queryCheck->num_rows == 0)
					{
						$lotArray[] = $lotValue;
					}
					else
					{
						$sql = "SELECT lotNumber FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotValue."' AND supplierId = 0";
						$queryCheck = $db->query($sql);
						if($queryCheck AND $queryCheck->num_rows > 0)
						{
							$lotArray[] = $lotValue;
						}
					}
				}

				$sqlFilter .= " AND lotNumber IN('".implode("','",$lotArray)."')";
			}
		}
		else
		{
			$sqlFilter .= " AND lotNumber IN('".implode("','",$lotNumberArray)."')";
		}
	}
    
    $totalRecords = 0;
	$sql = "SELECT * FROM view_workschedule ".$sqlFilter." ".$orderBy;//gerald payables
	$query = $db->query($sql);
	if($query AND $query->num_rows > 0)
	{
		$totalRecords = $query->num_rows;
	}
	
	//~ $sqlData = $sql;
	$sqlData = trim(preg_replace('/\s+/', ' ', $sql));

    $exportBTN = $tpl->setDataValue("L487")
                   ->setAttribute([
                        "form"  => "exportFormId",
                        "name"  => "exportFlag",
						"value" => "exportXLS"
                   ])
                   ->createButton();
    
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo displayText('L4170', 'utf8', 0, 1);?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/w3css/w3.css">
    <link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">
	<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
	<link rel="stylesheet" href="/<?php echo v; ?>/Others/Sam/css/bootstrap.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Others/Sam/aos.css">
    <link rel="stylesheet" href="/<?php echo v; ?>/Others/Sam/animate.min.css">
	<style>
        .dataTables_wrapper .dataTables_filter {
			position: absolute;
			text-align: right;
			visibility: hidden;
		}
        
        body
		{
			font-size: 11px;
			font-family: Roboto;
			margin:0px;
			padding:0px;
			background-color:whitesmoke;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content, .dropdown-content-filter {
            display: none;
            position: absolute;
            background-color:white;
            z-index: 9999999;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
		.animate__animated.animate__rotateOutDownLeft {
    --animate-duration: 5s;
    }
    @media only screen and (orientation:portrait) {
        #divFull{
            display: block !important;
            position: fixed;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 100%;
            z-index: 10000;
        }
    }
    @media only screen and (max-device-width: 568px) {
        #divFull{
            display: none !important;
        }
        #divFullm {
            display: block !important;
            position: fixed;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 100%;
            z-index: 10000;
           
        }
    }
	@media only screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape) {
		th, td{
			font-size: 10px !important;
		}
		.dataTables_scroll{
			zoom: 0.7 !important;
		}
		#mainTableId{
			height: 40vh !important;
		}
		.dataTables_scrollBody{
			height: 50vh !important;
		}
    }
	</style>
</head>
<body>
<div id='divFullm' class="divFull text-center" style="background-color: #96D6F7; display: none">
    <img class="mx-auto animate__animated animate__backInDown" style="height: 300px; margin-top: 15%;" src="/V4/Others/Sam/img/samsam.png" alt="">
    <div class="w-100 text-center">
        <h5 class="text-muted fw-bolder">Sorry... This page is not available for mobile view.</h5>
    </div>
    <div class="w-100 text-center mt-5">
        <h5 class="text-muted fw-bolder">Redirecting to main menu...</h5>
        <h3 class="text-muted fw-bolder animate__animated animate__fadeInDown animate__infinite" id='bilang'></h3>
    </div>
</div>
<div id='divFull' class="divFull text-center" style="background-color: #96D6F7; display: none;">
    <img class="mx-auto animate__animated animate__rotateOutDownLeft animate__infinite" style="height: 300px; margin-top: 5%; overflow: auto;" src="/V4/Others/Sam/img/rotate2.png" alt="">
    <img class="mx-auto" style="height: 300px; margin-top: 32%; margin-left: -20% !important;" src="/V4/Others/Sam/img/rotate1.png" alt="">
    <div class="w-100 text-center">
        <h3 class="text-muted fw-bolder">Rotate the device for best view.</h3>
    </div>
</div>
<?php
	$displayId = "L4170";
    $version = "";
    $previousLink = "/".v."/4-14%20Purchasing%20Software/raymond_purchasingSoftware.php";
    createHeader($displayId, $version, $previousLink);
?>
	<form id='exportFormId' action='gerald_poPreparationListAjax.php' method='POST'></form>
	<form action='' method='post' id='formFilter'></form>
	<form action='' method='post' id='formShow'></form>
	<input type='hidden' name='sqlData' value="<?php echo $sqlData;?>" form='exportFormId'>
    <div class="container-fluid">
			<div class="row w3-padding-top"></div>
			<div class="row w3-padding-top">
				<div class="col-md-3">
					<?php 
						if($_GET['country']==1)
						{
							?>
							<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='/<?php echo v; ?>/4-9%20Purchase%20Order%20Making%20Software/gerald_purchaseOrderMakingList.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('4-9', 'utf8', 0, 2); ?></b></button>
							<!--a href="#" onclick= "window.open('/<?php echo v; ?>/Others/Rose/Purchasing/lotsubconAuto.php','FAI1D','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=700,height=500'); return false;">BE_KAPCO</a-->
							<?php
						}
					?>
				</div>
				<div class="col-lg-9 col-md-12 mt-md-3" style='text-align:right;'>
					<input form='formShow' type='checkbox' name='showNoSupplier' <?php echo $checkedShow; ?> onchange='this.form.submit();'>
					<label>Show No Supplier</label>
					<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='gerald_purchaseOrderManualInput.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('B3', 'utf8', 0, 2); ?></b></button>
					<?php 
						//~ if($_GET['country']==1)
						//~ {
							?>					
							<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='gerald_insertForPurchase.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo "Subcon Auto Link";//displayText('L1169', 'utf8', 0, 2); ?></b></button>
							<?php
						//~ }
					?>
					<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='/<?php echo v; ?>/4-10 Purchase Request Software/gerald_prForm.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('L1169', 'utf8', 0, 2); ?></b></button>
<!--
					<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='/<?php echo v; ?>/4-10 Purchase Request Software/V2.0/gerald_purchaseRequestForm.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('L1169', 'utf8', 0, 2); ?></b></button>
-->
<!--
                	<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='gerald_purchaseOrderMakingSummary.php'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('L1170', 'utf8', 0, 2); ?></b></button>
-->
                	<button class='w3-btn w3-round w3-small w3-green' onclick="location.href='<?php echo $_SERVER['PHP_SELF']."?finishPoPreparation=1";?>'"><i class='fa fa-bookmark'></i>&emsp;<b><?php echo displayText('L1170', 'utf8', 0, 2); ?></b></button>
					<?php echo $buttonFilter.$buttonRefresh.$exportBTN; ?>
					<!-- <button class='w3-btn w3-tiny w3-pink w3-round' id='filterData'><i class='fa fa-list'></i> &emsp;<b><?php echo displayText('B7');?></b></button> -->
<!--
					<div class="dropdown">
						<button class='w3-btn w3-tiny w3-indigo w3-round'><i class='fa fa-cog'></i> &emsp;<b><?php echo displayText('L435');?></b></button>
						<div class="dropdown-content">
							<div class='w3-padding-top'></div>
							<button style='width:150px;' class='functionButton w3-btn w3-tiny w3-green w3-round' onclick="location.href='<?php echo $_SERVER['PHP_SELF']."?finishPoPreparation=1";?>';"><i class='fa fa-plus'></i> &emsp;<b><?php echo "Finish PO Preparation";?></b></button>
						</div>
					</div>
-->
					<!-- <button class='w3-btn w3-tiny w3-round w3-green' onclick="location.href='';"><i class='fa fa-refresh'></i>&emsp;<b><?php echo displayText('L436');?></b></button> -->
				</div>
			</div>
        <div class="row">
            <div class="col-md-12 w3-padding-top">
                <?php
                echo "<label>".displayText('L41', 'utf8', 0, 1)." : ".$totalRecords."</label>";
                if($rfqNumber!='')
                {
					echo "&nbsp; Copy Referer : <input type='text' class='w3-pale-green' style='width:425px;' readonly value='\\\SERVER\www\html\"".v."\2-7 Request For Quotation List V2\CAD File\\".$rfqNumber."'>";
				}
                ?>
                <table class='table table-bordered table-condensed table-striped w-100' id="mainTableId">
                    <thead class='w3-indigo thead' style='text-transform:uppercase;'>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L45');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L303');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1171');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L613');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L111');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1309');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L62');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L367');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1172');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1173');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L267');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L37');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L242');?></th>
						<th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1120');?></th>
                    </thead>
                    <tbody class='tbody'>
                    
                    </tbody>
                    <tfoot class='w3-indigo thead'>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                        <th style='vertical-align:middle;' class='w3-center'></th>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div id='modal-izi-filter'><span class='izimodal-content-filter'></span></div>
    <div id='modal-izi-function'><span class='izimodal-content-function'></span></div>
    <div id='modal-izi-help'><span class='izimodal-content-help'></span></div>
    <div id='modal-izi-product'><span class='izimodal-content-product'></span></div>
    <div id='modal-izi-remove'><span class='izimodal-content-remove'></span></div>
</body>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/daterangepicker.min.css">
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/moment.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/jquery.daterangepicker.min.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/css/bootstrap-multiselect.css" type="text/css" media="all" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/js/bootstrap-multiselect.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziModal-master/css/iziModal.css" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziModal-master/js/iziModal.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziToast-master/dist/css/iziToast.css" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziToast-master/dist/js/iziToast.js"></script>
<script>
if($('#divFullm').css('display') == 'block')
{
    babalik();
}
    
function babalik(){
    var bilang = document.getElementById('bilang');
    var counter = 6;
    var interval = setInterval(()=> {
        counter--;
        bilang.innerText =counter;
        if (counter == 0) {
            clearInterval(interval);
            location.href="/V4/1-15%20Sales%20Software/raymond_salesSoftware.php";
        }
    }, 1000);
    
}
</script>
<script type="text/javascript">	

function removeData(link)
{
    $("#modal-izi-remove").iziModal({
        title                   : '<i class="fa fa-trash"></i> <?php echo displayText("L678", 'utf8', 0, 1); ?>', // Remove
        headerColor             : '#1F4788',
        subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
        width                   : 400,
        fullscreen              : false,
        iframe                  : true,
        iframeURL               : link,
        transitionIn            : 'comingIn',
        transitionOut           : 'comingOut',
        padding                 : 20,
        radius                  : 0,
        top                     : 100,
        restoreDefaultContent   : true,
        closeOnEscape           : true,
        closeButton             : true,
        overlayClose            : false,
        onOpening               : function(modal){
                                    
                                },
            onClosed            : function(modal){
                                    $("#modal-izi-remove").iziModal("destroy");
                    }
    });

    $("#modal-izi-remove").iziModal("open");
}	

function linkProductModal(workSchedId,type)
{
	if(type==1)
	{
		$.ajax({
			url         : 'gerald_searchProductList.php',
			type        : 'POST',
			data        : {
								workSchedId      : workSchedId,
								type      : type
			},
			success     : function(data){
							location.reload();
			}
		});
	}
	else
	{
		$("#modal-izi-product").iziModal({
			title                   : '<i class="fa fa-info"></i>&emsp;<?php echo displayText("L1310", 'utf8', 0, 1);?>',
			headerColor             : '#1F4788',
			//~ subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
			width                   : 800,
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
											url         : 'gerald_searchProductList.php',
											type        : 'POST',
											data        : {
																workSchedId      : workSchedId,
																type      : type
											},
											success     : function(data){
															$( ".izimodal-content-product" ).html(data);
															modal.stopLoading();
											}
										});
									},
			onClosed                : function(modal){
										$("#modal-izi-product").iziModal("destroy");
						} 
		});

		$("#modal-izi-product").iziModal("open");
	}
}

function updateRemarks(obj)
{
	var remarks = $(obj).val();
	var listId = $(obj).data('listId');
	
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"post",
		data:{
			ajaxType:'updateRemarks',
			listId:listId,
			remarks:remarks
		},
		success:function(data){
			//~ alert(data);
		}
	});
}

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

$(document).ready(function(event){
    var sql = "<?php echo $sqlData; ?>";
    var totalRecords = "<?php echo $totalRecords; ?>";
    var dateFrom = "<?php echo $dateFrom; ?>";
    var assySheetWorksId = "<?php echo $assySheetWorksId; ?>";
    var dataTable = $('#mainTableId').DataTable({
        "processing"    : true,
        "ordering"      : false,
        "serverSide"    : true,
        "bInfo"         : false,
        "ajax"          :{
            url     : "gerald_poPreparationListAjax.php", // json datasource
            type    : "POST",  // method  , by default get
            data    : {
                        "sqlData"                   : sql,
                        "totalRecords"              : totalRecords,
                        "dateFrom"					: dateFrom
                      },
            error   : function(){  // error handling
                $(".mainTableId-error").html("");
                $("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                $("#mainTableId_processing").css("display","none");
            }
        },
        "createdRow": function( row, data, index ) {
            var lote = $('td:eq(0)', row).text();
            var purchaseType = $('td:eq(4)', row).text();
            var details = $('td:eq(13)', row).text();
            
            //~ if(details.indexOf("CPAR-CUS")!=-1)
            if(details=='No Data')
            {
				$('td:eq(13)', row).css('background-color','pink');
			}
			
			$('td:eq(2)', row).blur(function(){
				var thisObj = $(this),
					editType = thisObj.attr('data-edit-type'),
					newValue = thisObj.text();
				
				if(thisObj.attr('data-old-value')!=newValue)
				{
					$.ajax({
						url		: "<?php echo $_SERVER['PHP_SELF'];?>",
						type	: "POST",
						data	:{
							ajaxType:'editQuantity',
							lotNumber:lote,
							newValue:newValue
						},
						success	: function(data){
							
						}
					});
				}
				thisObj.attr('contentEditable','false').css('background-color','').removeAttr('data-old-value');
			});
			
			$('td:eq(2)', row).dblclick(function(){
				if(purchaseType!='Subcon')
				{
					var thisObj = $(this);
					
					thisObj
						.attr('contentEditable','true')
						.focus()
						.css('background-color','#FFFF99')
						.attr('data-old-value',thisObj.text())
						.text(thisObj.text());					
					
					/*
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
							var newVal = $(this).val();
								//~ lote = thisObj.attr('data-lote');
							
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
					}*/					
				}
			});
        },
        "initComplete": function(settings, json) {
            $('body').find('.dataTables_scrollBody').addClass("scrollbar");
        },
        "columnDefs": [
                        // {
                        //     "targets"       : [ 1, 2, 3, hiddenIndex, packHidden],
                        //     "visible"       : false,
                        //     "searchable"    : true
                        // }
                        // {
                        //     targets: -1,
                        //     className: 'dt-body-right'
                        // }
                        {
                            "targets" 		: [ 0 ],
                            "width"			: "5%"
                        }
        ],
        language    : {
                    processing  : ""
        },
        fixedColumns:   {
                leftColumns: 0
        },
        scrollX         : true,
        scrollY         : 570,
        scrollCollapse  : false,
        scroller        : {
            loadingIndicator    : true
        },
        stateSave       : false
    });
    
    $("#chkAll").change(function(){
		$(".chkbox").not(':disabled').prop('checked', $(this).prop("checked")); //change all ".checkbox" checked status
	});
    
    $("#filterData").click(function(){
        $("#modal-izi-filter").iziModal({
            title                   : '<i class="fa fa-flash"></i> <?php echo strtoupper(displayText("B7"));?>',
            headerColor             : '#1F4788',
            subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
            width                   : 1200,
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
                                            url         : 'gerald_poPreparationListFilter.php',
                                            type        : 'POST',
                                            data        : {
                                                            sqlData      : sql,
                                                            postVariable : "<?php echo str_replace('"',"'",json_encode($_POST));?>"
                                            },
                                            success     : function(data){
                                                            $( ".izimodal-content-filter" ).html(data);
                                                            modal.stopLoading();
                                            }
                                        });
                                    },
            onClosed                : function(modal){
                                        $("#modal-izi-filter").iziModal("destroy");
                        } 
        });

        $("#modal-izi-filter").iziModal("open");
    });
    
    $(".functionButton").click(function(){
		var thisId = $(this).attr('id');
		
		if(thisId=='extractZip' || thisId=='importExcel')
		{
			var url = (thisId=='extractZip') ? 'gerald_extractZip.php' : 'gerald_importExcel.php';
			
			$("#modal-izi-function").iziModal({
				title                   : '<i class="fa fa-flash"></i> <?php echo strtoupper(displayText("B7"));?>',
				headerColor             : '#1F4788',
				subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
				width                   : 500,
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
												url         : url,
												type        : 'POST',
												data        : {
																//~ sqlData      : sql,
																//~ postVariable : "<?php echo str_replace('"',"'",json_encode($_POST));?>"
												},
												success     : function(data){
																$( ".izimodal-content-function" ).html(data);
																modal.stopLoading();
												}
											});
										},
				onClosed                : function(modal){
											$("#modal-izi-function").iziModal("destroy");
							} 
			});
		}
		else if(thisId=='editSchedule')
		{
			$("#modal-izi-function").iziModal({
				title                   : '<i class="fa fa-flash"></i> <?php echo strtoupper(displayText("B7"));?>',
				headerColor             : '#1F4788',
				subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
				width                   : 1200,
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
												url         : 'gerald_rfqListInputForm.php',
												type        : 'POST',
												data        : {
																//~ sqlData      : sql,
																//~ postVariable : "<?php echo str_replace('"',"'",json_encode($_POST));?>"
												},
												success     : function(data){
																$( ".izimodal-content-function" ).html(data);
																modal.stopLoading();
												}
											});
										},
				onClosed                : function(modal){
											$("#modal-izi-function").iziModal("destroy");
							} 
			});			
		}

        $("#modal-izi-function").iziModal("open");
    });
    
	$("#helpBtn").click(function(){
		$("#modal-izi-help").iziModal({
			title                   : '<i class="fa fa-info"></i>&emsp;<?php echo strtoupper(displayText("L3586"));?>',
			headerColor             : '#1F4788',
			subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
			width                   : 800,
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
											url         : '/<?php echo v; ?>/Common Software/raymond_softwareHelpInfo.php',
											type        : 'POST',
											data        : {
																type      : 1,
																displayId   : '4-4'
											},
											success     : function(data){
															$( ".izimodal-content-help" ).html(data);
															modal.stopLoading();
											}
										});
									},
			onClosed                : function(modal){
										$("#modal-izi-help").iziModal("destroy");
						} 
		});

		$("#modal-izi-help").iziModal("open");
	});
});
</script>
