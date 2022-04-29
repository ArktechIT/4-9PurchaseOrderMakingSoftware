<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/rose_prodfunctions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
	
	function insertForPurchase($id)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "SELECT id, lotNumber, processRemarks FROM ppic_workschedule WHERE id = ".$id." LIMIT 1";
		$query = $db->query($sql);
		if($query AND $query->num_rows > 0)
		{
			$result = $query->fetch_assoc();
			$workSchedId = $result['id'];
			$lotNumber = $result['lotNumber'];
			$processRemarks = $result['processRemarks'];
			
			$partId = $workingQuantity = $identifier = $supplyType = '';
			$sql = "SELECT partId, workingQuantity, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$partId = $resultLotList['partId'];
				$workingQuantity = $resultLotList['workingQuantity'];
				$identifier = $resultLotList['identifier'];
				$supplyType = $resultLotList['status'];
			}	
			
			if($identifier==1)
			{
				$treatmentId = '';
				if($processRemarks!='')
				{
					$sql = "SELECT treatmentId FROM engineering_treatment WHERE treatmentName LIKE '".$processRemarks."' LIMIT 1";
					$queryTreatment = $db->query($sql);
					if($queryTreatment AND $queryTreatment->num_rows > 0)
					{
						$resultTreatment = $queryTreatment->fetch_assoc();
						$treatmentId = $resultTreatment['treatmentId'];
						$treatmentName = $processRemarks;
					}
				}
				
				if($treatmentId=='')
				{
					$tempTreatmentIdArray = array();
					$sql = "SELECT processCode FROM cadcam_subconlist WHERE partId = ".$partId."";
					$querySubconList = $db->query($sql);
					if($querySubconList AND $querySubconList->num_rows > 0)
					{
						while($resultSubconList = $querySubconList->fetch_assoc())
						{
							$tempTreatmentIdArray[] = $resultSubconList['processCode'];
						}
						
						$sql = "SELECT treatmentId, treatmentName FROM engineering_treatment WHERE treatmentId IN(".implode(",",$tempTreatmentIdArray).")";
						$queryTreatment = $db->query($sql);
						if($queryTreatment AND $queryTreatment->num_rows > 0)
						{
							while($resultTreatment = $queryTreatment->fetch_assoc())
							{
								$treatmentName = $resultTreatment['treatmentName'];
								
								$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 597 AND processRemarks LIKE '".$treatmentName."' LIMIT 1";
								$queryWorkschedule = $db->query($sql);
								if($queryWorkschedule AND $queryWorkschedule->num_rows == 0)
								{
									$sql = "UPDATE ppic_workschedule SET processRemarks = '".$treatmentName."' WHERE id = ".$workSchedId." LIMIT 1";
									$queryUpdate = $db->query($sql);
									
									$treatmentId = $resultTreatment['treatmentId'];
									break;
								}
							}
						}
					}
				}
				
				$aArray = array();
				$sql = "SELECT a, processCode FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$treatmentId."";
				$querySubconList = $db->query($sql);
				if($querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_array())
					{
						$aArray[] = $resultSubconList['a'];
					}
				}
				
				$sql = "SELECT a, subconId FROM engineering_subconprocessor WHERE a IN(".implode(",",$aArray).")";
				$querySubconProcessor = $db->query($sql);
				if($querySubconProcessor AND $querySubconProcessor->num_rows > 0)
				{
					if($querySubconProcessor->num_rows==1)
					{
						$resultSubconProcessor = $querySubconProcessor->fetch_assoc();
						$a = $resultSubconProcessor['a'];
						$subconId = $resultSubconProcessor['subconId'];
						
						$productIdArray = array();
						$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$a." AND supplyType = 5";
						$querySupplierProductLinking = $db->query($sql);
						if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
						{
							while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
							{
								$productIdArray[] = $resultSupplierProductLinking['productId'];
							}
						}
						
						$itemName = $itemDescription = $itemUnit = $itemContentQuantity = $itemContentUnit = '';
						$sql = "SELECT productId, productName, productDescription, productUnit, productContentQuantity, productContentUnit FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplierId = ".$subconId." AND supplierType = 2 ORDER BY productName, productDescription";
						$querySupplierProducts = $db->query($sql);
						if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
						{
							$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
							$productId = $resultSupplierProducts['productId'];
							$itemName = $resultSupplierProducts['productName'];
							$itemDescription = $resultSupplierProducts['productDescription'];
							$itemUnit = $resultSupplierProducts['productUnit'];
							$itemContentQuantity = $resultSupplierProducts['productContentQuantity'];
							$itemContentUnit = $resultSupplierProducts['productContentUnit'];
							
							$currency = '';
							$productPrice = 0;
							$sql = "SELECT priceLowerRange, priceUpperRange, currency, price FROM purchasing_price WHERE productId = ".$productId."";
							$queryPrice = $db->query($sql);
							if($queryPrice AND $queryPrice->num_rows > 0)
							{
								while($resultPrice = $queryPrice->fetch_assoc())
								{
									$priceLowerRange = $resultPrice['priceLowerRange'];
									$priceUpperRange = $resultPrice['priceUpperRange'];
									$currency = $resultPrice['currency'];
									$price = $resultPrice['price'];
									
									if($priceLowerRange != 0 AND $priceUpperRange != 0)
									{
										if($workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange)	$breakFlag = 1;
									}
									else
									{
										$breakFlag = 1;
									}
									
									if($breakFlag==1)
									{
										$productPrice = $price;
										break;
									}
								}
							}
							
							if($productPrice > 0)
							{
								//~ $workSchedId = $_POST['workSchedId'];
								$supplierId = $subconId;
								$supplierType = 2;
								$poCurrency = $currency;
								$itemPrice = $productPrice;
								$itemQuantity = $workingQuantity;
								
								$dateNeeded = '';
								$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) LIMIT 1";
								$queryWorkschedule = $db->query($sql);
								if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
								{
									$resultWorkschedule = $queryWorkschedule->fetch_assoc();
									$dateNeeded = $resultWorkschedule['targetFinish'];
								}
								
								$sql = "
									SELECT a.id FROM ppic_workschedule as a
									INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
									WHERE id = ".$workSchedId." LIMIT 1
								";
								$queryWorkSchedule = $db->query($sql);
								if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
								{
									$sql = "
										INSERT INTO purchasing_forpurchaseorder
											(	`lotNumber`, `processRemarks`, `productId`,		`supplierId`,		`supplierType`,		`poCurrency`,		`dateNeeded`,		`itemName`, 		`itemDescription`, 		`itemQuantity`, 	`itemUnit`,		`itemContentQuantity`, 		`itemContentUnit`,		`itemPrice`,		`itemFlag`)
										SELECT	`lotNumber`, `processRemarks`, '".$productId."','".$supplierId."',	'".$supplierType."','".$poCurrency."',	'".$dateNeeded."',	'".$itemName."',	'".$itemDescription."',	'".$itemQuantity."','".$itemUnit."','".$itemContentQuantity."',	'".$itemContentUnit."',	'".$itemPrice."',	'".$itemFlag."'
										FROM ppic_workschedule WHERE id = ".$workSchedId." LIMIT 1
									";
									$queryInsert = $db->query($sql);
								}
							}
						}
						
						
					}
				}
			}
			else if($identifier==4)
			{
				$supplyId = $partId;
				
				$productId = '';
				$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$supplyId." AND supplyType = ".$supplyType."";
				$querySupplierProductLinking = $db->query($sql);
				if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows == 1)
				{
					$resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc();
					$productId = $resultSupplierProductLinking['productId'];
				
					$supplierId = $supplierType = $itemName = $itemDescription = $itemUnit = $itemContentQuantity = $itemContentUnit = '';
					$sql = "SELECT supplierId, supplierType , productName, productDescription, productUnit, productContentQuantity, productContentUnit FROM purchasing_supplierproducts WHERE productId = ".$productId." LIMIT 1";
					$querySupplierProducts = $db->query($sql);
					if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
					{
						$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
						$supplierId = $resultSupplierProducts['supplierId'];
						$supplierType = $resultSupplierProducts['supplierType'];
						$itemName = $resultSupplierProducts['productName'];
						$itemDescription = $resultSupplierProducts['productDescription'];
						$itemUnit = $resultSupplierProducts['productUnit'];
						$itemContentQuantity = $resultSupplierProducts['productContentQuantity'];
						$itemContentUnit = $resultSupplierProducts['productContentUnit'];
						
						$currency = '';
						$productPrice = 0;
						$sql = "SELECT priceLowerRange, priceUpperRange, currency, price FROM purchasing_price WHERE productId = ".$productId."";
						$queryPrice = $db->query($sql);
						if($queryPrice AND $queryPrice->num_rows > 0)
						{
							while($resultPrice = $queryPrice->fetch_assoc())
							{
								$priceLowerRange = $resultPrice['priceLowerRange'];
								$priceUpperRange = $resultPrice['priceUpperRange'];
								$currency = $resultPrice['currency'];
								$price = $resultPrice['price'];
								
								if($priceLowerRange != 0 AND $priceUpperRange != 0)
								{
									if($workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange)	$breakFlag = 1;
								}
								else
								{
									$breakFlag = 1;
								}
								
								if($breakFlag==1)
								{
									$productPrice = $price;
									break;
								}
							}
						}
						
						if($productPrice > 0)
						{
							//~ $workSchedId = $_POST['workSchedId'];
							$poCurrency = $currency;
							$itemPrice = $productPrice;
							$itemQuantity = $workingQuantity;
							
							$dateNeeded = '';
							$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) LIMIT 1";
							$queryWorkschedule = $db->query($sql);
							if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
							{
								$resultWorkschedule = $queryWorkschedule->fetch_assoc();
								$dateNeeded = $resultWorkschedule['targetFinish'];
							}
							
							$sql = "
								SELECT a.id FROM ppic_workschedule as a
								INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
								WHERE id = ".$workSchedId." LIMIT 1
							";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
							{
								$sql = "
									INSERT INTO purchasing_forpurchaseorder
										(	`lotNumber`, `processRemarks`, `productId`,		`supplierId`,		`supplierType`,		`poCurrency`,		`dateNeeded`,		`itemName`, 		`itemDescription`, 		`itemQuantity`, 	`itemUnit`,		`itemContentQuantity`, 		`itemContentUnit`,		`itemPrice`,		`itemFlag`)
									SELECT	`lotNumber`, `processRemarks`, '".$productId."','".$supplierId."',	'".$supplierType."','".$poCurrency."',	'".$dateNeeded."',	'".$itemName."',	'".$itemDescription."',	'".$itemQuantity."','".$itemUnit."','".$itemContentQuantity."',	'".$itemContentUnit."',	'".$itemPrice."',	'".$itemFlag."'
									FROM ppic_workschedule WHERE id = ".$workSchedId." LIMIT 1
								";
								$queryInsert = $db->query($sql);
							}
						}
					}
				}
			}
		}
	}
	
	if($_GET['country']==1)
	{
		BE_KAPCO();
	}
	
	$sql = "SELECT id, lotNumber FROM view_workschedule WHERE processCode = 597 AND processSection = 5 AND lotNumber IN(SELECT lotNumber FROM ppic_lotlist WHERE identifier = 1) ORDER BY targetFinish, lotNumber";
	$sql = "SELECT id, lotNumber FROM view_workschedule WHERE processCode = 597 AND processSection = 5 AND lotNumber IN(SELECT lotNumber FROM ppic_lotlist WHERE identifier IN(1,4)) ORDER BY targetFinish, lotNumber";
	$queryWorkSched = $db->query($sql);
	if($queryWorkSched AND $queryWorkSched->num_rows > 0)
	{
		while($resultWorkSched = $queryWorkSched->fetch_assoc())
		{
			$id = $resultWorkSched['id'];
			$lotNumber = $resultWorkSched['lotNumber'];
			
			$poId = '';
			$sql = "SELECT poId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
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
			
			$sql = "SELECT lotId, remarks FROM system_lotOnHold WHERE lotNumber LIKE '".$lotNumber."' AND status = 0 LIMIT 1";
			$queryLotOnHold = $db->query($sql);
			if($queryLotOnHold->num_rows > 0)
			{
				continue;
			}
			
			$sql = "SELECT poNumber FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND itemStatus != 2 LIMIT 1";
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows > 0)
			{
				continue;
			}
			
			insertForPurchase($id);
		}
	}
	
	header('location:gerald_poPreparationList.php');
?>
