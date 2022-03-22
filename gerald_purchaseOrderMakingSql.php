<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/gerald_poMonitoringFunction.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");

	function updateWorkschedulePoContentId($inputLot)
	{
		include('PHP Modules/mysqliConnection.php');
	
		$sql = "SELECT id, processCode, processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$inputLot."' AND processCode IN(145,172,228) ORDER BY processOrder";
		$queryDeliveryToSubcon = $db->query($sql);
		if($queryDeliveryToSubcon AND $queryDeliveryToSubcon->num_rows > 0)
		{
			$partId = '';
			$sql = "SELECT partId, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$inputLot."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_array();
				$partId = $resultLotList['partId'];
			}		
			
			while($resultDeliveryToSubcon = $queryDeliveryToSubcon->fetch_assoc())
			{
				$id = $resultDeliveryToSubcon['id'];
				$processCode = $resultDeliveryToSubcon['processCode'];
				$processOrder = $resultDeliveryToSubcon['processOrder'];
				
				if($processCode==145)		$subconOrder = '1,0';
				else if($processCode==172)	$subconOrder = '2';
				else if($processCode==228)	$subconOrder = '3';
				
				$processRemarks = '';
				$subconProcessArray = array();
				$sql = "SELECT processCode FROM cadcam_subconlist WHERE partId = ".$partId." AND subconOrder IN(".$subconOrder.")";
				$querySubconList = $db->query($sql);
				if($querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_array())
					{
						$subconProcessArray[] = $resultSubconList['processCode'];
					}
				}
				
				$treatmentNameArray = array();
				$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId IN(".implode(",",$subconProcessArray).")";
				$queryTreatment = $db->query($sql);
				if($queryTreatment AND $queryTreatment->num_rows > 0)
				{
					while($resultTreatment = $queryTreatment->fetch_assoc())
					{
						$treatmentNameArray[] = $resultTreatment['treatmentName'];
					}
					
					$poContentIds = '';
					$sql = "SELECT GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ',') as poContentIds FROM purchasing_pocontents WHERE lotNumber LIKE '".$inputLot."' AND dataThree IN('".implode("','",$treatmentNameArray)."') AND itemStatus != 2 GROUP BY lotNumber";
					$queryPoContents = $db->query($sql);
					if($queryPoContents AND $queryPoContents->num_rows > 0)
					{
						$resultPoContents = $queryPoContents->fetch_assoc();
						$poContentIds = $resultPoContents['poContentIds'];
					}
					
					$sql = "UPDATE ppic_workschedule SET poContentIds = '".$poContentIds."' WHERE lotNumber LIKE '".$inputLot."' AND processOrder >= ".$processOrder." ORDER BY processOrder";
					$queryUpdate = $db->query($sql);
				}
			}
		}	
	}

	function updateReceivingProcessRemarks($inputLot)
	{
		include('PHP Modules/mysqliConnection.php');
		
		// ****************************** UPDATE Process Remarks of Receiving (Warehouse) Process (2016-10-26) ****************************** //
		$sql = "SELECT id, processCode, processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$inputLot."' AND processCode IN(137,138,229) AND processRemarks = '' ORDER BY processOrder";
		$queryReceivingWarehouse = $db->query($sql);
		if($queryReceivingWarehouse AND $queryReceivingWarehouse->num_rows > 0)
		{
			$partId = '';
			$sql = "SELECT partId, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$inputLot."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_array();
				$partId = $resultLotList['partId'];
			}		
			
			while($resultReceivingWarehouse = $queryReceivingWarehouse->fetch_assoc())
			{
				$id = $resultReceivingWarehouse['id'];
				$processCode = $resultReceivingWarehouse['processCode'];
				$processOrder = $resultReceivingWarehouse['processOrder'];
				
				if($processCode==137)		$subconOrder = '1,0';
				else if($processCode==138)	$subconOrder = '2';
				else if($processCode==229)	$subconOrder = '3';
				
				$processRemarks = '';
				$subconProcessArray = array();
				$sql = "SELECT processCode FROM cadcam_subconlist WHERE partId = ".$partId." AND subconOrder IN(".$subconOrder.")";
				$querySubconList = $db->query($sql);
				if($querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_array())
					{
						$subconProcessArray[] = $resultSubconList['processCode'];
					}
				}
				
				$rwProcessRemarksArray = array();
				$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId IN(".implode(",",$subconProcessArray).")";
				$queryTreatment = $db->query($sql);
				if($queryTreatment AND $queryTreatment->num_rows > 0)
				{
					while($resultTreatment = $queryTreatment->fetch_assoc())
					{
						$rwProcessRemarksArray[] = $resultTreatment['treatmentName'];
					}
					$processRemarks = implode(",",$rwProcessRemarksArray);
					
					$sql = "UPDATE ppic_workschedule SET processRemarks = '".$processRemarks."' WHERE id = ".$id." LIMIT 1";
					$queryUpdate = $db->query($sql);
					
					/*
					$poContentIds = '';
					$sql = "SELECT GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ',') as poContentIds FROM purchasing_pocontents WHERE lotNumber LIKE '".$inputLot."' AND dataThree IN('".implode("','",$rwProcessRemarksArray)."') AND itemStatus != 2 GROUP BY lotNumber";
					$queryPoContents = $db->query($sql);
					if($queryPoContents AND $queryPoContents->num_rows > 0)
					{
						$resultPoContents = $queryPoContents->fetch_assoc();
						$poContentIds = $resultPoContents['poContentIds'];
					}
					
					$sql = "UPDATE ppic_workschedule SET poContentIds = '".$poContentIds."' WHERE lotNumber LIKE '".$inputLot."' AND processOrder >= ".$processOrder." ORDER BY processOrder";
					$queryUpdate = $db->query($sql);
					*/
				}
			}
		}
		// **************************** END UPDATE Process Remarks of Receiving (Warehouse) Process (2016-10-26) **************************** //
	}
	
//~ print_r($_POST);
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='linkedProduct')
		{
			$lotNumber = $_POST['lotNumber'];
			$productId = $_POST['productId'];
			
			$supplyId = (isset($_GET['supplyId'])) ? $_GET['supplyId'] : "";
			$supplyType = (isset($_GET['supplyType'])) ? $_GET['supplyType'] : "";
			$sqlMain = $supplyId."\n".$supplierType;
			if($supplyId!="" AND $supplyType!="")
			{
				$sql = "INSERT INTO `purchasing_supplierproductlinking`
								(	`productId`,		`supplyId`,			`supplyType`)
						VALUES	(	'".$productId."',	'".$supplyId."',	'".$supplyType."')";
				$queryInsert = $db->query($sql);
			}
			
			$availability = 0;
			$subconFlag = 0;
			$subconProcessCount = 0;
			$processRemarks = '';
			$sql = "SELECT partId, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' AND (identifier = 1 OR (identifier = 4 AND status = 2)) LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$partId = $resultLotList['partId'];
				$identifier = $resultLotList['identifier'];
				
				if($identifier==1)
				{
					$sql = "SELECT processCode FROM cadcam_subconlist WHERE partId = ".$partId."";
					$querySubconList = $db->query($sql);
					$subconProcessCount = $querySubconList->num_rows;
				}
				else if($identifier==4)
				{
					$materialId = $cadcamTreatmentId = '';
					$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
					$querySubconMaterial = $db->query($sql);
					if($querySubconMaterial->num_rows > 0)
					{
						$resultSubconMaterial = $querySubconMaterial->fetch_array();
						$materialId = $resultSubconMaterial['materialId'];
						$cadcamTreatmentId = $resultSubconMaterial['treatmentId'];
					}
					
					$sql = "SELECT processCode FROM engineering_subcontreatment WHERE treatmentId = ".$cadcamTreatmentId."";
					$querySubconTreatment = $db->query($sql);
					$subconProcessCount = $querySubconTreatment->num_rows;
				}
				
				$sql = "SELECT processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 LIMIT 1";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
					$processRemarks = $resultWorkSchedule['processRemarks'];
				}
				$subconFlag = 1;
				
				$availability = 1;
			}
			
			if($_GET['country']=='2')	$availability = 1;
			
			if(strstr($processRemarks,$productId)===FALSE)
			{
				$processRemarks = ($processRemarks!='') ? $processRemarks.",".$productId : $productId;
			}
			//~ $processRemarks = '';
			$sql = "UPDATE ppic_workschedule SET processRemarks = '".$processRemarks."', availability = '".$availability."' WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE system_confirmedmaterialpo SET poNumber = 'nopo' WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$productCount = 0;
			$supplierAliasArray = $productNameArray = $productDescriptionArray = $priceArray = $currencySignArray = array();
			$sql = "SELECT productId, supplierId, supplierType, productName, productDescription FROM purchasing_supplierproducts WHERE productId IN(".$processRemarks.")";
			$querySupplierProducts = $db->query($sql);
			if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
			{
				$productCount = $querySupplierProducts->num_rows;
				while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
				{
					$prodId = $resultSupplierProducts['productId'];
					$supplierId = $resultSupplierProducts['supplierId'];
					$supplierType = $resultSupplierProducts['supplierType'];
					$productName = $resultSupplierProducts['productName'];
					$productDescription = $resultSupplierProducts['productDescription'];
					
					$currency = $price = $statusPrice = '';
					$sql = "SELECT currency, price, status FROM purchasing_price WHERE productId = ".$prodId." LIMIT 1"; //price;
					$queryPrice = $db->query($sql);
					if($queryPrice AND $queryPrice->num_rows > 0)
					{
						$resultPrice = $queryPrice->fetch_assoc();
						$currency = $resultPrice['currency'];
						$price = $resultPrice['price'];
						$statusPrice = $resultPrice['status'];
						
						if($statusPrice != 2)
						{
							$remarksPrice = '<span>Pending Price</span>';
						}
					}
					
					if($currency==1)		$currencySign = 'USD';
					else if($currency==2)	$currencySign = 'PHP';
					else if($currency==3)	$currencySign = 'YEN';
					
					$supplierAlias = '';
					if($supplierType==1)
					{
						$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
						$querySupplier = $db->query($sql);
						if($querySupplier AND $querySupplier->num_rows > 0)
						{
							$resultSupplier = $querySupplier->fetch_assoc();
							$supplierAlias = $resultSupplier['supplierAlias'];
						}
					}
					else if($supplierType==2)
					{
						$sql = "SELECT subconAlias FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
						$querySubcon = $db->query($sql);
						if($querySubcon AND $querySubcon->num_rows > 0)
						{
							$resultSubcon = $querySubcon->fetch_assoc();
							$supplierAlias = $resultSubcon['subconAlias'];
						}
					}
					
					if(!in_array($supplierAlias,$supplierAliasArray))	$supplierAliasArray[] = $supplierAlias;
					if(!in_array($productName,$productNameArray))	$productNameArray[] = $productName;
					if(!in_array($productDescription,$productDescriptionArray))	$productDescriptionArray[] = $productDescription;
					$priceArray[] = $price;
					$currencySignArray[] = $currencySign;
				}
			}
			
			$unLinkFlag = 0;
			if($subconFlag == 1)
			{
				if($subconProcessCount > 0 AND $subconProcessCount == $productCount)	$unLinkFlag = 1;
				//~ $unLinkFlag = 1;
			}
			else
			{
				$unLinkFlag = 1;
			}
			
			$data = array(
				'supplierAlias' 		=>	implode("<br>",$supplierAliasArray),
				'productName' 			=>	implode("<br>",$productNameArray),
				'productDescription' 	=>	implode("<br>",$productDescriptionArray),
				'price' 				=>	$priceArray,
				'currency' 				=>	$currencySignArray,
				'unLinkFlag' 			=>	$unLinkFlag,
				'sql' 	=>	$sqlMain
				);
			echo json_encode($data);
		}
		else if($_POST['ajaxType']=='unLinkedProduct')
		{
			$lotNumber = $_POST['lotNumber'];
			
			$availability = 0;
			$sql = "SELECT partId, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' AND (identifier = 1 OR (identifier = 4 AND status = 2)) LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$availability = 1;
			}
			
			echo $sql = "UPDATE ppic_workschedule SET processRemarks = '', availability = '".$availability."' WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		else if($_POST['ajaxType']=='removeItem')
		{
			$lotNumber = $_POST['lotNumber'];
			
			$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 AND status = 0 LIMIT 1";
			$queryWorkSched = $db->query($sql);
			if($queryWorkSched AND $queryWorkSched->num_rows > 0)
			{
				$resultWorkSched = $queryWorkSched->fetch_assoc();
				
				finishProcess("",$resultWorkSched['id'], 0, $_SESSION['idNumber'],'');
			}
		}
		else if($_POST['ajaxType']=='checkPONumber')
		{
			$sql = "SELECT poNumber FROM purchasing_podetails WHERE poNumber LIKE '".$_POST['poNumber']."' LIMIT 1";
			$queryCheckPo = $db->query($sql);
			
			$sql = "SELECT poNumber FROM purchasing_podetailsnew WHERE poNumber LIKE '".$_POST['poNumber']."' LIMIT 1";
			$queryCheckPoNew = $db->query($sql);
			if($queryCheckPo->num_rows > 0 OR $queryCheckPoNew->num_rows > 0)
			{
				echo displayText('L3811')." ".$_POST['poNumber']." already exists!";
				exit(0);
			}
			
			if($_GET['country']!=2)
			{
				//~ $pattern = '/^[0]{3}+[1-9]{1}+[0-9]{3}$/';
				$pattern = '/^[0]{2}+[1-9]{1}+[0-9]{4}$/';
				
				if(!preg_match($pattern, $_POST['poNumber']))
				{
					echo "Invalid PO Number!!";
					exit(0);
				}
			}
			
			exit(0);
		}
	}
	else if(isset($_POST['finalize']))
	{
		$poNumber = $_POST['poNumber'];
		$supplierId = $_POST['supplierId'];
		$supplierType = $_POST['supplierType'];
		$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
		$poCurrency = $_POST['poCurrency'];
		$shipmentType = $_POST['shipmentType'];
		$poTargetReceiveDate = $_POST['poTargetReceiveDate'];//Temporary
		$poRemarks = $_POST['poRemarks'];
		$poDiscount = $_POST['poDiscount'];
		
		$chargeDescriptionArray = (isset($_POST['chargeDescription'])) ? array_values(array_filter($_POST['chargeDescription'])) : array();
		$chargeQuantityArray = (isset($_POST['chargeQuantity'])) ? array_values(array_filter($_POST['chargeQuantity'])) : array();
		$chargeUnitArray = (isset($_POST['chargeUnit'])) ? array_values(array_filter($_POST['chargeUnit'])) : array();
		$chargeUnitPriceArray = (isset($_POST['chargeUnitPrice'])) ? array_values(array_filter($_POST['chargeUnitPrice'])) : array();
		
		$poIssueDate = date('Y-m-d');
		$prepared = $_SESSION['idNumber'];
		
		$sqlFilter = '';
		if($customerAlias!='')
		{
			if($customerAlias=='others')
			{
				$sqlFilter = " AND customerAlias NOT IN('B/E Phils.','JAMCO PHILS')";
			}
			else
			{
				$sqlFilter = " AND customerAlias LIKE '".$customerAlias."'";
			}
		}		
		
		$terms = $supplierAlias = '';
		if($supplierType==1)
		{
			$sql = "SELECT terms, supplierAlias FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
		}
		else if($supplierType==2)
		{
			$sql = "SELECT terms, subconAlias FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
		}
		if($sql!='')
		{
			$querySupplier = $db->query($sql);
			if($querySupplier AND $querySupplier->num_rows > 0)
			{
				$resultSupplier = $querySupplier->fetch_row();
				$terms = $resultSupplier[0];
				$supplierAlias = $resultSupplier[1];
			}
		}
		
		$sql = "INSERT INTO	`purchasing_podetailsnew`
						(	`poNumber`,			`supplierId`,				`supplierType`,		`supplierAlias`,	`poTerms`,
							`poShipmentType`,	`poTargetReceiveDate`,		`poIncharge`,		`poIssueDate`,		`poRemarks`,		
							`poStatus`,			`poCurrency`,				`poDiscount`,		`poInputDateTime`)
				VALUES	(	'".$poNumber."',	'".$supplierId."',			'".$supplierType."','".$supplierAlias."','".$terms."',
							'".$shipmentType."','".$poTargetReceiveDate."',	'".$prepared."',	'".$poIssueDate."',	'".$poRemarks."',
							0,					'".$poCurrency."',			'".$poDiscount."',	NOW())";
		$queryInsert = $db->query($sql);
		
		$poContentDetailsDataArray = array();
		
		//~ $sqlMain = "INSERT INTO `purchasing_pocontents`(`poNumber`, `productId`, `itemName`, `itemDescription`, `itemQuantity`, `itemUnit`, `itemContentQuantity`, `itemContentUnit`, `itemPrice`, `itemStatus`, `lotNumber`, `itemFlag`, `dataOne`, `dataTwo`, `dataThree`, `dataFour`, `dataFive`) VALUES";
		$sqlMain = "INSERT INTO `purchasing_pocontents`(`poNumber`, `productId`, `itemName`, `itemDescription`, `itemQuantity`, `itemUnit`, `itemContentQuantity`, `itemContentUnit`, `itemPrice`, `itemStatus`, `lotNumber`, `itemFlag`, `dataOne`, `dataTwo`, `dataThree`, `dataFour`, `dataFive`, `supplierAlias`, `issueDate`, `sendingDate`, `receivingDate`) VALUES";//2018-04-06
		$sqlValueArray = array();
		$counter = 0;
		
		$dateNeededArray = array();
		$lotNumberArray = array();
		$sql = "SELECT lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 1 ".$sqlFilter." ORDER BY targetFinish";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$targetFinish = $resultWorkSchedule['targetFinish'];
				$processRemarks = $resultWorkSchedule['processRemarks'];
				
				$poId = $partId = $workingQuantity = $identifier = $supplyType = '';
				$sql = "SELECT poId, partId, workingQuantity, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					$resultLotList = $queryLotList->fetch_assoc();
					$poId = $resultLotList['poId'];
					$partId = $resultLotList['partId'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$supplyType = $resultLotList['status'];
				}
				
				$workingQuantity = wholeNumber($workingQuantity);
				
				$dateNeeded = '';
				
				$dataOne = $dataTwo = $dataThree = $dataFour = $dataFive = '';
				$itemFlag = 0;
				if($identifier==1)
				{
					$partNumber = $revisionId = $materialSpecId = '';
					$sql = "SELECT partNumber, revisionId, materialSpecId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
					$queryParts = $db->query($sql);
					if($queryParts AND $queryParts->num_rows > 0)
					{
						$resultParts = $queryParts->fetch_assoc();
						$partNumber = $resultParts['partNumber'];
						$revisionId = $resultParts['revisionId'];
						$materialSpecId = $resultParts['materialSpecId'];
					}
					
					$metalType = '';
					$sql = "SELECT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
					$queryMaterialSpecs = $db->query($sql);
					if($queryMaterialSpecs->num_rows > 0)
					{
						$resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
						$materialTypeId = $resultMaterialSpecs['materialTypeId'];
						
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$queryMat1 = $db->query($sql);
						if($queryMat1 AND $queryMat1->num_rows > 0)
						{
							$resultMat1 = $queryMat1->fetch_assoc();
							$metalType = $resultMat1['materialType'];
						}							
					}						
					
					//cadcam_materialspecs; 03-08-2017
					//~ $metalType = '';
					//~ $sql = "SELECT metalType FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
					//~ $queryMaterialSpecs = $db->query($sql);
					//~ if($queryMaterialSpecs->num_rows > 0)
					//~ {
						//~ $resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
						//~ $metalType = $resultMaterialSpecs['metalType'];
					//~ }
					
					$dataOne = $partNumber;
					$dataTwo = $revisionId;
					
					if($revisionId!='')	$revisionId = "rev ".$revisionId;
				}
				else if($identifier==4)
				{
					if($supplyType==1 OR $supplyType==2)
					{
						$partNumber = $revisionId = '';
						
						$materialId = $cadcamTreatmentId = '';
						$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
						$querySubconMaterial = $db->query($sql);
						if($querySubconMaterial->num_rows > 0)
						{
							$resultSubconMaterial = $querySubconMaterial->fetch_array();
							$materialId = $resultSubconMaterial['materialId'];
							$cadcamTreatmentId = $resultSubconMaterial['treatmentId'];
						}
						
						$materialSpecId = $length = $width = '';
						$sql = "SELECT `materialSpecId`, `length`, `width` FROM `purchasing_material` WHERE `materialId` = ".$materialId." LIMIT 1";
						$queryMaterial = $db->query($sql);
						if($queryMaterial->num_rows > 0)
						{
							$resultMaterial = $queryMaterial->fetch_array();
							$materialSpecId = $resultMaterial['materialSpecId'];
							$length = $resultMaterial['length'];
							$width = $resultMaterial['width'];
						}
						
						$materialTypeId = $metalThickness = '';
						$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
						$queryMaterialSpecs = $db->query($sql);
						if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows)
						{
							$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
							$materialTypeId = $resultMaterialSpecs['materialTypeId'];
							$thickness = $resultMaterialSpecs['metalThickness'];
						}
						
						$materialType = '';
						$sql = "SELECT `materialType` FROM `engineering_materialtype` WHERE `materialTypeId` = ".$materialTypeId." LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_array();
							$materialType = $resultMaterialType['materialType'];
						}
						
						$cadcamTreatmentName = '';						
						$sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$cadcamTreatmentId." LIMIT 1";
						$queryTreatmentProcess = $db->query($sql);
						if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
						{
							$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
							$cadcamTreatmentName = $resultTreatmentProcess['treatmentName'];
						}
						
						$metalType = $materialType;
						
						$partNumber = $materialType." t".$thickness."X".$length."X".$width;
						
						$dataOne = $materialType;
						$dataTwo = $thickness;
						$dataThree = $length;
						$dataFour = $width;
						$dataFive = $cadcamTreatmentName;
						
						if($supplyType==1)
						{
							$sql = "UPDATE system_confirmedmaterialpo SET poNumber = '".$poNumber."' WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
							$queryUpdate = $db->query($sql);
							
							//~ $materialComputationId = '';
							//~ $sql = "SELECT materialComputationId FROM ppic_materialcomputationdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
							//~ $queryMaterialComputationDetails = $db->query($sql);
							//~ if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
							//~ {
								//~ $resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc();
								//~ $materialComputationId = $resultMaterialComputationDetails['materialComputationId'];
							//~ }
							
							//~ $sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
							$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
							$queryMaterialComputation = $db->query($sql);
							if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
							{
								$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
								$dateNeeded = $resultMaterialComputation['dateNeeded'];
								$dateNeededArray[] = $dateNeeded;
							}
						}
					}
					else if($supplyType==3)
					{
						$sql = "SELECT itemName, itemDescription FROM purchasing_items WHERE itemId = ".$partId." LIMIT 1";
						$queryItems = $db->query($sql);
						if($queryItems AND $queryItems->num_rows > 0)
						{
							$resultItems = $queryItems->fetch_assoc();
							$dataOne = $resultItems['itemName'];
							$dataTwo = $resultItems['itemDescription'];
						}
					}
					else if($supplyType==4)
					{
						$sql = "SELECT accessoryNumber, accessoryName, accessoryDescription, revisionId FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
						$queryAccessories = $db->query($sql);
						if($queryAccessories AND $queryAccessories->num_rows > 0)
						{
							$resultAccessories = $queryAccessories->fetch_assoc();
							$dataOne = $resultAccessories['accessoryNumber'];
							$dataTwo = $resultAccessories['accessoryName'];
							$dataThree = $resultAccessories['accessoryDescription'];
							$dataFour = $resultAccessories['revisionId'];
						}
					}
				}
				
				$pvc = '';
				if($identifier==4 AND $supplyType==1)
				{
					$sql = "SELECT pvc FROM system_confirmedmaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryConfirmedMaterialPo = $db->query($sql);
					if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
					{
						$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
						$pvc = ($resultConfirmedMaterialPo['pvc']==1) ? 'w/PVC' : '';
						
						if($resultConfirmedMaterialPo['pvc']==1)
						{
							$itemFlag = 1;
						}
					}
				}
				
				$productIds = $processRemarks;
				
				$productIdsCount = count(explode(",",$productIds));
				$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId IN(".$productIds.") AND itemStatus != 2";
				$queryPoContents = $db->query($sql);
				$poProductIdCount = ($queryPoContents AND $queryPoContents->num_rows) ? $queryPoContents->num_rows : 0;
				if($poProductIdCount >= $productIdsCount)
				{
					continue;
				}
				
				if($identifier==1 OR ($identifier==4 AND $supplyType==2))
				{
					$subconProcessCount = 0;
					if($identifier==1)
					{
						$sql = "SELECT a, processCode FROM cadcam_subconlist WHERE partId = ".$partId."";
						$querySubconList = $db->query($sql);
						if($querySubconList AND $querySubconList->num_rows > 0)
						{
							$subconProcessCount = $querySubconList->num_rows;
						}
					}
					else if($identifier==4)
					{
						$cadamTreatmentId = '';
						$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
						$querySubconMaterial = $db->query($sql);
						if($querySubconMaterial->num_rows > 0)
						{
							$resultSubconMaterial = $querySubconMaterial->fetch_array();
							$cadamTreatmentId = $resultSubconMaterial['treatmentId'];
						}
						
						$sql = "SELECT processCode FROM engineering_subcontreatment WHERE treatmentId = ".$cadamTreatmentId."";
						$querySubconTreatment = $db->query($sql);
						if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
						{
							$subconProcessCount = $querySubconTreatment->num_rows;
						}
					}
					
					if($subconProcessCount!=$productIdsCount)
					{
						continue;
					}
				}
				
				$packingCostFlag = 0;
				
				$sql = "SELECT `productId`, `productName`, `productDescription`, `productUnit`, `productContentQuantity`, `productContentUnit` FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType."";
				$querySupplierProducts = $db->query($sql);
				if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
				{
					while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
					{
						$productId = $resultSupplierProducts['productId'];
						$productName = $resultSupplierProducts['productName'];
						$productDescription = $resultSupplierProducts['productDescription'];
						$productUnit = $resultSupplierProducts['productUnit'];
						$productContentQuantity = $resultSupplierProducts['productContentQuantity'];
						$productContentUnit = $resultSupplierProducts['productContentUnit'];
						
						$surfaceArea = $packingCost = 0;
						if($supplierType==2)
						{
							$treatmentProcess = $productName;
							
							$sidesNumber = '';
							$sql = "SELECT supplyId, supplyType FROM purchasing_supplierproductlinking WHERE productId = ".$productId." LIMIT 1";
							$querySupplierProductLinking = $db->query($sql);
							if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
							{
								$resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc();
								$supplyId = $resultSupplierProductLinking['supplyId'];
								$supType = $resultSupplierProductLinking['supplyType'];
								
								if($identifier==1)
								{
									$subconOrder = 1;
									$sql = '';
									if($supType==2)
									{
										$sql = "SELECT processCode, surfaceArea, subconOrder FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$supplyId." LIMIT 1";
									}
									else if($supType==5)
									{
										$treatmentProcess = $productDescription;
										$sql = "SELECT processCode, surfaceArea, subconOrder FROM cadcam_subconlist WHERE a = ".$supplyId." LIMIT 1";
									}
									if($sql!='')
									{
										$querySubconList = $db->query($sql);
										if($querySubconList AND $querySubconList->num_rows > 0)
										{
											$resultSubconList = $querySubconList->fetch_assoc();
											$treatmentId = $resultSubconList['processCode'];
											$surfaceArea = $resultSubconList['surfaceArea'];
											$subconOrder = $resultSubconList['subconOrder'];
										}
									}
									
									$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId = ".$treatmentId." LIMIT 1";
									$queryTreatment = $db->query($sql);
									if($queryTreatment AND $queryTreatment->num_rows > 0)
									{
										$resultTreatment = $queryTreatment->fetch_assoc();
										$dataThree = $resultTreatment['treatmentName'];
									}
									
									if($supType==5)
									{
										$surfaceArea = 0;
									}
									
									if($subconOrder==2)
									{
										$deliveryToSubconProcess = 172;
										$receivingWarehouseProcess = 138;
									}
									else if($subconOrder==3)
									{
										$deliveryToSubconProcess = 228;
										$receivingWarehouseProcess = 229;
									}
									else
									{
										$deliveryToSubconProcess = 145;
										$receivingWarehouseProcess = 137;
									}
									
									//~ $packingCost = (in_array($supplyId,array(270,272))) ? ($totalSurfaceClear * 2) * 0.0031 : 0 ;
									$packingCost = ($supplyId==270) ? ($surfaceArea * 0.0031) : 0 ;
								}
								else if($identifier==4)
								{
									$deliveryToSubconProcess = 145;
									$receivingWarehouseProcess = 137;
									
									$surfaceArea = (($thickness*$length*2)+($thickness*$width*2))/10000;
									$surfaceArea = ($length*$width*2/10000)+$surfaceArea;
									
									$sidesNo = 2;
									$sql = "SELECT sidesNumber FROM engineering_subcontreatment WHERE treatmentId = ".$cadcamTreatmentId." AND processCode = ".$supplyId." LIMIT 1";
									$querySubconTreatment = $db->query($sql);
									if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
									{
										$resultSubconTreatment = $querySubconTreatment->fetch_assoc();
										$sidesNo = $resultSubconTreatment['sidesNumber'];
										if($supplyId==272)
										{
											$sidesNumber = " ".$sidesNo." Side(s)";
										}
									}
									
									if($supplyId == 273)
									{
										$surfaceArea = ($length*$width*2/10000);
									}
									
									if($sidesNo==1)	$surfaceArea = $surfaceArea/2;
									
									$packingCost = 0.61;
									if($packingCostFlag==1)	$packingCost = 0;
								}
								
								if($_GET['country']==2)
								{
									$sendingDate = '';
									$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 92 ORDER BY processOrder LIMIT 1";
									$queryWorkSched = $db->query($sql);
									if($queryWorkSched AND $queryWorkSched->num_rows > 0)
									{
										$resultWorkSched = $queryWorkSched->fetch_assoc();
										$targetFinish = $resultWorkSched['targetFinish'];
										
										$targetFinish = addDays(1,$targetFinish);
										//~ if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(5);
										
										$sendingDate = $targetFinish;
									}
									
									$receivingDate = '';
									$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processSection = 10 ORDER BY processOrder LIMIT 1";
									$queryWorkSched = $db->query($sql);
									if($queryWorkSched AND $queryWorkSched->num_rows > 0)
									{
										$resultWorkSched = $queryWorkSched->fetch_assoc();
										$targetFinish = $resultWorkSched['targetFinish'];
										
										//~ if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(2);
										
										$receivingDate = $targetFinish;
									}
								}
								else
								{
									$receivingDate = '';
									$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$receivingWarehouseProcess." LIMIT 1";
									$queryWorkSched = $db->query($sql);
									if($queryWorkSched AND $queryWorkSched->num_rows > 0)
									{
										$resultWorkSched = $queryWorkSched->fetch_assoc();
										$receivingDate = $resultWorkSched['targetFinish'];
										if(strtotime($receivingDate) < strtotime(date('Y-m-d')))	$receivingDate = addDays(5);
									}
									
									$sendingDate = '';
									$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$deliveryToSubconProcess." LIMIT 1";
									$queryWorkSched = $db->query($sql);
									if($queryWorkSched AND $queryWorkSched->num_rows > 0)
									{
										$resultWorkSched = $queryWorkSched->fetch_assoc();
										$sendingDate = $resultWorkSched['targetFinish'];
										if(strtotime($sendingDate) < strtotime(date('Y-m-d')))	$sendingDate = addDays(2);
									}
								}
								
								$poContentDetailsData['productId'] = $productId;
								$poContentDetailsData['lotNumber'] = $lotNumber;
								$poContentDetailsData['quantity'] = $workingQuantity;
								$poContentDetailsData['sendingDate'] = $sendingDate;
								$poContentDetailsData['receivingDate'] = $receivingDate;
								
								$poContentDetailsDataArray[] = $poContentDetailsData;
							}
							
							$specRev = '';
							$sql = "SELECT specificationRevision FROM engineering_specifications WHERE specificationNumber LIKE '".$treatmentProcess."' AND status = 0 ORDER BY specificationDate DESC";
							$querySpecificationRevision = $db->query($sql);
							if($querySpecificationRevision->num_rows > 0)
							{
								$resultSpecificationRevision = $querySpecificationRevision->fetch_array();
								$specRev = "rev.".$resultSpecificationRevision['specificationRevision'];
							}
							
							if($treatmentProcess=='PS1101' AND date('Y-m-d')=='2020-10-22')	$specRev = 'rev.H';//2020-10-22
							
							$productName = $treatmentProcess." ".$specRev.$sidesNumber;
							$productDescription = $partNumber." ".$revisionId;
						}
						else
						{
							if($identifier==4 AND $supplyType==1)
							{
								if($dateNeeded!='')
								{
									$poContentDetailsData['productId'] = $productId;
									$poContentDetailsData['lotNumber'] = $lotNumber;
									$poContentDetailsData['quantity'] = $workingQuantity;
									$poContentDetailsData['sendingDate'] = '0000-00-00';
									$poContentDetailsData['receivingDate'] = $dateNeeded;
									
									$poContentDetailsDataArray[] = $poContentDetailsData;
								}
							}
						}
						
						if($packingCost > 0 AND $packingCostFlag==0) $packingCostFlag = 1;
						
						$price = 0;
						$breakFlag = 0;
						$priceCount = 0;
						$sql = "SELECT priceLowerRange, priceUpperRange, price FROM purchasing_price WHERE productId = ".$productId." AND currency = ".$poCurrency."";
						$queryPrice = $db->query($sql);
						if($queryPrice AND $queryPrice->num_rows > 0)
						{
							while($resultPrice = $queryPrice->fetch_assoc())
							{
								$priceLowerRange = $resultPrice['priceLowerRange'];
								$priceUpperRange = $resultPrice['priceUpperRange'];
								$price = $resultPrice['price'];
								
								if($priceLowerRange != 0 AND $priceUpperRange != 0)
								{
									if($workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange)	$breakFlag = 1;
								}
								else
								{
									$breakFlag = 1;
								}
								
								if(++$priceCount == $queryPrice->num_rows)	$breakFlag = 1;
								
								if($breakFlag==1)
								{
									$unitPrice = $price;
									if($supplierType==2 OR $supplyType==2)
									{
										if($surfaceArea > 0)
										{
											$unitPrice = ($price * $surfaceArea)+$packingCost;
										}
									}
									
									if($supplyType==1)
									{
										if($productUnit==2 AND $productId!=4526 AND $supplierId!=937)
										{
											$materialId = $cadamTreatmentId = '';
											$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
											$querySubconMaterial = $db->query($sql);
											if($querySubconMaterial->num_rows > 0)
											{
												$resultSubconMaterial = $querySubconMaterial->fetch_array();
												$materialId = $resultSubconMaterial['materialId'];
												$cadamTreatmentId = $resultSubconMaterial['treatmentId'];
											}
											
											$materialSpecId = $length = $width = '';
											$sql = "SELECT `materialSpecId`, `length`, `width` FROM `purchasing_material` WHERE `materialId` = ".$materialId." LIMIT 1";
											$queryMaterial = $db->query($sql);
											if($queryMaterial->num_rows > 0)
											{
												$resultMaterial = $queryMaterial->fetch_array();
												$materialSpecId = $resultMaterial['materialSpecId'];
												$length = $resultMaterial['length'];
												$width = $resultMaterial['width'];
											}
											
											$materialTypeId = $metalThickness = '';
											$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
											$queryMaterialSpecs = $db->query($sql);
											if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows)
											{
												$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
												$materialTypeId = $resultMaterialSpecs['materialTypeId'];
												$thickness = $resultMaterialSpecs['metalThickness'];
											}
											
											$baseWeight = $coatingWeight = 0;
											$sql = "SELECT `baseWeight`, `coatingWeight` FROM `engineering_materialtype` WHERE `materialTypeId` = ".$materialTypeId." LIMIT 1";
											$queryMaterialType = $db->query($sql);
											if($queryMaterialType->num_rows > 0)
											{
												$resultMaterialType = $queryMaterialType->fetch_array();
												$baseWeight = $resultMaterialType['baseWeight'];
												$coatingWeight = $resultMaterialType['coatingWeight'];
											}
											
											$productDescription = $thickness." x ".$length." x ".$width;
											
											if($pvc=='w/PVC')
											{
												$unitPrice += ($supplierId==682) ? 0.10 : 0.15 ; //682 supplierId of Toyota Tsusho
											}
											
											$var1 = $var2 = $var3 = 1;
											//~ if($baseWeight!=0 AND $coatingWeight!=0)
											if($baseWeight!=0)
											{
												$var1 = (($baseWeight*$thickness)+$coatingWeight);
												$var2 = ($length/1000);
												$var3 = ($width/1000);
											}
											
											// -------------------------- MM Steel --------------------------------------
											if($supplierId==3)//Mm Steel
											{
												$var1 = round($var1,4);
												$var2 = round(($length * $width) / 1000000,4);
												$ans1 = ($var1*$var2);
												
												$ans1 = (string)$ans1;
												$decimalPlaces = 0;
												$i = 0;
												$first3Significant = '';
												$finalAns = '';
												while(strlen($first3Significant) < 4)
												{
													if(strstr($finalAns,'.')) $decimalPlaces++;
													if($ans1[$i] == '0' AND $i == 0)
													{
														$finalAns .= $ans1[$i];
													}
													else
													{
														if($ans1[$i]!='.')
														{
															$first3Significant .= $ans1[$i];
														}
														$finalAns .= $ans1[$i];
													}
													$i++;
													
													if($i > strlen($ans1))	break;
												}
												$ans1 = round($finalAns,($decimalPlaces - 1));
											}
											// ---------------------- End Of MM Steel -------------------------------
											else
											{
												if($length > 0 AND $width > 0)
												{
													$ans1 = ($var1*$var2*$var3);
												}
												else
												{
													$ans1 = 1;
												}
											}
											
											$unitPrice = ($ans1*$unitPrice);
										}
									}
									
									//~ $customerId = '';
									//~ $sql = "SELECT customerId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
									//~ $queryParts = $db->query($sql);
									//~ if($queryParts AND $queryParts->num_rows > 0)
									//~ {
										//~ $resultParts = $queryParts->fetch_assoc();
										//~ $customerId = $resultParts['customerId'];
									//~ }
									
									//~ if($customerId==45 AND $supplierId==11)
									//~ {
										//~ $unitPrice = $unitPrice - ($unitPrice*0.10);
									//~ }
									
									break;
								}
							}
						}
						
						if($identifier==4 AND $supplyType==1 AND $pvc!='') $productDescription .= " ".$pvc;
						
						$productDescription = addslashes($productDescription);
						$dataTwo = addslashes($dataTwo);
						
						if($identifier==4)
						{
							if($supplyType==1)
							{
								if($dateNeeded!='')
								{
									$receivingDate = $dateNeeded;
								}
							}
							else
							{
								$receivingDate = $poTargetReceiveDate;
							}
						}
						
						
						
						if($sendingDate=='')	$sendingDate = '0000-00-00';
						if($receivingDate=='')	$receivingDate = '0000-00-00';
						
						//~ $sqlValueArray[] = "('".$poNumber."',	'".$productId."','".$productName."','".$productDescription."','".$workingQuantity."','".$productUnit."','".$productContentQuantity."','".$productContentUnit."','".$unitPrice."','0','".$lotNumber."',0,'".$dataOne."','".$dataTwo."','".$dataThree."','".$dataFour."','".$dataFive."')";
						$sqlValueArray[] = "('".$poNumber."',	'".$productId."','".$db->real_escape_string($productName)."','".$db->real_escape_string($productDescription)."','".$workingQuantity."','".$productUnit."','".$productContentQuantity."','".$productContentUnit."','".$unitPrice."','0','".$lotNumber."','".$itemFlag."','".$db->real_escape_string($dataOne)."','".$db->real_escape_string($dataTwo)."','".$dataThree."','".$dataFour."','".$dataFive."', '".$supplierAlias."', '".$poIssueDate."', '".$sendingDate."', '".$receivingDate."')";//2018-04-06
						$counter++;
						
						if($counter == 50)
						{
							$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
							$queryInsert = $db->query($sqlInsert);
							$sqlValueArray = array();
							$counter = 0;
						}
					}
				}
			}
			if($counter > 0)
			{
				$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryInsert = $db->query($sqlInsert);
			}
		}
		
		$dateNeededArray = array_values(array_filter($dateNeededArray));
		
		if(count($dateNeededArray) == 1)
		{
			$sql = "UPDATE purchasing_podetailsnew SET poTargetReceiveDate = '".$dateNeededArray[0]."' WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		
		if(count($poContentDetailsDataArray) > 0)
		{
			$sqlMain = "INSERT INTO `purchasing_pocontentdetails` (`lotNumber`,`quantity`,`sendingDate`,`receivingDate`,`poContentId`) VALUES";
			$sqlValueArray = array();
			$counter = 0;
			
			foreach($poContentDetailsDataArray as $data)
			{
				
				$productId = $data['productId'];
				$lotNumber = $data['lotNumber'];
				$workingQuantity = $data['quantity'];
				$sendingDate = $data['sendingDate'];
				$receivingDate = $data['receivingDate'];
				
				$sql = "SELECT identifier = ";
				
				$poContentId = '';
				$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND productId = ".$productId." AND lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					$resultPoContents = $queryPoContents->fetch_assoc();
					$poContentId = $resultPoContents['poContentId'];
					
					$sql = "SELECT `lotNumber`,	`quantity`,	`sendingDate`, `receivingDate`,	`poContentId` FROM purchasing_pocontentdetails WHERE poContentId = ".$poContentId." LIMIT 1";
					$queryPoContentDetails = $db->query($sql);
					if($queryPoContentDetails AND $queryPoContentDetails->num_rows == 0)
					{
						$sqlValueArray[] = "('".$lotNumber."','".$workingQuantity."','".$sendingDate."','".$receivingDate."','".$poContentId."')";
						$counter++;
						
						if($counter == 50)
						{
							echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
							$queryInsert = $db->query($sqlInsert);
							$sqlValueArray = array();
							$counter = 0;
						}
					}
				}
			}
			if($counter > 0)
			{
				echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryInsert = $db->query($sqlInsert);
			}
		}
		
		if(count($chargeDescriptionArray) > 0)
		{
			$sqlMain = "INSERT INTO `purchasing_charges` (`poNumber`,`chargeDescription`,`chargeQuantity`,`chargeUnit`,`chargeUnitPrice`) VALUES";
			$sqlValueArray = array();
			$counter = 0;			
			
			foreach($chargeDescriptionArray as $key => $val)
			{
				$chargeQuantity = $chargeQuantityArray[$key];
				$chargeUnit = $chargeUnitArray[$key];
				$unitPrice = $chargeUnitPriceArray[$key];
				
				$sqlValueArray[] = "('".$poNumber."','".$val."','".$chargeQuantity."','".$chargeUnit."','".$unitPrice."')";
				$counter++;
				
				if($counter == 50)
				{
					echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
					$queryInsert = $db->query($sqlInsert);
					$sqlValueArray = array();
					$counter = 0;
				}
			}
			if($counter > 0)
			{
				echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryInsert = $db->query($sqlInsert);
			}
		}
		
		//~ if($_SESSION['idNumber']=='0346')
		//~ {
			//~ exit(0);
		//~ }
		
		//~ header('location:gerald_purchaseOrderConverter.php?saveFile=1&poNumber='.$poNumber);
		//~ header('location:gerald_purchaseOrderPrinting.php?poNumber='.$poNumber);
		
		if($_GET['country']=='2')
		{
			$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$prepared."', approvedBy = '".$prepared."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			//~ header('location:gerald_purchaseOrderStatus.php?poNumber='.$poNumber);
			?>
			<script>
				parent.location.href = 'gerald_purchaseOrderStatus.php?poNumber=<?php echo $poNumber;?>';
			</script>
			<?php
			exit(0);
		}
		
		// ------------------------------------------------------------ Checking Notification ------------------------------------------------------------ //
		$notificationDetail = 'You have new Purchase Order ('.$poNumber.') for checking';
		$notificationLink = '/V3/4-9 Purchase Order Making Software/gerald_purchaseOrderStatus.php';
		
		$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationDetail LIKE '".$notificationDetail."' AND notificationKey LIKE '".$poNumber."' AND notificationLink LIKE '".$notificationLink."' AND notificationType = 13 LIMIT 1";
		$queryNotificationIdDetails = $db->query($sql);
		if($queryNotificationIdDetails AND $queryNotificationIdDetails->num_rows == 0)
		{
			$sql = "INSERT INTO `system_notificationdetails`
							(	`notificationDetail`,		`notificationKey`,	`notificationLink`,			`notificationType`)
					VALUES	(	'".$notificationDetail."',	'".$poNumber."',	'".$notificationLink."',	'13')";
			$queryInsert = $db->query($sql);
			
			$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
			$query = $db->query($sql);
			$result = $query->fetch_array();
			$notificationId = $result['max'];
			
			$idNumberArray = array();
			
			$jamcoItemsFlag = 0;
			
			if($_GET['country']==2)
			{
				//~ $idNumberArray = array('0352','J014','J026');
				//~ $idNumberArray = array('J052');
				//~ $idNumberArray = array('0458');
				$idNumberArray = array('0458','0466','J014');
				
				$lotNumberArray = array();
				$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					while($resultPoContents = $queryPoContents->fetch_assoc())
					{
						$lotNumberArray[] = $resultPoContents['lotNumber'];
					}
				}
				
				$partIdArray = array();
				$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND identifier = 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$partIdArray[] = $resultLotList['partId'];
					}
					
					$sql = "SELECT partId FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).") AND customerId = 49 LIMIT 1";//Jamco Japan
					$queryParts = $db->query($sql);
					if($queryParts AND $queryParts->num_rows > 0)
					{
						$jamcoItemsFlag = 1;
						$idNumberArray = array('0458');
					}
				}
			}
			else
			{
				$sql = "SELECT idNumber FROM system_userpermission WHERE permissionId IN(105,205,305)";//Purchasing Head //305 is Temporary
				$queryUserPermission = $db->query($sql);
				if($queryUserPermission AND $queryUserPermission->num_rows > 0)
				{
					while($resultUserPermission = $queryUserPermission->fetch_assoc())
					{
						$idNumberArray[] = $resultUserPermission['idNumber'];
					}
				}
			}
			
			$sql = "SELECT idNumber FROM hr_employee WHERE idNumber IN('".implode("','",$idNumberArray)."') AND status = 1";
			$queryEmployee = $db->query($sql);
			if($queryEmployee AND $queryEmployee->num_rows > 0)
			{
				while($resultEmployee = $queryEmployee->fetch_assoc())
				{
					$idNumber = $resultEmployee['idNumber'];
					
					$sql = "INSERT INTO `system_notification`
									(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
							VALUES	(	'".$notificationId."',	'".$idNumber."',			'0',					'2')";
					$queryInsert = $db->query($sql);
				}
			}
			/* Deactivate 2019-07-06
			if($_GET['country']==2 AND $jamcoItemsFlag==0)
			{
				$attachFile = "PO".$poNumber.".pdf";
				
				$msg = "
					Dear Sir/Madam,<br><br>
					Good day.<br>
					<p>
						<a href='http://192.198.0.101".$notificationLink."?notificationId=".$notificationId."''>".$notificationDetail."</a>
					</p>
					<br>
					Regards<br>
					Tamura
					";
				
				$from = "tamura@arktech.co.jp";
				$fromName = "Arktech PMS Automatic Email";
				$subject = "Automated Email: Purchase Order ".$poNumber;
				$bodyText = $msg;
				$destinationAddressArray = array("takeda@arktech.co.jp");
				
				$account = 'tamura@arktech.co.jp';
				$password = 't0131810';
				
				$checkError = sendArktechMail($account,$password,$from,$fromName,$subject,$bodyText,$destinationAddressArray);
			}*/
			// ---------------------------------------------------------- END Checking Notification ---------------------------------------------------------- //
		}
		
		?>
		<script>
			parent.location.href = 'gerald_purchaseOrderMakingSummary.php';
		</script>
		<?php
		exit(0);
	}
	else if(isset($_GET['purchaseOrderStatus']))
	{
		$purchaseOrderStatus = $_GET['purchaseOrderStatus'];
		$notificationId = $_GET['notificationId'];
		$idNumber = $_SESSION['idNumber'];
		
		$sql = "SELECT notificationKey FROM system_notificationdetails WHERE notificationId = ".$notificationId." LIMIT 1";
		$queryNotificationIdDetails = $db->query($sql);
		if($queryNotificationIdDetails AND $queryNotificationIdDetails->num_rows > 0)
		{
			$resultNotificationIdDetails = $queryNotificationIdDetails->fetch_assoc();
			$poNumber = $resultNotificationIdDetails['notificationKey'];
			
			/* 2017-08-09
			//~ $field = ($purchaseOrderStatus=='CHECK') ? 'checkedBy' : 'approvedBy';
			$field = ($purchaseOrderStatus==1) ? 'checkedBy' : 'approvedBy';
			
			$sql = "UPDATE purchasing_podetailsnew SET ".$field." = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND ".$field." = '' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
			$queryUpdate = $db->query($sql);
			
			//~ if($purchaseOrderStatus=='CHECK')
			if($purchaseOrderStatus==1)
			{
				// ------------------------------------------------------------ Approval Notification ------------------------------------------------------------ //
				$notificationDetail = 'You have new Purchase Order ('.$poNumber.') for approval';
				$notificationLink = '/4-9 Purchase Order Making Software/gerald_purchaseOrderStatus.php';
				
				$sql = "INSERT INTO `system_notificationdetails`
								(	`notificationDetail`,		`notificationKey`,	`notificationLink`,			`notificationType`)
						VALUES	(	'".$notificationDetail."',	'".$poNumber."',	'".$notificationLink."',	'13')";
				$queryInsert = $db->query($sql);
				
				$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
				$query = $db->query($sql);
				$result = $query->fetch_array();
				$notificationId = $result['max'];
				
				$idNumberArray = array();
				$sql = "SELECT idNumber FROM system_userpermission WHERE permissionId IN(105,205)";//Purchasing Head (Temporary)
				//~ $sql = "SELECT idNumber FROM system_userpermission WHERE permissionId IN(135)";//Top Management
				$queryUserPermission = $db->query($sql);
				if($queryUserPermission AND $queryUserPermission->num_rows > 0)
				{
					while($resultUserPermission = $queryUserPermission->fetch_assoc())
					{
						$idNumberArray[] = $resultUserPermission['idNumber'];
					}
				}
				
				$sql = "SELECT idNumber FROM hr_employee WHERE idNumber IN('".implode("','",$idNumberArray)."') AND status = 1";
				$queryEmployee = $db->query($sql);
				if($queryEmployee AND $queryEmployee->num_rows > 0)
				{
					while($resultEmployee = $queryEmployee->fetch_assoc())
					{
						$idNumber = $resultEmployee['idNumber'];
						
						$sql = "INSERT INTO `system_notification`
										(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
								VALUES	(	'".$notificationId."',	'".$idNumber."',			'0',					'2')";
						$queryInsert = $db->query($sql);
					}
				}
				// ---------------------------------------------------------- END Approval Notification ---------------------------------------------------------- //		
			}
			*/
			
			if($_GET['country']==1)
			{
				// ---- Temporary ---- //
				//~ if($purchaseOrderStatus=='CHECK')
				if($purchaseOrderStatus==1)
				{
					//~ if($idNumber=='0449')	$idNumber = '0331';
					
					$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$idNumber."', approvedBy = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
					$queryUpdate = $db->query($sql);
					
					$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
					$queryUpdate = $db->query($sql);
				}
				// ---- Temporary ---- //
			}
			else if($_GET['country']==2)
			{
				$jamcoItemsFlag = 0;
				
				$lotNumberArray = array();
				$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					while($resultPoContents = $queryPoContents->fetch_assoc())
					{
						$lotNumberArray[] = $resultPoContents['lotNumber'];
					}
				}
				
				$partIdArray = array();
				$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND identifier = 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$partIdArray[] = $resultLotList['partId'];
					}
					
					$sql = "SELECT partId FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).") AND customerId = 49 LIMIT 1";//Jamco Japan
					$queryParts = $db->query($sql);
					if($queryParts AND $queryParts->num_rows > 0)
					{
						$jamcoItemsFlag = 1;
					}
				}
				
				if($jamcoItemsFlag==1)
				{
					if($purchaseOrderStatus==1)
					{
						//~ if($idNumber=='0449')	$idNumber = '0331';
						
						$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$idNumber."', approvedBy = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
						$queryUpdate = $db->query($sql);
					}
				}
				else
				{
					$checkedBy = $approvedBy = '';
					$sql = "SELECT checkedBy, approvedBy FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
					$queryPoDetailsNew = $db->query($sql);
					if($queryPoDetailsNew AND $queryPoDetailsNew->num_rows > 0)
					{
						$resultPoDetailsNew = $queryPoDetailsNew->fetch_assoc();
						$checkedBy = $resultPoDetailsNew['checkedBy'];
						$approvedBy = $resultPoDetailsNew['approvedBy'];
					}
					
					if($checkedBy=='' AND $approvedBy=='')
					{
						$approveFlag = 0;
						$productIdArray = array();
						$sql = "SELECT productId, itemPrice FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
						$queryPoContents = $db->query($sql);
						if($queryPoContents AND $queryPoContents->num_rows > 0)
						{
							while($resultPoContents = $queryPoContents->fetch_assoc())
							{
								$productIdArray[] = $productId = $resultPoContents['productId'];
								$itemPrice = $resultPoContents['itemPrice'];
								
								$sql = "SELECT itemPrice FROM purchasing_pocontents WHERE poNumber NOT LIKE '".$poNumber."' AND productId = ".$productId." AND itemStatus != 2 ORDER BY poContentId DESC LIMIT 1";
								$queryCheckLastPO = $db->query($sql);
								if($queryCheckLastPO AND $queryCheckLastPO->num_rows > 0)
								{
									$resultCheckLastPO = $queryCheckLastPO->fetch_assoc();
									if($itemPrice > $resultCheckLastPO['itemPrice'])
									{
										$approveFlag = 1;
									}
								}
							}
						}
						
						if($approveFlag==0)
						{
							$lastPoNumberArray = array();
							$sql = "SELECT poNumber FROM purchasing_pocontents WHERE poNumber NOT LIKE '".$poNumber."' AND productId IN(".implode(",",$productIdArray).") AND itemStatus != 2 LIMIT 1";
							$queryPoContents = $db->query($sql);
							if($queryPoContents AND $queryPoContents->num_rows == 0)
							{
								$approveFlag = 1;
							}
						}
						
						if($approveFlag==1)
						{
							if($purchaseOrderStatus==1)
							{
								//~ if($idNumber=='0449')	$idNumber = '0331';
								
								$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
								$queryUpdate = $db->query($sql);
								
								$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
								$queryUpdate = $db->query($sql);
								
								// ------------------------------------------------------------ Checking Notification ------------------------------------------------------------ //
								$notificationDetail = 'You have new Purchase Order ('.$poNumber.') for checking';
								$notificationLink = '/V3/4-9 Purchase Order Making Software/gerald_purchaseOrderStatus.php';
								
								$sql = "INSERT INTO `system_notificationdetails`
												(	`notificationDetail`,		`notificationKey`,	`notificationLink`,			`notificationType`)
										VALUES	(	'".$notificationDetail."',	'".$poNumber."',	'".$notificationLink."',	'13')";
								$queryInsert = $db->query($sql);
								
								$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
								$query = $db->query($sql);
								$result = $query->fetch_array();
								$notificationId = $result['max'];
								
								$idNumberArray = array();
								
								if($_GET['country']==2)
								{
									//~ $idNumberArray = array('0352','J014','J026');
									//~ $idNumberArray = array('J052');
									$idNumberArray = array('0458');
								}
								else
								{
									$sql = "SELECT idNumber FROM system_userpermission WHERE permissionId IN(105,205,305)";//Purchasing Head //305 is Temporary
									$queryUserPermission = $db->query($sql);
									if($queryUserPermission AND $queryUserPermission->num_rows > 0)
									{
										while($resultUserPermission = $queryUserPermission->fetch_assoc())
										{
											$idNumberArray[] = $resultUserPermission['idNumber'];
										}
									}
								}
								
								$sql = "SELECT idNumber FROM hr_employee WHERE idNumber IN('".implode("','",$idNumberArray)."') AND status = 1";
								$queryEmployee = $db->query($sql);
								if($queryEmployee AND $queryEmployee->num_rows > 0)
								{
									while($resultEmployee = $queryEmployee->fetch_assoc())
									{
										$idNumber = $resultEmployee['idNumber'];
										
										$sql = "INSERT INTO `system_notification`
														(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
												VALUES	(	'".$notificationId."',	'".$idNumber."',			'0',					'2')";
										$queryInsert = $db->query($sql);
									}
								}
								
								if($_GET['country']==2)
								{
									$attachFile = "PO".$poNumber.".pdf";
									
									$msg = "
										Dear Sir/Madam,<br><br>
										Good day.<br>
										<p>
											<a href='http://192.198.0.101".$notificationLink."?notificationId=".$notificationId."''>".$notificationDetail."</a>
										</p>
										<br>
										Regards<br>
										Tamura
										";
									
									$from = "tamura@arktech.co.jp";
									$fromName = "Arktech PMS Automatic Email";
									$subject = "Automated Email: Purchase Order ".$poNumber;
									$bodyText = $msg;
									//~ $destinationAddressArray = array("y-arakawa@arktech.co.jp");
									$destinationAddressArray = array("isabel@arktech.co.jp");
									
									$account = 'tamura@arktech.co.jp';
									$password = 't0131810';
									
									$checkError = sendArktechMail($account,$password,$from,$fromName,$subject,$bodyText,$destinationAddressArray);
								}
								// ---------------------------------------------------------- END Checking Notification ---------------------------------------------------------- //
							}
						}
						else
						{
							if($purchaseOrderStatus==1)
							{
								//~ if($idNumber=='0449')	$idNumber = '0331';
								
								$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$idNumber."', approvedBy = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
								$queryUpdate = $db->query($sql);
								
								$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
								$queryUpdate = $db->query($sql);
							}
						}
					}
					else if($checkedBy!='' AND $approvedBy=='')
					{
						if($purchaseOrderStatus==1)
						{
							//~ if($idNumber=='0449')	$idNumber = '0331';
							
							$sql = "UPDATE purchasing_podetailsnew SET approvedBy = '".$idNumber."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy != '' AND approvedBy = '' LIMIT 1";
							$queryUpdate = $db->query($sql);
							
							$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
							$queryUpdate = $db->query($sql);
						}
					}
				}
			}
		}
		
		header('location:/V3/dashboard.php');
	}
	else if(isset($_GET['finish']) AND $_GET['finish']==1)
	{
		$poNumber = $_GET['poNumber'];
		
		/*	UPDATE ppic_workschedule SET status = 1			Finished Purchase Order Making Process
		 * 	UPDATE ppic_lotlist SET poContentId				Link to lotlist	
		 * 	INSERT INTO `ppic_workschedule`					Add Receiving, Inspection and Storage Process (IF not subcon po)
		 * 	INSERT or UPDATE warehouse_temporaryinventory	Create Temporary (Material and Accessory)
		 * 	UPDATE purchasing_podetailsnew SET poStatus		Finished the PO
		 */
		
		// Get PO Data
		$supplierId = $supplierType = $poIncharge = $issueDate = $poTargetReceiveDate = '';
		$sql = "SELECT supplierId, supplierType, poIncharge, poIssueDate, poTargetReceiveDate, poCurrency FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' AND poStatus = 0 LIMIT 1";
		$queryPoDetailsNew = $db->query($sql);
		if($queryPoDetailsNew AND $queryPoDetailsNew->num_rows > 0)
		{
			$resultPoDetailsNew = $queryPoDetailsNew->fetch_assoc();
			$supplierId = $resultPoDetailsNew['supplierId'];
			$supplierType = $resultPoDetailsNew['supplierType'];
			$poIncharge = $resultPoDetailsNew['poIncharge'];
			$issueDate = $resultPoDetailsNew['poIssueDate'];
			$poTargetReceiveDate = $resultPoDetailsNew['poTargetReceiveDate'];
			$poCurrency = $resultPoDetailsNew['poCurrency'];
			
			$supplierAlias = $supplierName = $taxStatus = '';
			if($supplierType==1)
			{
				$sql = "SELECT supplierAlias, supplierName, taxStatus FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
			}
			else if($supplierType==2)
			{
				$sql = "SELECT subconAlias, subconName, taxStatus FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
			}
			if($sql!='')
			{
				$querySupplier = $db->query($sql);
				if($querySupplier AND $querySupplier->num_rows > 0)
				{
					$resultSupplier = $querySupplier->fetch_row();
					$supplierAlias = $resultSupplier[0];
					$supplierName = $resultSupplier[1];
					$taxStatus = $resultSupplier[2];
				}
			}
			
			//~ $sql = "INSERT INTO `purchasing_openpocontent`
							//~ (	`poContentId`, `poNumber`, `supplierAlias`,		 `lotNumber`, `itemName`, `itemDescription`, `itemQuantity`, `issueDate`,		`poInputDateTime`)
					//~ SELECT 		`poContentId`, `poNumber`, '".$supplierAlias."', `lotNumber`, `itemName`, `itemDescription`, `itemQuantity`, '".$issueDate."',	NOW()
					//~ FROM		`purchasing_pocontents`
					//~ WHERE	poNumber LIKE '".$poNumber."'";
			//~ $queryInsert = $db->query($sql);
			
			// Get PO Items
			$sql = "SELECT DISTINCT lotNumber FROM purchasing_openpocontent WHERE poNumber LIKE '".$poNumber."'";
			$queryOpenPoContent = $db->query($sql);
			if($queryOpenPoContent AND $queryOpenPoContent->num_rows > 0)
			{
				while($resultOpenPoContent = $queryOpenPoContent->fetch_assoc())
				{
					$lotNumber = $resultOpenPoContent['lotNumber'];
					
					$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND lotNumber LIKE '".$lotNumber."'";
					$queryPoContents = $db->query($sql);
					if($queryPoContents AND $queryPoContents->num_rows > 0)
					{
						while($resultPoContents = $queryPoContents->fetch_assoc())
						{
							$poContentId = $resultPoContents['poContentId'];
							
							$sendingDate = $receivingDate = '';
							$sql = "SELECT sendingDate, receivingDate FROM purchasing_pocontentdetails WHERE poContentId = ".$poContentId." ORDER BY receivingDate DESC LIMIT 1";
							$queryPoContentDetails = $db->query($sql);
							if($queryPoContentDetails AND $queryPoContentDetails->num_rows > 0)
							{
								$resultPoContentDetails = $queryPoContentDetails->fetch_assoc();
								$sendingDate = $resultPoContentDetails['sendingDate'];
								$receivingDate = $resultPoContentDetails['receivingDate'];
							}
							
							
							$sql = "UPDATE	purchasing_openpocontent
									SET		sendingDate = '".$sendingDate."',
											receivingDate = '".$receivingDate."'
									WHERE 	poContentId = ".$poContentId." LIMIT 1";
							$queryUpdate = $db->query($sql);
						}
					}
					
					// Items with Purchase Order Making Process
					$sql = "SELECT id, processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND status = 0 AND processRemarks != '' LIMIT 1";
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
						$id = $resultWorkSchedule['id'];
						$productIds = $resultWorkSchedule['processRemarks'];
						
						$productIdsCount = count(explode(",",$productIds));
						
						$poContentIdArray = array();
						$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId IN(".$productIds.") AND itemStatus != 2";
						$queryPoContents = $db->query($sql);
						if($queryPoContents AND $queryPoContents->num_rows)
						{
							$poProductIdCount = $queryPoContents->num_rows;
							while($resultPoContents = $queryPoContents->fetch_assoc())
							{
								$poContentIdArray[] = $resultPoContents['poContentId'];
							}
							
							$poContentIds = count($poContentIdArray > 0) ? implode(",",$poContentIdArray) : '';
							
							if($poProductIdCount >= $productIdsCount)
							{
								finishProcess("",$id, 0, $poIncharge,'');
								
								updateReceivingProcessRemarks($lotNumber);
								
								$identifier = $supplyType = '';
								$sql = "SELECT identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									$resultLotList = $queryLotList->fetch_assoc();
									$identifier = $resultLotList['identifier'];
									$supplyType = $resultLotList['status'];
								}
								
								if($identifier==1 OR ($identifier==4 AND $supplyType==2))
								{
									$sql = "UPDATE ppic_lotlist SET poContentId = '".$poContentIds."' WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
									$queryUpdate = $db->query($sql);
									updateWorkschedulePoContentId($lotNumber);
								}
								else if($identifier==4)
								{
									$sql = "UPDATE ppic_lotlist SET poId = ".$poContentIds." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
									$queryUpdate = $db->query($sql);
									
									//Incoming Inspection Default
									$inspectionProcess = 163;
									if($supplyType == 1 OR $supplyType == 2)
									{
										$inspectionProcess = 352; //2018-10-19 Nabel Receiving Inspection if Material
									}
									
									if(($supplyType == 1 OR $supplyType == 4) AND $_GET['country']==1)
									{
										//Change Section of Incoming Inspection from 32(Receiving Inspection) to 4(QC Inspection)
										$sql = "INSERT INTO `ppic_workschedule`
														(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
												VALUES	(	'".$lotNumber."',		555,						2,				'".$poTargetReceiveDate."', '',				0,			'',					34,					1),
														(	'".$lotNumber."',		137,						3,				'".$poTargetReceiveDate."',	'',				0,			'',					36,					0),
														(	'".$lotNumber."',		".$inspectionProcess.",		4,				'".$poTargetReceiveDate."', '',				0,			'',					4,					0),
														(	'".$lotNumber."',		353,						5,				'".$poTargetReceiveDate."', '',				0,			'',					31,					0)
											";
										$queryInsert = $db->query($sql);
									}
									else
									{
										//Change Section of Incoming Inspection from 32(Receiving Inspection) to 4(QC Inspection)
										$sql = "INSERT INTO `ppic_workschedule`
														(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
												VALUES	(	'".$lotNumber."',		137,						2,				'".$poTargetReceiveDate."',	'',				0,			'',					36,					1),
														(	'".$lotNumber."',		".$inspectionProcess.",		3,				'".$poTargetReceiveDate."', '',				0,			'',					4,					0),
														(	'".$lotNumber."',		353,						4,				'".$poTargetReceiveDate."', '',				0,			'',					31,					0)
											";
										$queryInsert = $db->query($sql);
									}
									if($queryInsert)
									{
										$partName = $partNumber = $customerAlias = $dataOne = $dataTwo = $dataThree = $dataFour = $dataFive = '';
										$sql = "SELECT supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive FROM purchasing_pocontents WHERE poContentId IN(".$poContentIds.") LIMIT 1";
										$queryOpenPoList = $db->query($sql);
										if($queryOpenPoList AND $queryOpenPoList->num_rows > 0)
										{
											$resultOpenPoList = $queryOpenPoList->fetch_assoc();
											$customerAlias = $resultOpenPoList['supplierAlias'];
											$dataOne = $resultOpenPoList['dataOne'];
											$dataTwo = $resultOpenPoList['dataTwo'];
											$dataThree = $resultOpenPoList['dataThree'];
											$dataFour = $resultOpenPoList['dataFour'];
											$dataFive = $resultOpenPoList['dataFive'];
										}
										
										if($supplyType == 1 OR $supplyType == 2)
										{
											$partNumber = $dataOne;
											$partName = "t".$dataTwo." ".$dataThree." X ".$dataFour;
											
											$dataSeven = $dataOne." t".$dataTwo." ".$dataFive;
											$decimalOne = $dataTwo;
											$dataTwo = $dataFive;
										}
										else if($supplyType == 3 OR $supplyType == 4)
										{
											$partNumber = $dataOne;
											$partName = $dataTwo;
										}
										
										$sql = "
											INSERT INTO view_workschedule
													(	`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`,	`customerAlias`,		`poNumber`,			`partNumber`,										`partName`,										`dataOne`,		`dataTwo`,		`dataSeven`,		`decimalOne`)
											SELECT 		`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`, '".$customerAlias."',	'".$poNumber."',	'".mysqli_real_escape_string($db,$partNumber)."',	'".mysqli_real_escape_string($db,$partName)."',	'".$dataOne."', '".$dataTwo."', '".$dataSeven."',	'".$decimalOne."'
											FROM	ppic_workschedule
											WHERE 	lotNumber LIKE '".$lotNumber."' AND status = 0 ORDER BY processOrder
											";
										$queryInsert = $db->query($sql);
									}
									
									if($supplyType==1 OR $supplyType==4)
									{
										$leftQuantity = -1;
										
										$sql = "SELECT dataOne, dataTwo, dataThree, dataFour, dataFive, itemQuantity, itemFlag INTO @dataOne, @dataTwo, @dataThree, @dataFour, @dataFive, @itemQuantity, @itemFlag FROM `purchasing_pocontents` WHERE `poContentId` = ".$poContentId." LIMIT 1";
										$queryPoContents = $db->query($sql);
										
										//~ $sql = "SELECT listId, poContentId, linkedBalQty, @itemQuantity as itemQuantity FROM `warehouse_temporarymaterial` WHERE inventoryId = '' AND supplierAlias LIKE '".$supplierAlias."' AND materialType = @dataOne AND thickness = @dataTwo AND length = @dataThree AND width = @dataFour AND treatment = @dataFive AND linkedBalQty <= @itemQuantity AND linkedBalQty > 0 AND inputType = 0 ORDER BY linkedBalQty DESC";
										$sql = "SELECT listId, poContentId, linkedBalQty, @itemQuantity as itemQuantity FROM `warehouse_temporaryinventory` WHERE inventoryId = '' AND supplierAlias LIKE '".$supplierAlias."' AND dataOne = @dataOne AND dataTwo = @dataTwo AND dataThree = @dataThree AND dataFour = @dataFour AND dataFive = @dataFive AND itemFlag = @itemFlag AND linkedBalQty <= @itemQuantity AND linkedBalQty > 0 AND inputType = 0 ORDER BY linkedBalQty DESC";
										$queryTemporaryMaterial = $db->query($sql);
										if($queryTemporaryMaterial AND $queryTemporaryMaterial->num_rows > 0)
										{
											while($resultTemporaryMaterial = $queryTemporaryMaterial->fetch_assoc())
											{
												$listId = $resultTemporaryMaterial['listId'];
												$tempPoContentId = ($resultTemporaryMaterial['poContentId']!='') ? $resultTemporaryMaterial['poContentId'].",".$poContentId : $poContentId;
												$linkedBalQty = $resultTemporaryMaterial['linkedBalQty'];
												$itemQuantity = $resultTemporaryMaterial['itemQuantity'];
												
												if($leftQuantity==-1)	$leftQuantity = $itemQuantity;
												
												if($leftQuantity >= $linkedBalQty)
												{
													//~ $sql = "UPDATE warehouse_temporarymaterial SET poContentId = '".$tempPoContentId."', linkedBalQty = 0 WHERE listId = ".$listId." LIMIT 1";
													$sql = "UPDATE warehouse_temporaryinventory SET poContentId = '".$tempPoContentId."', linkedBalQty = 0 WHERE listId = ".$listId." LIMIT 1";
													$queryUpdate = $db->query($sql);
													
													$leftQuantity -= $linkedBalQty;
												}
												else
												{
													//~ $sql = "UPDATE warehouse_temporarymaterial SET poContentId = '".$tempPoContentId."', linkedBalQty = (linkedBalQty - ".$leftQuantity.")  WHERE listId = ".$listId." LIMIT 1";
													$sql = "UPDATE warehouse_temporaryinventory SET poContentId = '".$tempPoContentId."', linkedBalQty = (linkedBalQty - ".$leftQuantity.")  WHERE listId = ".$listId." LIMIT 1";
													$queryUpdate = $db->query($sql);
													
													$leftQuantity -= $leftQuantity;
												}
												
												if($leftQuantity < 0)
												{
													break;
												}
											}
											if($leftQuantity > 0)
											{
												//~ $sql = "
													//~ INSERT INTO `warehouse_temporarymaterial`
															//~ (	`supplierAlias`,		`stockDate`,	`stockTime`,	`materialType`, `thickness`,	`length`,	`width`,	`treatment`,	`quantity`,				`idNumber`,						`poContentId`,		`linkedBalQty`,		`tempBalQty`,			`inputType`)
													//~ SELECT		'".$supplierAlias."',	NOW(),			NOW(),			dataOne,		dataTwo,		dataThree,	dataFour,	dataFive,		'".$leftQuantity."',	'".$_SESSION['idNumber']."',	'".$poContentId."',	'0',				'".$leftQuantity."',	1
													//~ FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1
												//~ ";
												$sql = "
													INSERT INTO `warehouse_temporaryinventory`
															(	`supplierAlias`,		`stockDate`,	`stockTime`,	`type`, 			`dataOne`, `dataTwo`,	`dataThree`,	`dataFour`,	`dataFive`,	`quantity`,				`pvcStatus`,	`idNumber`,					`poContentId`,		`linkedBalQty`,		`tempBalQty`,			`inputType`)
													SELECT		'".$supplierAlias."',	NOW(),			NOW(),			'".$supplyType."',	dataOne,	dataTwo,	dataThree,		dataFour,	dataFive,	'".$leftQuantity."',	itemFlag',		".$_SESSION['idNumber']."',	'".$poContentId."',	'0',				'".$leftQuantity."',	1
													FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1
												";
												$queryInsert = $db->query($sql);
											}
										}
										else
										{
											//~ $sql = "SELECT listId, @itemQuantity as itemQuantity FROM `warehouse_temporarymaterial` WHERE inventoryId = '' AND supplierAlias LIKE '".$supplierAlias."' AND materialType = @dataOne AND thickness = @dataTwo AND length = @dataThree AND width = @dataFour AND treatment = @dataFive AND linkedBalQty > @itemQuantity AND linkedBalQty > 0 AND inputType = 0 ORDER BY linkedBalQty LIMIT 1";
											$sql = "SELECT listId, @itemQuantity as itemQuantity FROM `warehouse_temporaryinventory` WHERE inventoryId = '' AND supplierAlias LIKE '".$supplierAlias."' AND dataOne = @dataOne AND dataTwo = @dataTwo AND dataThree = @dataThree AND dataFour = @dataFour AND dataFive = @dataFive AND linkedBalQty > @itemQuantity AND linkedBalQty > 0 AND inputType = 0 ORDER BY linkedBalQty LIMIT 1";
											$queryTemporaryMaterial = $db->query($sql);
											if($queryTemporaryMaterial AND $queryTemporaryMaterial->num_rows > 0)
											{
												$resultTemporaryMaterial = $queryTemporaryMaterial->fetch_assoc();
												$listId = $resultTemporaryMaterial['listId'];
												$itemQuantity = $resultTemporaryMaterial['itemQuantity'];
												
												//~ $sql = "UPDATE warehouse_temporarymaterial SET poContentId = ".$poContentId.", linkedBalQty = ".$itemQuantity." WHERE listId = ".$listId." LIMIT 1";
												$sql = "UPDATE warehouse_temporaryinventory SET poContentId = ".$poContentId.", linkedBalQty = ".$itemQuantity." WHERE listId = ".$listId." LIMIT 1";
												$queryUpdate = $db->query($sql);
											}
											else
											{
												//~ $sql = "
													//~ INSERT INTO `warehouse_temporarymaterial`
															//~ (	`supplierAlias`,		`stockDate`,	`stockTime`,	`materialType`, `thickness`,	`length`,	`width`,	`treatment`,	`quantity`,		`idNumber`,						`poContentId`,		`linkedBalQty`,	`tempBalQty`,	`inputType`)
													//~ SELECT		'".$supplierAlias."',	NOW(),			NOW(),			dataOne,		dataTwo,		dataThree,	dataFour,	dataFive,		itemQuantity,	'".$_SESSION['idNumber']."',	'".$poContentId."',	0,				itemQuantity,	1
													//~ FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1
												//~ ";
												$sql = "
													INSERT INTO `warehouse_temporaryinventory`
															(	`supplierAlias`,		`stockDate`,	`stockTime`,	`type`, 			`dataOne`, `dataTwo`,	`dataThree`,	`dataFour`,	`dataFive`,	`quantity`,		`pvcStatus`,	`idNumber`,						`poContentId`,		`linkedBalQty`,	`tempBalQty`,	`inputType`)
													SELECT		'".$supplierAlias."',	NOW(),			NOW(),			'".$supplyType."',	dataOne,	dataTwo,	dataThree,		dataFour,	dataFive,	itemQuantity,	itemFlag,		'".$_SESSION['idNumber']."',	'".$poContentId."',	0,				itemQuantity,	1
													FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1
												";
												$queryInsert = $db->query($sql);
											}
										}
										
										$sql = "SELECT listId FROM warehouse_temporaryinventory WHERE poContentId = ".$poContentId." ";
										$queryTemporaryInventory = $db->query($sql);
										if($queryTemporaryInventory AND $queryTemporaryInventory->num_rows > 0)
										{
											$resultTemporaryInventory = $queryTemporaryInventory->fetch_assoc();
											$listId = $resultTemporaryInventory['listId'];
											
											$sql = "SELECT inventoryId FROM warehouse_inventory WHERE inventoryId LIKE 'TMP".$listId."' AND type = 1 AND dataSix = 3 LIMIT 1";
											$queryInventory = $db->query($sql);
											if($queryInventory AND $queryInventory->num_rows > 0)
											{
												$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
												$queryMaterialComputation = $db->query($sql);
												if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
												{
													$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
													$materialComputationId = $resultMaterialComputation['materialComputationId'];
													
													$sql = "INSERT INTO engineering_booking
																	(	inventoryId,		bookingQuantity,			bookingDate,	bookingTime,	bookingStatus,	nestingType,		temporaryBookingFlag,	cuttingDate)
															VALUES	(	'".$inventoryId."', '".ceil($requirement)."',	now(), 			now(), 			2,				'".$nestingType."', 1,						'".$deliveryDate."')";
													//~ $insertQuery = $db->query($sql);	
													
													$bookingId = $db->insert_id;
													
													$sql = "
														INSERT INTO engineering_bookingdetails
																(	bookingId, 				lotNumber,	quantity,			materialRequirement)
														SELECT		'".$tmpBookingId."',	lotNumber,	workingQuantity,	requirement
														FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."
													";
													//~ $queryInsert = $db->query($sql);
													
													//~ $sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
													
												}
											}
										}
										
									}
								}
							}
						}
					}
				}
			}
			
			$sql = "
				INSERT INTO `accounting_payablesnew`
						(	`poContentIds`, 											 	`supplierName`, 	`poNumber`, `lotNumber`, `currency`,		`itemQuantity`, `unitPrice`, `quantity`,	 `taxStatus`)
				SELECT		GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ','),	'".$supplierName."',`poNumber`, `lotNumber`, '".$poCurrency."', `itemQuantity`, SUM(itemPrice), `itemQuantity`, '".$taxStatus."'
				FROM 	`purchasing_pocontents` WHERE `poNumber` LIKE '".$poNumber."' AND itemStatus != 2 GROUP BY lotNumber
				";
			$queryInsert = $db->query($sql);
			if($queryInsert)
			{
				$payableIdArray = array();
				$sql = "SELECT payableId, lotNumber FROM accounting_payablesnew WHERE poNumber LIKE '".$poNumber."'";
				$queryPayables = $db->query($sql);
				if($queryPayables AND $queryPayables->num_rows > 0)
				{
					while($resultPayables = $queryPayables->fetch_assoc())
					{
						$payableId = $resultPayables['payableId'];
						$lotNumber = $resultPayables['lotNumber'];
						
						$identifier = $status = '';
						$sql = "SELECT identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$identifier = $resultLotList['identifier'];
							$status = $resultLotList['status'];
						}
						
						if($identifier==1)
						{
							$type = 2;
						}
						else if($identifier==4)
						{
							$type = $status;
						}
						
						if(!isset($payableIdArray[$type])) $payableIdArray[$type] = array();
						$payableIdArray[$type][] = $payableId;
					}
					
					if(count($payableIdArray) > 0)
					{
						foreach($payableIdArray as $key => $arrayValue)
						{
							$sql = "UPDATE accounting_payablesnew SET type = ".$key." WHERE payableId IN(".implode(",",$arrayValue).")";
							$queryUpdate = $db->query($sql);
						}
					}
				}
			}
			
			insertPOMonitoring($poNumber);
			
			//~ $sql = "UPDATE purchasing_podetailsnew SET poStatus = 1 WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
			$sql = "UPDATE purchasing_podetailsnew SET poStatus = 4 WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			header('location:gerald_purchaseOrderMakingSummary.php');
		}
	}
	else if(isset($_GET['editPO']) AND $_GET['editPO']==1)
	{
		$poNumber = $_GET['poNumber'];
		$shipmentType = $_POST['shipmentType'];
		$poRemarks = $_POST['poRemarks'];
		$poDiscount = $_POST['poDiscount'];
		
		echo $sql = "UPDATE	purchasing_podetailsnew
				SET		poShipmentType = '".$shipmentType."',
						poRemarks = '".$poRemarks."',
						poDiscount = '".$poDiscount."'
				WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
		$queryUpdate = $db->query($sql);
		
		header('location:gerald_purchaseOrderStatus.php?poNumber='.$poNumber);
	}
	else if(isset($_GET['changePoNumber']) AND $_GET['changePoNumber']==1)
	{
		$poNumber = $_GET['poNumber'];
		$currentPo = $_GET['currentPo'];
		$changePOType = $_GET['changePOType'];
		
		if($changePOType=='newPOId')
		{
			$sql = "INSERT INTO	`purchasing_podetailsnew`
							(	`poNumber`,			`supplierId`,	`supplierType`,	`poTerms`,
								`poShipmentType`,	`poIncharge`,	`poIssueDate`,	`poRemarks`,		
								`poStatus`,			`poCurrency`,	`poDiscount`,	`poInputDateTime`,
								`checkedBy`,		`approvedBy`)	
					SELECT 		'".$poNumber."',	`supplierId`,	`supplierType`,	`poTerms`,
								`poShipmentType`,	`poIncharge`,	`poIssueDate`,	`poRemarks`,		
								`poStatus`,			`poCurrency`,	`poDiscount`,	`poInputDateTime`,
								`checkedBy`,		`approvedBy`
					FROM		`purchasing_podetailsnew`
					WHERE	poNumber LIKE '".$currentPo."'";
			$queryInsert = $db->query($sql);
			
			$sql = "UPDATE purchasing_podetailsnew SET poStatus = '2' WHERE poNumber LIKE '".$currentPo."' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$sql = "INSERT INTO	`purchasing_pocontents`
							(	`poNumber`,			`productId`,	`itemName`,				`itemDescription`,
								`itemQuantity`, 	`itemUnit`,		`itemContentQuantity`,	`itemContentUnit`,
								`itemPrice`,		`itemStatus`,	`lotNumber`,			`itemFlag`, 
								`dataOne`, 			`dataTwo`, 		`dataThree`, 			`dataFour`, 
								`dataFive`, 		`supplierAlias`,`issueDate`,			`sendingDate`, `receivingDate`)
					SELECT		'".$poNumber."',	`productId`,	`itemName`,				`itemDescription`,
								`itemQuantity`,		`itemUnit`,		`itemContentQuantity`,	`itemContentUnit`,
								`itemPrice`,		`itemStatus`,	`lotNumber`,			`itemFlag`, 
								`dataOne`, 			`dataTwo`, 		`dataThree`, 			`dataFour`, 
								`dataFive`, 		`supplierAlias`,`issueDate`,			`sendingDate`, `receivingDate`
					FROM		`purchasing_pocontents`
					WHERE	poNumber LIKE '".$currentPo."'";
			$queryInsert = $db->query($sql);
			
			$sql = "UPDATE purchasing_pocontents SET itemStatus = '2' WHERE poNumber LIKE '".$currentPo."'";
			$queryUpdate = $db->query($sql);
		}
		else
		{
			$sql = "UPDATE purchasing_podetailsnew SET poNumber = '".$poNumber."' WHERE poNumber LIKE '".$currentPo."' LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE purchasing_pocontents SET poNumber = '".$poNumber."' WHERE poNumber LIKE '".$currentPo."'";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE purchasing_charges SET poNumber = '".$poNumber."' WHERE poNumber LIKE '".$currentPo."'";
			$queryUpdate = $db->query($sql);
		}
		
		header('location:gerald_purchaseOrderStatus.php?poNumber='.$poNumber);
	}
	else if(isset($_GET['uniqueSupplier']))
	{
		$uniqueSupplier = $_GET['uniqueSupplier'];
		
		// ------------------------------------------------------------ Checking Notification ------------------------------------------------------------ //
		$notificationDetail = 'You have new Purchase Order Review';
		$notificationLink = '/V3/4-9 Purchase Order Making Software/gerald_purchaseOrderReview.php';
		
		$notificationIdArray = array();
		$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationDetail = '".$notificationDetail."' AND notificationKey = '".$uniqueSupplier."' AND notificationLink = '".$notificationLink."' AND notificationType = 19";
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
			$sql = "INSERT INTO `system_notificationdetails`
							(	`notificationDetail`,		`notificationKey`,		`notificationLink`,			`notificationType`)
					VALUES	(	'".$notificationDetail."',	'".$uniqueSupplier."',	'".$notificationLink."',	'19')";
			$queryInsert = $db->query($sql);
			
			$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
			$query = $db->query($sql);
			$result = $query->fetch_array();
			$notificationId = $result['max'];
			
			$idNumberArray = array();
			
			if($_GET['country']=='2')
			{
				$idNumberArray = array('0458','0466','J014','J026');
			}
			else
			{
				$idNumberArray = array('0048','0346');
			}
			
			$sql = "SELECT idNumber FROM hr_employee WHERE idNumber IN('".implode("','",$idNumberArray)."') AND status = 1";
			$queryEmployee = $db->query($sql);
			if($queryEmployee AND $queryEmployee->num_rows > 0)
			{
				while($resultEmployee = $queryEmployee->fetch_assoc())
				{
					$idNumber = $resultEmployee['idNumber'];
					
					$sql = "INSERT INTO `system_notification`
									(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
							VALUES	(	'".$notificationId."',	'".$idNumber."',			'0',					'2')";
					$queryInsert = $db->query($sql);
				}
			}
			// ---------------------------------------------------------- END Checking Notification ---------------------------------------------------------- //		
			//~ header('location:gerald_purchaseOrderReview.php');
		}
		
		?>
		<script>
			parent.location.reload();
		</script>
		<?php
		exit(0);
	}
	else if(isset($_POST['purchaseReviewConfirmation']))
	{
		$notificationId = $_GET['notificationId'];
		$lotNumberArray = $_POST['lotNumbers'];
		
		$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
		$queryUpdate = $db->query($sql);
		
		$sql = "UPDATE ppic_workschedule SET availability = '1' WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode = 461 AND processSection = 5";
		$queryUpdate = $db->query($sql);
		
		header('location:/V3/dashboard.php');
	}
	else if(isset($_GET['purchaseReviewDelete']))
	{
		$lotNumber = $_GET['lotNumber'];
		
		$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 AND status = 0 LIMIT 1";
		$queryWorkSched = $db->query($sql);
		if($queryWorkSched AND $queryWorkSched->num_rows > 0)
		{
			$resultWorkSched = $queryWorkSched->fetch_assoc();
			
			finishProcess("",$resultWorkSched['id'], 0, $_SESSION['idNumber'],'');
		}
		
		// ------------------------------------------------------------ Checking Notification ------------------------------------------------------------ //
		$notificationDetail = 'Denied Purchase Order';
		$notificationLink = '/V3/4-9 Purchase Order Making Software/gerald_purchaseOrderMakingSql.php';
		
		$sql = "INSERT INTO `system_notificationdetails`
						(	`notificationDetail`,		`notificationKey`,	`notificationLink`,			`notificationType`)
				VALUES	(	'".$notificationDetail."',	'".$lotNumber."',	'".$notificationLink."',	'20')";
		$queryInsert = $db->query($sql);
		
		$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
		$query = $db->query($sql);
		$result = $query->fetch_array();
		$notificationId = $result['max'];
		
		$sql = "INSERT INTO `system_notification`
						(	`notificationId`,		`notificationTarget`,		`notificationStatus`,	`targetType`)
				VALUES	(	'".$notificationId."',	'5',						'0',					'0')";
		$queryInsert = $db->query($sql);
		
		header('location:gerald_purchaseOrderReview.php?notificationId='.$notificationId);
	}
	else if(isset($_GET['deniedPurchaseOrder']))
	{
		$notificationId = $_GET['notificationId'];
		
		$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId = ".$notificationId."";
		$queryUpdate = $db->query($sql);
		
		header('location:/V3/dashboard.php');
	}
?>
