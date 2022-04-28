<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
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

	function purchasingInsertOrders($key,$poNumber,$email,$poRemarks="")
	{
		
		if($emailValue=="" and $poNumber!="")
		{
			$attachPathFile = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/Email Attachment/".$poNumber.".pdf";
			if(file_exists($attachPathFile))
			{
				unlink($attachPathFile);
			}
		}
		
		include('PHP Modules/mysqliConnection.php');
		include('PHP Modules/anthony_wholeNumber.php');
		//$key = (isset($_GET['key'])) ? $_GET['key'] : "";
		$dataExplode=explode("`",$key);
		$dataExplode2=explode("-",$dataExplode[1]);
		
		$supplierId=$dataExplode[0];			
		$supplierType=$dataExplode2[0];
		$currency=$dataExplode2[1];
		$poCurrency=$dataExplode2[1];
		$manualFlag=(isset($dataExplode2[2])) ? $dataExplode2[2] : 0;
		
		$manualLotArray = array();
		if($manualFlag==1)
		{
			$sql = "SELECT listId, itemName, itemDescription, itemQuantity, itemUnit, itemPrice FROM purchasing_forpurchaseorder WHERE lotNumber = '' AND processRemarks = '".$_SESSION['idNumber']."' AND supplierId = 0 AND supplierType = ".$supplierType."";	
			$queryForPurchaseOrder = $db->query($sql);
			if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
			{
				while($resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc())
				{
					$listId = $resultForPurchaseOrder['listId'];
					$itemName = $resultForPurchaseOrder['itemName'];
					$itemDescription = $resultForPurchaseOrder['itemDescription'];
					$itemQuantity = $resultForPurchaseOrder['itemQuantity'];
					$itemUnit = $resultForPurchaseOrder['itemUnit'];
					$itemPrice = $resultForPurchaseOrder['itemPrice'];
					
					$itemId = '';
					$sql = "SELECT itemId FROM purchasing_items WHERE itemName LIKE '".$itemName."' AND itemDescription LIKE '".$itemDescription."' LIMIT 1";
					$queryItems = $db->query($sql);
					if($queryItems AND $queryItems->num_rows > 0)
					{
						$resultItems = $queryItems->fetch_assoc();
						$itemId = $resultItems['itemId'];
					}
					else
					{
						$sql = "INSERT INTO `purchasing_items`(`itemName`, `itemDescription`) VALUES ('".$itemName."','".$itemDescription."')";
						$queryInsert = $db->query($sql);
						if($queryInsert)
						{
							$itemId = $db->insert_id;
						}
					}
					
					$lot = createPurchasingLotNumber($itemId,3);
					
					if($lot!='')
					{
						$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$itemQuantity." WHERE lotNumber LIKE '".$lot."' LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE purchasing_forpurchaseorder SET lotNumber = '".$lot."', processRemarks = '', supplierId = '".$supplierId."', poCurrency = '".$poCurrency."' WHERE listId = ".$listId." LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$manualLotArray[] = $lot;
					}
				}
				
				$sql = "
					SELECT a.id, a.lotNumber, a.processRemarks, b.supplierType FROM view_workschedule as a
					INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
					WHERE a.processCode = 597 AND a.status = 0 AND a.lotNumber IN('".implode("','",$manualLotArray)."')
				";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],$resultWorkSchedule['processRemarks']);
					}
				}
			}
		}
		
			//~ $poRemarks = ($supplierType == 2 AND $_GET['country']=='1') ? "NOTE: Please include inspection data, Certificate of conformance and Test Report upon delivery and via email." : "";
			$poRemarks = ($supplierType == 2 AND $_GET['country']=='1') ? "NOTE: Please include inspection data, Certificate of conformance and Test Report upon delivery and via email." : $poRemarks;
			
			$supplierAlias = $shipment = $shipmentType = $terms = '';
			$sql = "SELECT supplierAlias, shipment, terms, supplierName, taxStatus FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
			if($supplierType==2)	$sql = "SELECT subconAlias, shipment, terms, subconName, taxStatus FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
			$querySupplier = $db->query($sql);
			if($querySupplier AND $querySupplier->num_rows > 0)
			{
				$resultSupplier = $querySupplier->fetch_row();
				$supplierAlias = $resultSupplier[0];
				$shipment = $resultSupplier[1];
				$shipmentType = $resultSupplier[1];
				$terms = $resultSupplier[2];
				$supplierName = $resultSupplier[3];
				$taxStatus = $resultSupplier[4];
			}
			
			
			///-GET latest PO# End
			
			
			$customerAlias ="";
			$poTargetReceiveDate = "0000-00-00";//Temporary
			//~ $poRemarks = "";
			$poDiscount = "";
			
			$chargeDescriptionArray = array();
			$chargeQuantityArray = array();
			$chargeUnitArray = array();
			$chargeUnitPriceArray = array();
			
			
		$poIssueDate = date('Y-m-d');
		$prepared = $_SESSION['idNumber'];
		
		$sqlFilter = '';		
		
		
		
		$sql = "INSERT INTO	`purchasing_podetailsnew`
						(	`poNumber`,			`supplierId`,				`supplierType`,		`supplierAlias`,	`poTerms`,
							`poShipmentType`,	`poTargetReceiveDate`,		`poIncharge`,		`poIssueDate`,		`poRemarks`,		
							`poStatus`,			`poCurrency`,				`poDiscount`,		`poInputDateTime`)
				VALUES	(	'".$poNumber."',	'".$supplierId."',			'".$supplierType."','".$supplierAlias."','".$terms."',
							'".$shipmentType."','".$poTargetReceiveDate."',	'".$prepared."',	'".$poIssueDate."',	'".$poRemarks."',
							0,					'".$poCurrency."',			'".$poDiscount."',	NOW())";
		$queryInsert = $db->query($sql);
		//echo $sql."<br>";
		$poContentDetailsDataArray = array();
		
		$sqlMain = "INSERT INTO `purchasing_pocontents`(`poNumber`, `orderNo`, `productId`, `itemName`, `itemDescription`, `itemQuantity`, `itemUnit`, `itemContentQuantity`, `itemContentUnit`, `itemPrice`, `itemStatus`, `lotNumber`, `itemFlag`, `dataOne`, `dataTwo`, `dataThree`, `dataFour`, `dataFive`, `supplierAlias`, `issueDate`, `sendingDate`, `receivingDate`, `itemRemarks`) VALUES";//2018-04-06
		$sqlValueArray = array();
		$counter = 0;
		
		$orderNo = 0;
		$dateNeededArray = array();
		$lotNumberArray = array();
    	$sqlRose1 = "SELCT * FROM purchasing_forpurchaseorder WHERE supplierId LIKE ".$supplierId." and supplierType LIKE ".$supplierType." and poCurrency LIKE ".$currency."";
		$sql = "SELECT * FROM purchasing_forpurchaseorder WHERE supplierId LIKE '".$supplierId."' and supplierType LIKE '".$supplierType."' and poCurrency LIKE '".$currency."'";
		$sql = "
			SELECT b.listId, b.lotNumber, b.processRemarks, b.itemRemarks, b.productId, b.dateNeeded, b.itemPrice FROM ppic_workschedule as a
			INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
			WHERE a.processCode = 597 AND a.status = 1 AND b.supplierId LIKE '".$supplierId."' and b.supplierType LIKE '".$supplierType."' and b.poCurrency LIKE '".$currency."'
		";		
		if($manualFlag==1)
		{
			$sql = "
				SELECT b.listId, b.lotNumber, b.processRemarks, b.itemRemarks, b.productId, b.dateNeeded, b.itemPrice FROM ppic_workschedule as a
				INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
				WHERE a.processCode = 597 AND a.status = 1 AND b.supplierId LIKE '".$supplierId."' and b.supplierType LIKE '".$supplierType."' and b.poCurrency LIKE '".$currency."'
				AND a.lotNumber IN('".implode("','",$manualLotArray)."')
			";
		}
		//echo $sql."<br>";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$forPurchaseListId = $resultWorkSchedule['listId'];
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$targetFinish = $resultWorkSchedule['dateNeeded'];
				$dateNeededDB = $resultWorkSchedule['dateNeeded'];
				$processRemarks = $resultWorkSchedule['processRemarks'];
				$itemRemarks = $resultWorkSchedule['itemRemarks'];
				$productId = $resultWorkSchedule['productId'];
				$itemPrice = $resultWorkSchedule['itemPrice'];
				//echo "<br>".$lotNumber."<br>";
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
				
				$dateNeededArray[] = $dateNeededDB;
				
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
							
							$materialComputationId = '';
							$sql = "SELECT materialComputationId FROM ppic_materialcomputationdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
							$queryMaterialComputationDetails = $db->query($sql);
							if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
							{
								$resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc();
								$materialComputationId = $resultMaterialComputationDetails['materialComputationId'];
							}
							
							$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
							$queryMaterialComputation = $db->query($sql);
							if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
							{
								$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
								$dateNeeded = $resultMaterialComputation['dateNeeded'];
								//~ $dateNeededArray[] = $dateNeeded;
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
				
				if($manualFlag==1)
				{
					$listId = $lotNumber;
					$sql = "SELECT itemName, itemDescription, itemQuantity, itemUnit, itemPrice FROM purchasing_forpurchaseorder WHERE listId = ".$forPurchaseListId." LIMIT 1";
					$queryForPurchase = $db->query($sql);
					if($queryForPurchase AND $queryForPurchase->num_rows > 0)
					{
						$resultForPurchase = $queryForPurchase->fetch_assoc();
						$productName = $resultForPurchase['itemName'];
						$productDescription = $resultForPurchase['itemDescription'];
						$workingQuantity = $resultForPurchase['itemQuantity'];
						$productUnit = $resultForPurchase['itemUnit'];
						$unitPrice = $resultForPurchase['itemPrice'];
						
						$dataOne = $productName;
						$dataTwo = $productDescription;
						
						$sqlValueArray[] = "('".$poNumber."',	'0','".$db->real_escape_string($productName)."','".$db->real_escape_string($productDescription)."','".$workingQuantity."','".$productUnit."','".$productContentQuantity."','".$productContentUnit."','".$unitPrice."','0','".$lotNumber."','".$itemFlag."','".$db->real_escape_string($dataOne)."','".$db->real_escape_string($dataTwo)."','".$dataThree."','".$dataFour."','".$dataFive."', '".$supplierAlias."', '".$poIssueDate."', '".$sendingDate."', '".$dateNeededDB."', '".$itemRemarks."')";//2018-04-06
						$counter++;
						
						if($counter == 50)
						{
							$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
							//echo $sqlInsert."<br>";
							$queryInsert = $db->query($sqlInsert);
							$sqlValueArray = array();
							$counter = 0;
							$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser1',now(),'rose_purchaseOrderMakingSql.php')";
							$queryInsertRose = $db->query($sqlRosemie);
						}						
					}
				}
				else
				{
					//$productIds = $processRemarks;
					$productIds = $productId;
					//echo "<br>".$lotNumber."<br>";
					$productIdsCount = count(explode(",",$productIds));
					$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId IN(".$productIds.") AND itemStatus != 2";
					//echo "<br>".$sql."<br>";
					$queryPoContents = $db->query($sql);
					$poProductIdCount = ($queryPoContents AND $queryPoContents->num_rows) ? $queryPoContents->num_rows : 0;
					if($poProductIdCount >= $productIdsCount)
					{
						continue;
					}
				
					// if($identifier==1 OR ($identifier==4 AND $supplyType==2))
					// {
						// $subconProcessCount = 0;
						// if($identifier==1)
						// {
							// $sql = "SELECT b.a, b.processCode FROM cadcam_subconlist as b
							// INNER JOIN engineering_subconprocessor as c on c.subconId=".$supplierId." and c.a=b.a
							// WHERE b.partId = ".$partId."";
							// $querySubconList = $db->query($sql);
							// if($querySubconList AND $querySubconList->num_rows > 0)
							// {
								// $subconProcessCount = $querySubconList->num_rows;
							// }						//echo "<br>".$sql."<br>";
						// }
						// else if($identifier==4)
						// {
							// $cadamTreatmentId = '';
							// $sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
							// $querySubconMaterial = $db->query($sql);
							// if($querySubconMaterial->num_rows > 0)
							// {
								// $resultSubconMaterial = $querySubconMaterial->fetch_array();
								// $cadamTreatmentId = $resultSubconMaterial['treatmentId'];
							// }
							
							// $sql = "SELECT processCode FROM engineering_subcontreatment WHERE treatmentId = ".$cadamTreatmentId."";
							// $querySubconTreatment = $db->query($sql);
							// if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
							// {
								// $subconProcessCount = $querySubconTreatment->num_rows;
							// }
						// }	//echo "<br>".$subconProcessCount." VS ".$productIdsCount."<br>";
						// if($subconProcessCount!=$productIdsCount)
						// {
							// continue;
						// }
					// }
					
					$packingCostFlag = 0;
					$sqlRose2 = "SELCT productId, productName, productDescription, productUnit, productContentQuantity, productContentUnit FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType."";
					$sql = "SELECT `productId`, `productName`, `productDescription`, `productUnit`, `productContentQuantity`, `productContentUnit` FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType."";
					//echo $sql."<br>";
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
								//echo "ROSEMIE";
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
									
									$breakFlag = 0;
									
									if($priceLowerRange != 0 AND $priceUpperRange != 0)
									{
										if($priceLowerRange == $priceUpperRange)
										{
											if($workingQuantity >= $priceLowerRange)	$breakFlag = 1;
										}
										else
										{
											if($workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange)	$breakFlag = 1;
										}
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
							
							if($_GET['country']==2)
							{
								$unitPrice = $itemPrice;//2021-09-07
							}
														
							
							if($sendingDate=='')	$sendingDate = '0000-00-00';
							if($receivingDate=='')	$receivingDate = '0000-00-00';

							if($supplierType==1)	$orderNo++;
							
							//~ $sqlValueArray[] = "('".$poNumber."',	'".$productId."','".$productName."','".$productDescription."','".$workingQuantity."','".$productUnit."','".$productContentQuantity."','".$productContentUnit."','".$unitPrice."','0','".$lotNumber."',0,'".$dataOne."','".$dataTwo."','".$dataThree."','".$dataFour."','".$dataFive."')";
							$sqlValueArray[] = "('".$poNumber."',	'".$orderNo."','".$productId."','".$db->real_escape_string($productName)."','".$db->real_escape_string($productDescription)."','".$workingQuantity."','".$productUnit."','".$productContentQuantity."','".$productContentUnit."','".$unitPrice."','0','".$lotNumber."','".$itemFlag."','".$db->real_escape_string($dataOne)."','".$db->real_escape_string($dataTwo)."','".$dataThree."','".$dataFour."','".$dataFive."', '".$supplierAlias."', '".$poIssueDate."', '".$sendingDate."', '".$dateNeededDB."', '".$itemRemarks."')";//2018-04-06
							$counter++;
							
							if($counter == 50)
							{
								$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
								//echo $sqlInsert."<br>";
								$queryInsert = $db->query($sqlInsert);
								$sqlValueArray = array();
								$counter = 0;
								$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser1',now(),'rose_purchaseOrderMakingSql.php')";
								$queryInsertRose = $db->query($sqlRosemie);
							}
						}
					}
				}
            
            	//updateLOT
				//INSERT INTO `ppic_workschedule` (`id`, `poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `previousActualFinish`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`, `stAnalysis`) VALUES
				//(3740197, 703421, 110, '300251416600010', '20-08-2688', 'RF8361D-121-1C', '', '598', 2, 5, 'メッキ Plating\r\n', '0000-00-00 00:00:00', '2020-08-27', '2020-08-27', 0, '2020-08-20', '2020-10-28', '2020-10-28', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00', '0.0', '', 0, 0, 1, 2, 0, 0);

				//$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 598 AND status = 0 AND trim(processRemarks) like '".trim($processRemarks)."' LIMIT 1";
            	//$sqlRose3 = "SELCT id FROM ppic_workschedule WHERE lotNumber LIKE ".$lotNumber." AND processCode = 598 AND status = 0 AND trim(processRemarks) like ".trim($processRemarks)." LIMIT 1";
				
				//SELECT a.id FROM view_workschedule as a INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks  AND b.listId = ".$forPurchaseListId." WHERE a.processCode = 598 AND a.status = 0 AND a.lotNumber LIKE '".$lotNumber."'
				$sql = "SELECT a.id, a.processRemarks FROM view_workschedule as a INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks  AND b.listId = ".$forPurchaseListId." WHERE a.processCode = 598 AND a.status = 0 AND a.lotNumber LIKE '".$lotNumber."'";
            	$sqlRose3 = "SELCT a.id FROM view_workschedule as a INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks  AND b.listId = ".$forPurchaseListId." WHERE a.processCode = 598 AND a.status = 0 AND a.lotNumber LIKE ".$lotNumber."";
				
				$queryWorkSched = $db->query($sql);
				if($queryWorkSched AND $queryWorkSched->num_rows > 0)
				{
					$resultWorkSched = $queryWorkSched->fetch_assoc();
				
					finishProcess("",$resultWorkSched['id'], 0, $_SESSION['idNumber'],$resultWorkSched['processRemarks']);
				}
            	$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber.$sqlRose3."inser2b',now(),'rose_purchaseOrderMakingSql.php')";
				$queryInsertRose = $db->query($sqlRosemie);
			}
			if($counter > 0)
			{
				
				$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				//echo $sqlInsert."<br>";
				$queryInsert = $db->query($sqlInsert);
				$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser2',now(),'rose_purchaseOrderMakingSql.php')";
				$queryInsertRose = $db->query($sqlRosemie);
			}
			
			if($supplierType==2)
			{
				$itemCount = 0;
				$sql = "SELECT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND itemStatus != 2 GROUP BY lotNumber ORDER BY poContentId";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					while($resultPoContents = $queryPoContents->fetch_assoc())
					{
						$lote = $resultPoContents['lotNumber'];
						
						$sql = "UPDATE purchasing_pocontents SET orderNo = '".++$itemCount."' WHERE poNumber LIKE '".$poNumber."' AND lotNumber LIKE '".$lote."' AND itemStatus!=2";
						$queryUpdate = $db->query($sql);
					}
				}			
			}
		}
		
		//~ print_r($dateNeededArray);
		
		$dateNeededArray = array_values(array_unique(array_filter($dateNeededArray)));
		//~ print_r($dateNeededArray);
		if(count($dateNeededArray) == 1)
		{
			$sql = "UPDATE purchasing_podetailsnew SET poTargetReceiveDate = '".$dateNeededArray[0]."' WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		
		//~ if($_SESSION['idNumber']=='0346') exit(0);
		
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
					//if($queryPoContentDetails AND $queryPoContentDetails->num_rows == 0)
                    if($queryPoContentDetails->num_rows == 0)
					{
						$sqlValueArray[] = "('".$lotNumber."','".$workingQuantity."','".$sendingDate."','".$receivingDate."','".$poContentId."')";
						$counter++;
						
						if($counter == 50)
						{	//echo "<br>";
							$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
							$queryInsert = $db->query($sqlInsert);
							$sqlValueArray = array();
							$counter = 0;
							$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser3',now(),'rose_purchaseOrderMakingSql.php')";
							$queryInsertRose = $db->query($sqlRosemie);
						}
                    	$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$lotNumber."~".$poNumber."inser4b',now(),'rose_purchaseOrderMakingSql.php')";
						$queryInsertRose = $db->query($sqlRosemie);
					}
				}
			}
			if($counter > 0)
			{	//echo "<br>";
				$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryInsert = $db->query($sqlInsert);
				$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser4',now(),'rose_purchaseOrderMakingSql.php')";
				$queryInsertRose = $db->query($sqlRosemie);
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
					//echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
					$queryInsert = $db->query($sqlInsert);
					$sqlValueArray = array();
					$counter = 0;
					$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser5',now(),'rose_purchaseOrderMakingSql.php')";
					$queryInsertRose = $db->query($sqlRosemie);
				}
			}
			if($counter > 0)
			{
				//echo "<br>".$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryInsert = $db->query($sqlInsert);
				$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$poNumber."inser6',now(),'rose_purchaseOrderMakingSql.php')";
				$queryInsertRose = $db->query($sqlRosemie);
			}
		}
		
		
		//~ if($_GET['country']=='2')
		//~ {
			$sql = "UPDATE purchasing_podetailsnew SET checkedBy = '".$prepared."', approvedBy = '".$prepared."' WHERE poNumber LIKE '".$poNumber."' AND checkedBy = '' AND approvedBy = '' LIMIT 1";
			$queryUpdate = $db->query($sql);	
			//echo $sql."<br>";
		//~ }	
		
		//GE code accounting start
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
						foreach($payableIdArray as $keyz => $arrayValue)
						{
							$sql = "UPDATE accounting_payablesnew SET type = ".$keyz." WHERE payableId IN(".implode(",",$arrayValue).")";
							$queryUpdate = $db->query($sql);
						}
					}
				}
			}
		//GE code accounting end
		//echo $sqlRose3."<br>".$sqlRose2."<br>".$sqlRose1."<br><br>";
       
    	$sqlRosemie = "INSERT INTO rosequery (stringSQL,logDate,filePath) VALUES('".$sqlRose3."\n".$sqlRose2."\n".$sqlRose1."',now(),'rose_purchaseOrderMakingSql.php')";
        //echo $sqlRosemie;
		$queryInsertRose = $db->query($sqlRosemie);
		
		$manualLotFilter = "";
		if($manualFlag==1)
		{
			$manualLotFilter = " AND lotNumber IN('".implode("','",$manualLotArray)."')";
		}		
		
		$sqlDelete = "DELETE FROM purchasing_forpurchaseorder WHERE supplierId LIKE '".$supplierId."' and supplierType LIKE '".$supplierType."' and poCurrency LIKE '".$currency."'".$manualLotFilter;
		$queryUpdate = $db->query($sqlDelete);			
	}
	function purchasingInsertTMP($poNumber)
	{
    	include('PHP Modules/mysqliConnection.php');
    	include('../../54 Automated Material Computation Software/ace_materialTemporaryBooking.php');
		
		$sql = "UPDATE purchasing_podetailsnew SET poStatus = 4 WHERE poNumber LIKE '".$poNumber."'";
		$queryUpdate = $db->query($sql);	//rose: by gerald 2020-08-07 8:48AM
		
		$sql = "SELECT * FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
		//echo $sql."<br>";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$poContentId = $resultWorkSchedule['poContentId'];
				$poNumber = $resultWorkSchedule['poNumber'];
				$productId = $resultWorkSchedule['productId'];
				$itemName = $resultWorkSchedule['itemName'];
				$itemDescription = $resultWorkSchedule['itemDescription'];
				$itemQuantity = $resultWorkSchedule['itemQuantity'];
				$itemUnit = $resultWorkSchedule['itemUnit'];
				$itemContentQuantity = $resultWorkSchedule['itemContentQuantity'];
				$itemContentUnit = $resultWorkSchedule['itemContentUnit'];
				$itemPrice = $resultWorkSchedule['itemPrice'];
				$itemStatus = $resultWorkSchedule['itemStatus'];
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$itemFlag = $resultWorkSchedule['itemFlag'];
				$dataOne = $resultWorkSchedule['dataOne'];
				$dataTwo = $resultWorkSchedule['dataTwo'];
				$dataThree = $resultWorkSchedule['dataThree'];
				$dataFour = $resultWorkSchedule['dataFour'];
				$dataFive = $resultWorkSchedule['dataFive'];
				$supplierAlias = $resultWorkSchedule['supplierAlias'];					
				$issueDate = $resultWorkSchedule['issueDate'];
				$sendingDate = $resultWorkSchedule['sendingDate'];
				$receivingDate = $resultWorkSchedule['receivingDate'];
				$itemRemarks = $resultWorkSchedule['itemRemarks'];
				if($receivingDate=="0000-00-00")
				{
					$receivingDate=date("Y-m-d",strtotime("+3 days", strtotime($issueDate)));
				}
				
				$identifier	=0;			
				$supplyType	=0;			
				$poContentIds="";			
					$sql2 = "SELECT identifier, status, poContentId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryLotList = $db->query($sql2);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$identifier = $resultLotList['identifier'];
						$supplyType = $resultLotList['status'];
						$poContentIds = $resultLotList['poContentId'];//rose: by gerald 2020-08-07 8:48AM
						// if($poContentIds==""){$poContentIds = $resultLotList['poContentId'];}
						// else{$poContentIds = $poContentIds.",".$resultLotList['poContentId'];}
					}
				if($poContentIds==""){$poContentIds = $poContentId;}
				else{$poContentIds = $poContentIds.",".$poContentId;}//rose: by gerald 2020-08-07 8:48AM
				
				$sql = "SELECT GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ',') as poContentIds FROM purchasing_pocontents WHERE `lotNumber` LIKE '".$lotNumber."' AND itemStatus != 2 GROUP BY lotNumber";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					$resultPoContents = $queryPoContents->fetch_assoc();
					$poContentIds = $resultPoContents['poContentIds'];
				}
				
				if($identifier==4 and ($supplyType==1 or $supplyType==4))
				{
					$poStockFlag = 0;
					$inventoryId = $sourceId = $inventoryQuantity = '';
					// if($_SESSION['idNumber']=='0346')
					// {
						$sql = "SELECT materialType, thickness FROM system_mergematerialspecs WHERE materialType LIKE '{$dataOne}' AND thickness LIKE '{$dataTwo}'";
						$queryMergeMaterialSpecs = $db->query($sql);
						if($queryMergeMaterialSpecs AND $queryMergeMaterialSpecs->num_rows > 0)
						{
							$sql = "SELECT inventoryId, sourceId, inventoryQuantity FROM warehouse_inventory WHERE inventoryId NOT IN('M80988','M81486') AND type = 1 AND dataOne LIKE '".$dataOne."' AND dataTwo = ".$dataTwo." AND dataThree = ".$dataThree." AND dataFour = ".$dataFour." AND dataFive = '".$dataFive."' ORDER BY listId DESC LIMIT 1";
							$queryInventory = $db->query($sql);
							if($queryInventory AND $queryInventory->num_rows > 0)
							{
								$resultInventory = $queryInventory->fetch_assoc();
								$inventoryId = $resultInventory['inventoryId'];
								$sourceId = $resultInventory['sourceId'];
								$inventoryQuantity = $resultInventory['inventoryQuantity'];
							}
							$poStockFlag = 1;							
						}
						else
						{
							if(($dataOne=='MSM CC D ZC 90' AND $dataTwo==1.6) OR ($dataOne=='NSDCC QN K12' AND $dataTwo==2.0) OR ($dataOne=='NSDCC QN K12' AND $dataTwo==0.8) OR ($dataOne=='NSDHC QN X K18' AND $dataTwo==3.2) OR ($dataOne=='SPCC-SD' AND $dataTwo==2.0))
							{
								$sql = "SELECT inventoryId, sourceId, inventoryQuantity FROM warehouse_inventory WHERE inventoryId NOT IN('M80988','M81486') AND type = 1 AND dataOne LIKE '".$dataOne."' AND dataTwo = ".$dataTwo." AND dataThree = ".$dataThree." AND dataFour = ".$dataFour." AND dataFive = '".$dataFive."' ORDER BY listId DESC LIMIT 1";
								$queryInventory = $db->query($sql);
								if($queryInventory AND $queryInventory->num_rows > 0)
								{
									$resultInventory = $queryInventory->fetch_assoc();
									$inventoryId = $resultInventory['inventoryId'];
									$sourceId = $resultInventory['sourceId'];
									$inventoryQuantity = $resultInventory['inventoryQuantity'];
								}
								$poStockFlag = 1;
							}							
						}
					// }
					
					if($inventoryId=='')
					{
						$sql = "INSERT INTO warehouse_temporaryinventory (supplierAlias, stockDate, stockTime, type, dataOne, dataTwo, dataThree, dataFour, dataFive, quantity, pvcStatus, idNumber, poContentId, lotNumber, linkedBalQty, tempBalQty, inputType) VALUES ('".$supplierAlias."',NOW(),NOW(),'".$supplyType."','".$dataOne."','".$dataTwo."','".$dataThree."','".$dataFour."','".$dataFive."','".$itemQuantity."','".$itemFlag."','".$_SESSION['idNumber']."','".$poContentId."','".$lotNumber."',0,'".$itemQuantity."',1)";
						$queryInsert = $db->query($sql);
						if($queryInsert)
						{
							$tempListId = $db->insert_id;
							
							$inventoryId = $tempInventoryId = "TMP".$tempListId;
							
							$sql = "SELECT inventoryId FROM warehouse_inventory WHERE inventoryId LIKE '".$tempInventoryId."' LIMIT 1";
							$queryInventory = $db->query($sql);
							if($queryInventory AND $queryInventory->num_rows > 0)
							{
								$sql = "SELECT materialComputationId, materialComputationIdSource FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryMaterialComputation = $db->query($sql);
								if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
								{
									$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
									$materialComputationId = ($resultMaterialComputation['materialComputationIdSource'] > 0) ? $resultMaterialComputation['materialComputationIdSource'] : $resultMaterialComputation['materialComputationId'];
									
									$lotNumberArray = array();
									$sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
									$queryMaterialComputationDetails = $db->query($sql);
									if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
									{
										while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
										{
											$lotNumberArray[] = $resultMaterialComputationDetails['lotNumber'];
										}
										materialTemporaryBooking('',$lotNumberArray,$tempInventoryId);
									}
								}							
							}
						}
					}

					if($poStockFlag==1)
					{
						$sql = "SELECT listId FROM warehouse_postock WHERE inventoryId LIKE '".$inventoryId."'";
						$queryPoStock = $db->query($sql);
						if($queryPoStock AND $queryPoStock->num_rows == 0)
						{
							$poIdSource = 0;
							$sql = "SELECT poId FROM ppic_lotlist WHERE lotNumber LIKE '".$sourceId."' AND identifier = 4 LIMIT 1";
							$queryLotList = $db->query($sql);
							if($queryLotList AND $queryLotList->num_rows > 0)
							{
								$resultLotList = $queryLotList->fetch_assoc();
								$poIdSource = $resultLotList['poId'];
							}

							$sql = "
								INSERT INTO `warehouse_postock`
										(	`inventoryId`, `poContentId`, `lotNumber`, `inventoryQuantity`, `stock`, `type`)
								VALUES	(	'".$inventoryId."','".$poIdSource."','".$sourceId."','".$inventoryQuantity."','0','0'),
										(	'".$inventoryId."','".$poIdSource."','".$sourceId."','-".$inventoryQuantity."','".$inventoryQuantity."','1')
							";
							$queryInsert = $db->query($sql);							
						}

						$sql = "SELECT listId FROM warehouse_postock WHERE inventoryId LIKE '".$inventoryId."' AND poContentId = '".$poContentId."' AND lotNumber LIKE '".$lotNumber."' AND inventoryQuantity = ".$itemQuantity." AND stock = 0 AND type = 0 LIMIT 1";
						$queryPoStock = $db->query($sql);
						if($queryPoStock AND $queryPoStock->num_rows == 0)
						{
							$sql = "
								INSERT INTO `warehouse_postock`
										(	`inventoryId`, `poContentId`, `lotNumber`, `inventoryQuantity`, `stock`, `type`)
								VALUES	(	'".$inventoryId."','".$poContentId."','".$lotNumber."','".$itemQuantity."','0','0')
							";
							$queryInsert = $db->query($sql);
						}

						$totalQty2 = 0;
						$sql = "SELECT (SUM(inventoryQuantity) + SUM(stock)) as totalQty FROM warehouse_postock WHERE inventoryId LIKE '".$inventoryId."'";
						$queryPoStock = $db->query($sql);
						if($queryPoStock AND $queryPoStock->num_rows > 0)
						{
							$resultPoStock = $queryPoStock->fetch_assoc();
							$totalQty2 = $resultPoStock['totalQty'];
						}

						$sql = "UPDATE warehouse_inventory SET inventoryQuantity = '".$totalQty2."' WHERE inventoryId = '".$inventoryId."' LIMIT 1";
						$queryUpdate = $db->query($sql);

						$sql = "SELECT materialComputationId, materialComputationIdSource FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryMaterialComputation = $db->query($sql);
						if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
						{
							$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
							$materialComputationId = ($resultMaterialComputation['materialComputationIdSource'] > 0) ? $resultMaterialComputation['materialComputationIdSource'] : $resultMaterialComputation['materialComputationId'];
							
							$lotNumberArray = array();
							$sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
							$queryMaterialComputationDetails = $db->query($sql);
							if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
							{
								while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
								{
									$lotNumberArray[] = $resultMaterialComputationDetails['lotNumber'];
								}
								materialTemporaryBooking('',$lotNumberArray,$inventoryId);
							}
						}						
					}
				}
				if($identifier==1 OR ($identifier==4 AND $supplyType==2))
				{
					$sql = "UPDATE ppic_lotlist SET poContentId = '".$poContentIds."' WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryUpdate = $db->query($sql);
					
					updateWorkschedulePoContentId($lotNumber);
					
                	//2020-11-10 rose START accounting process
						$sql = "SELECT a.id, a.processOrder as pOrder , a.processRemarks, a.targetFinish as targetF FROM view_workschedule as a INNER JOIN purchasing_pocontents as b ON b.lotNumber = a.lotNumber AND b.dataThree = a.processRemarks  AND b.poContentId = ".$poContentId." WHERE a.processCode IN (137,138,229) AND a.status = 0 AND a.lotNumber LIKE '".$lotNumber."'";
						$queryWorkSched = $db->query($sql);
						if($queryWorkSched AND $queryWorkSched->num_rows > 0)
						{
							$resultWorkSched = $queryWorkSched->fetch_assoc();
							$sql3 = "UPDATE ppic_workschedule SET processOrder = (processOrder+4) WHERE processOrder > ".($resultWorkSched['pOrder']+1)." and lotNumber like '".$lotNumber."'";
							$updateQuery3 = $db->query($sql3);
							/* 2021-06-21 these processes were not yet used
								$sql = "INSERT INTO `ppic_workschedule`
										(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
								VALUES	(	'".$lotNumber."',		600,						".($resultWorkSched['pOrder']+0).",				'".$resultWorkSched['targetF']."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		601,						".($resultWorkSched['pOrder']+1).",				'".$resultWorkSched['targetF']."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		602,						".($resultWorkSched['pOrder']+2).",				'".$resultWorkSched['targetF']."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		603,						".($resultWorkSched['pOrder']+3).",				'".$resultWorkSched['targetF']."',	'',				0,			'',					37,					0)";
							$queryInsert = $db->query($sql);
							*/
						}
					//2020-11-10 rose END accounting process
				}
				else if($identifier==4)
				{
					//$sql = "UPDATE ppic_lotlist SET poId = ".$poContentIds." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$sql = "UPDATE ppic_lotlist SET poId = ".$poContentId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1"; //rose: by gerald 2020-08-07 8:48AM
					$queryUpdate = $db->query($sql);
					$inspectionProcess = 163;
					if($supplyType == 1 OR $supplyType == 2)
					{
						$inspectionProcess = 352;
					}
					// 137, 163, 352, 353, 555,)Receiving (Warehouse), Incoming Inspection, Receiving Inspection, Warehouse Storage, Certificate Upload
					if(($supplyType == 1 OR $supplyType == 4) AND $_GET['country']==1)
					{	
						/* 2021-06-21 these processes were not yet used
						$sql = "INSERT INTO `ppic_workschedule`
										(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
								VALUES	(	'".$lotNumber."',		555,						3,				'".$receivingDate."', '',				0,			'',					34,					1),
										(	'".$lotNumber."',		137,						4,				'".$receivingDate."',	'',				0,			'',					36,					0),
										(	'".$lotNumber."',		".$inspectionProcess.",		5,				'".$receivingDate."', '',				0,			'',					4,					0),
										(	'".$lotNumber."',		599,						6,				'".$receivingDate."',	'',				0,			'',					4,					0),
										(	'".$lotNumber."',		600,						7,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		601,						8,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		602,						9,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		603,						10,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		353,						11,				'".$receivingDate."', '',				0,			'',					31,					0)
							";
						$queryInsert = $db->query($sql);
						*/
						
						$sql = "INSERT INTO `ppic_workschedule`
										(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
								VALUES	(	'".$lotNumber."',		555,						3,				'".$receivingDate."', '',				0,			'',					34,					1),
										(	'".$lotNumber."',		137,						4,				'".$receivingDate."',	'',				0,			'',					36,					0),
										(	'".$lotNumber."',		".$inspectionProcess.",		5,				'".$receivingDate."', '',				0,			'',					4,					0),
										(	'".$lotNumber."',		353,						6,				'".$receivingDate."', '',				0,			'',					31,					0)
							";
						$queryInsert = $db->query($sql);						
					}
					else
					{
						/* 2021-06-21 these processes were not yet used
						$sql = "INSERT INTO `ppic_workschedule`
										(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
								VALUES	(	'".$lotNumber."',		137,						3,				'".$receivingDate."',	'',				0,			'',					4,					1),
										(	'".$lotNumber."',		".$inspectionProcess.",		4,				'".$receivingDate."', '',				0,			'',					4,					0),
										(	'".$lotNumber."',		599,						5,				'".$receivingDate."',	'',				0,			'',					4,					0),
										(	'".$lotNumber."',		600,						6,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		601,						7,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		602,						8,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		603,						9,				'".$receivingDate."',	'',				0,			'',					37,					0),
										(	'".$lotNumber."',		353,						10,				'".$receivingDate."', '',				0,			'',					16,					0)
							";
						$queryInsert = $db->query($sql);
						*/
						
						$sql = "INSERT INTO `ppic_workschedule`
										(	`lotNumber`,		`processCode`,				`processOrder`,				`targetFinish`,		`actualFinish`,	`status`,	`employeeId`,		`processSection`,	`availability`)
								VALUES	(	'".$lotNumber."',		137,						3,				'".$receivingDate."',	'',				0,			'',					36,					1),
										(	'".$lotNumber."',		".$inspectionProcess.",		4,				'".$receivingDate."', '',				0,			'',					4,					0),
										(	'".$lotNumber."',		353,						5,				'".$receivingDate."', '',				0,			'',					31,					0)
							";
						$queryInsert = $db->query($sql);
					}
					if($queryInsert)
					{
						$partName = $partNumber = $customerAlias = '';
						$customerAlias = $resultWorkSchedule['supplierAlias'];
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
				}
			}			
		}
	}
?>
