<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");

	$obj = new PMSDatabase;
	$tpl = new PMSTemplates;

	$requestData= $_REQUEST;
	$sqlData = isset($requestData['sqlData']) ? $requestData['sqlData'] : '';
	$totalRecords = isset($requestData['totalRecords']) ? $requestData['totalRecords'] : '';
	
	$exportFlag = (isset($_POST['exportFlag'])) ? $_POST['exportFlag'] : '';
	
	$totalData = $totalRecords;
	$totalFiltered = $totalRecords;
	
	if($exportFlag!='')
	{
		//~ $filename = "CPAR LIST (".date('ymdHis').").xls";
		//~ header('Content-type: application/ms-excel');
		//~ header('Content-Disposition: attachment; filename='.$filename);

		$dateNow = date('Y-m-d');
		$filename = "PO Preparation (".$dateNow.").xls";
		header('Content-type: application/ms-excel');
		header('Content-Disposition: attachment; filename='.$filename);
		
		?>
		<table class='table table-bordered table-condensed table-striped' id="mainTableId" border = 1>
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
				<!-- <th style='vertical-align:middle;' class='w3-center'><?php echo displayText('L1120');?></th> -->
			</thead>
			<tbody class='tbody'>
		<?php
	}
	
	$data = array();
	$sql = $sqlData;
	if($exportFlag=='') $sql.=" LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
	$counter = $requestData['start'];
	$query = $db->query($sql);
	if($query AND $query->num_rows > 0)
	{
		while($result = $query->fetch_assoc())
		{
			$workSchedId = $result['id'];
			$lotNumber = $result['lotNumber'];
			$processRemarks = $result['processRemarks'];
			$targetFinish = $result['targetFinish'];
			
			$partId = $workingQuantity = $identifier = $supplyType = $dateGenerated = '';
			$sql = "SELECT partId, workingQuantity, identifier, status, dateGenerated FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$partId = $resultLotList['partId'];
				$workingQuantity = $resultLotList['workingQuantity'];
				$identifier = $resultLotList['identifier'];
				$supplyType = $resultLotList['status'];
				$dateGenerated = $resultLotList['dateGenerated'];
			}
			
			$holdFlag = 0;
			$sql = "SELECT lotId, remarks FROM system_lotOnHold WHERE lotNumber LIKE '".$lotNumber."' AND status = 0 LIMIT 1";
			$queryLotOnHold = $db->query($sql);
			if($queryLotOnHold->num_rows > 0)
			{
				$holdFlag = 1;
			}
			
			$dateNeeded = '';
			$sql = "SELECT dateNeeded FROM purchasing_prcontent WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryPrContent = $db->query($sql);
			if($queryPrContent AND $queryPrContent->num_rows > 0)
			{
				$resultPrContent = $queryPrContent->fetch_assoc();
				$dateNeeded = $resultPrContent['dateNeeded'];
			}
			
			$editTableNumber = "";
			$description = '';
			$supplyTypeName = '';
			
			$productId = '';
			
			$noSubconLinkFlag = 0;
			$noPurchasingDataFlag = 0;
			
			$poContentDataThreeFilter = "";
			
			if($identifier==1)
			{
				$partNumber = $revisionId = '';
				$sql = "SELECT partNumber, revisionId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
				$queryParts = $db->query($sql);
				if($queryParts AND $queryParts->num_rows > 0)
				{
					$resultParts = $queryParts->fetch_assoc();
					$partNumber = $resultParts['partNumber'];
					$revisionId = $resultParts['revisionId'];
				}
				
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
				
				$aArray = $treatmentIdArray = array();
				$sql = "SELECT a, processCode FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$treatmentId."";
				$querySubconList = $db->query($sql);
				if($querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_array())
					{
						$aArray[] = $resultSubconList['a'];
						$treatmentIdArray[] = $resultSubconList['processCode'];
					}
				}
				
				$aArray2 = array();
				$noSubconLinkFlag = 1;
				$sql = "SELECT a FROM engineering_subconprocessor WHERE a IN(".implode(",",$aArray).")";
				$querySubconProcessor = $db->query($sql);
				if($querySubconProcessor AND $querySubconProcessor->num_rows > 0)
				{
					$noSubconLinkFlag = 0;
					
					while($resultSubconProcessor = $querySubconProcessor->fetch_assoc())
					{
						$aArray2[] = $resultSubconProcessor['a'];
					}
					$roseErrorNodata="";
					$productIdArray = array();
					// $sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId IN(".implode(",",$aArray2).") AND supplyType = 5 LIMIT 1";// commented limit 1 kasi naka while ka 2021-08-03 rose
					$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId IN(".implode(",",$aArray2).") AND supplyType = 5";
					// $roseErrorNodata=$sql;
					$querySupplierProductLinking = $db->query($sql);
					if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
					{
						while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
						{
							$productIdArray[] = $resultSupplierProductLinking['productId'];
						}
					}
					
					$sql = "SELECT productId FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplierType = 2 ORDER BY productName, productDescription";
					//$roseErrorNodata=$sql;
					$querySupplierProducts = $db->query($sql);
					if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
					{
						while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
						{
							$productId = $resultSupplierProducts['productId'];
							
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
									
									$breakFlag = 0;
									
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
						}
					}
					else
					{
						$noPurchasingDataFlag = 1;
					}
				}
				
				$dateNeededRose ="";
				$processOrder = '';
				// $sql = "SELECT processOrder, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) AND processRemarks LIKE '".$treatmentName."' LIMIT 1";
				$sql = "SELECT processOrder, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) AND (processRemarks LIKE '".$treatmentName."' OR processRemarks LIKE '%".$treatmentName."%') LIMIT 1";
				// if($_GET['country']==2) $sql = "SELECT processOrder, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$treatmentId." LIMIT 1";
				$queryWorkschedule = $db->query($sql);
				if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
				{
					$resultWorkschedule = $queryWorkschedule->fetch_assoc();
					$processOrder = $resultWorkschedule['processOrder'];
					$dateNeeded = $resultWorkschedule['targetFinish'];
					$dateNeededRose = $resultWorkschedule['targetFinish'];
				}
								
				$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) AND processOrder >= ".$processOrder." LIMIT 1";// commented by rose 2021-08-03 no value processOrder //revived code gerald 2021-09-20
				//~ $sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) LIMIT 1";
				//$dateNeededRose =$sql;
				$queryWorkschedule = $db->query($sql);
				if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
				{
					$resultWorkschedule = $queryWorkschedule->fetch_assoc();
					$dateNeeded = $resultWorkschedule['targetFinish'];
					$dateNeededRose = $resultWorkschedule['targetFinish'];
				}
				
				$description = $partNumber." rev ".$revisionId."<br>".$treatmentName;
				
				$supplyTypeName = 'Subcon';
				
				if($treatmentName!='')
				{
					$poContentDataThreeFilter = " AND dataThree LIKE '".$treatmentName."'";
				}
			}
			else if($identifier==4)
			{
				$supplyId = $partId;
				
				$description = '';
				if($supplyType==3)
				{
					$sql = "SELECT CONCAT(itemName,'<br>',itemDescription) as supply FROM purchasing_items WHERE itemId = ".$supplyId." LIMIT 1";
					$queryItems = $db->query($sql);
					if($queryItems AND $queryItems->num_rows > 0)
					{
						$resultItems = $queryItems->fetch_assoc();
						$description = $resultItems['supply'];
					}
					$supplyTypeName = 'Item';
				}
				else if($supplyType==4)
				{
					$sql = "SELECT CONCAT(accessoryNumber,'<br>',accessoryName,'<br>',accessoryDescription) as supply FROM cadcam_accessories WHERE accessoryId = ".$supplyId." LIMIT 1";
					$queryItems = $db->query($sql);
					if($queryItems AND $queryItems->num_rows > 0)
					{
						$resultItems = $queryItems->fetch_assoc();
						$description = $resultItems['supply'];
					}
					$supplyTypeName = 'Accessory';
				}
				else if($supplyType==1)
				{
					$materialId = '';
					$sql = "SELECT materialId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$supplyId." LIMIT 1";
					$queryMaterialTreatment = $db->query($sql);
					if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
					{
						$resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc();
						$materialId = $resultMaterialTreatment['materialId'];
					}
					
					$sql = "SELECT materialSpecId, length, width FROM purchasing_material WHERE materialId = ".$materialId." LIMIT 1";
					//~ $sql = "SELECT materialSpecId, length, width FROM purchasing_material WHERE materialId = ".$supplyId." LIMIT 1";
					$queryMaterial = $db->query($sql);
					if($queryMaterial AND $queryMaterial->num_rows > 0)
					{
						$resultMaterial = $queryMaterial->fetch_assoc();
						$materialSpecId = $resultMaterial['materialSpecId'];
						$length = $resultMaterial['length'];
						$width = $resultMaterial['width'];
						
						$materialTypeId = $thickness = '';
						$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
						$queryMaterialSpecs = $db->query($sql);
						if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
						{
							$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
							$materialTypeId = $resultMaterialSpecs['materialTypeId'];
							$thickness = $resultMaterialSpecs['metalThickness'];
						}
						
						$materialType = '';
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType AND $queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_assoc();
							$materialType = $resultMaterialType['materialType'];
						}
						
						$pvc = '';
						$sql = "SELECT pvc FROM system_confirmedmaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryConfirmedMaterialPo = $db->query($sql);
						if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
						{
							$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
							$pvc = ($resultConfirmedMaterialPo['pvc']==1) ? 'w/PVC' : '';
						}
						
						$description = $materialType."<br>".$thickness." X ".$length." X ".$width." ".$pvc;
					}
					$supplyTypeName = 'Material';
					
					$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryMaterialComputation = $db->query($sql);
					if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
					{
						$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
						$dateNeeded = $resultMaterialComputation['dateNeeded'];
					}
				}
				
				$editTableNumber = "editTableNumber";
			}
			
			if($dateNeededRose!="" and $noSubconLinkFlag==0){$button = "<img class='linkingClass' src='/".v."/Common Data/Templates/images/view1.png' height='20' onclick=\" linkProductModal(".$workSchedId.",0); \" style='cursor:pointer;'>click";}
			else{$button = "<img class='linkingClass' src='/".v."/Common Data/Templates/images/view1.png' height='20' onclick=\" linkProductModal(".$workSchedId.",0); \" style='cursor:pointer;'>";}
			
			$supplierAlias = $productName = $productDescription = '';
			$productPrice = 0;
			$priceInput = '';
			$dateNeededInput = $dateNeeded;
			$quantityInput = $workingQuantity;
			
			$productId = $supplierId = $supplierType = $poCurrency = $itemRemarks = $remarksInput = '';
			$sql = "SELECT listId, productId, supplierId, supplierType, poCurrency, itemRemarks, `dateNeeded`, `itemName`, `itemDescription`, `itemQuantity`, `itemUnit`, `itemContentQuantity`, `itemContentUnit`, `itemPrice` FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."' AND processRemarks = '".$processRemarks."' LIMIT 1";
			$queryForPurchaseOrder = $db->query($sql);
			if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
			{
				$resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc();
				$listId = $resultForPurchaseOrder['listId'];
				$productId = $resultForPurchaseOrder['productId'];
				$supplierId = $resultForPurchaseOrder['supplierId'];
				$supplierType = $resultForPurchaseOrder['supplierType'];
				$poCurrency = $resultForPurchaseOrder['poCurrency'];
				$itemRemarks = $resultForPurchaseOrder['itemRemarks'];
				$receivingDate = $resultForPurchaseOrder['dateNeeded'];
				$productName = $resultForPurchaseOrder['itemName'];
				$productDescription = $resultForPurchaseOrder['itemDescription'];
				$itemQuantity = $resultForPurchaseOrder['itemQuantity'];
				$itemUnit = $resultForPurchaseOrder['itemUnit'];
				$productPrice = $resultForPurchaseOrder['itemPrice'];
				
				if($supplierType==1)
				{
					$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
				}
				else if($supplierType==2)
				{
					$sql = "SELECT subconAlias FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
				}
				if($sql!='')
				{
					$querySupplier = $db->query($sql);
					if($querySupplier AND $querySupplier->num_rows > 0)
					{
						$resultSupplier = $querySupplier->fetch_row();
						$supplierAlias = $resultSupplier[0];
					}
				}
				
				//~ if($_SESSION['idNumber']=='0346')
				//~ {
					if($identifier==4)
					{
						$quantityInput = "<input type='number' value='".$itemQuantity."' min='0.0001' step='any' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemQuantity'>";
					}
				
					$dateNeededInput = "";
					if(strtotime($receivingDate) < strtotime(date('Y-m-d')))
					{						
						if($receivingDate=="0000-00-00" and $supplierType==2 and $dateNeededRose!="")
						{
							$receivingDate=$dateNeededRose;
						}
						// $dateNeededInput = "<span style='color:red;font-size:10px;'>".$dateNeededRose."~Original Date:".$receivingDate."</span><br>";						
						if(strtotime($receivingDate) < strtotime(date('Y-m-d')))
						{
							$dateNeededInput = "<span style='color:red;font-size:10px;'>Original Date:".$receivingDate."</span><br>";
							$receivingDate = date('Y-m-d');
						}
						
					}
					$dateNeededInput .= "<input type='date' value='".$receivingDate."' min='".date('Y-m-d')."' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='dateNeeded'>";
					$priceInput = "<input type='number' value='".$productPrice."' min='0.0001' step='any' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemPrice'>";
					$remarksInput = "<input type='input' value='".$itemRemarks."' onchange=\" updateData(this) \" data-list-id='".$listId."' data-column='itemRemarks'>";
				//~ }
				//~ else
				//~ {
					//~ $priceInput = $productPrice;
					//~ $remarksInput = "<input type='input' value='".$itemRemarks."' onchange=\" updateRemarks(this) \" data-list-id='".$listId."'>";
				//~ }
				
				$button = "<img class='linkingClass' src='/".v."/Common Data/Templates/images/close1.png' height='20' onclick=\" linkProductModal(".$workSchedId.",1); \" style='cursor:pointer;'>";
			}
			
			
			$productMOQ = '';
			$sql = "SELECT productName, productDescription, productMOQ FROM `purchasing_supplierproducts` WHERE productId = ".$productId." LIMIT 1";
			$querySupplierProducts = $db->query($sql);
			if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
			{
				$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
				$productMOQ = $resultSupplierProducts['productMOQ'];
			}
			
			/*
			$productPrice = 0;
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
					
					if($breakFlag==1)
					{
						$productPrice = $price;
						break;
					}
				}
			}*/
			
			$totalPrice = $productPrice * $workingQuantity;
			
			$holdSpan = '';
			if($holdFlag==1)
			{
				$holdSpan = "<br><span style='color:red;font-size:8px;'>(Hold Lot Number)</span>";
				$button = "";
			}
			
			if($noSubconLinkFlag==1)
			{
				$supplierAlias = "<span style='color:red;'>No Assigned Subcon</span>";
				$button = "";
			}
			
			$poNumberSpan = "";
			$sql = "SELECT poNumber FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND itemStatus != 2 ".$poContentDataThreeFilter." LIMIT 1";
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows > 0)
			{
				$resultPoContents = $queryPoContents->fetch_assoc();
				$poNumber = $resultPoContents['poNumber'];
				
				$poNumberSpan = "<br><span style='color:red;'>(Ongoing PO : ".$poNumber.")</span>";
				$button = "";
			}			
			
			if($noPurchasingDataFlag==1)
			{
				$button = "No Data".$roseErrorNodata;
			}
			
			$actionButtons = $removeButton = "";

			if($_GET['country']==1)
			{
				if($supplyTypeName == 'Material')
				{
					if($poNumber=='')
					{
						if($dateNeeded != "")
						{
							$actionButtons .= "<a title='Details' onclick=\"TINY.box.show({url:'rhay_viewMaterialDetails.php?lotNumber=".$lotNumber."&dateNeeded=".$dateNeeded."', width:550, height:500, opacity:20,top:1,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/".v."/Common Data/Templates/images/details.png' height='20' ></a>";
							if($_SESSION['userType'] == 0)
							{
								/*$actionButtons .= "<a href = '#'title='Remove' onclick=\"TINY.box.show({url:'rhay_deleteConfirmedMaterialPo.php?lote=".$lotNumber."&dateNeeded=".$dateNeeded."', width:300, height:200, opacity:20,top:100,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/V3/Common Data/Templates/images/cross1.png' height='20' ></a>"; */
								$actionRemove = "rhay_deleteConfirmedMaterialPo.php?lote=".$lotNumber."&dateNeeded=".$dateNeeded.""; 

								$tpl->setDataValue("L678"); // Remove
							    $tpl->setAttribute("type","button");
							    $tpl->setAttribute("onclick", "removeData('".$actionRemove."')");
							    $removeButton = $tpl->createButton(1);
							}
						} 
						else
						{
							$actionButtons .= "<a title='Details' onclick=\"TINY.box.show({url:'rhay_viewMaterialDetails.php?lotNumber=".$lotNumber."&dateNeeded=".$dateNeeded."', width:300, height:200, opacity:20,top:100,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/".v."/Common Data/Templates/images/details.png' height='20' ></a>";
							if($_SESSION['userType'] == 0)
							{
								$actionRemove = "rhay_deleteConfirmedMaterialPo.php?lote=".$lotNumber."&dateNeeded=".$dateNeeded.""; 
								$tpl->setDataValue("L678"); // Remove
							    $tpl->setAttribute("type","button");
							    $tpl->setAttribute("onclick", "removeData('".$actionRemove."')");
							    $removeButton = $tpl->createButton(1);
							}
						}
					}
				}
			}			
			
			$description = str_replace("\n","<br>",$description);
			$productDescription = str_replace("\n","<br>",$productDescription);
			
			if($exportFlag=='')
			{
				$nestedData=array(); 
				$nestedData[] = $lotNumber.$holdSpan;
				$nestedData[] = $description;
				$nestedData[] = $quantityInput;
				$nestedData[] = $productMOQ;
				$nestedData[] = $supplyTypeName;
				$nestedData[] = $dateNeededInput;
				$nestedData[] = $targetFinish;
				$nestedData[] = $supplierAlias.$poNumberSpan;
				$nestedData[] = $productName;
				$nestedData[] = $productDescription;
				$nestedData[] = $priceInput;
				$nestedData[] = $totalPrice;
				$nestedData[] = $remarksInput;
				$nestedData[] = $button.$actionButtons.$removeButton;
			}
			else
			{
				echo "
					<tr>
						<td>".$lotNumber.$holdSpan."</td>
						<td>".$description."</td>
						<td>".$workingQuantity."</td>
						<td>".$productMOQ."</td>
						<td>".$supplyTypeName."</td>
						<td>".$dateNeeded."</td>
						<td>".$targetFinish."</td>
						<td>".$supplierAlias.$poNumberSpan."</td>
						<td>".$productName."</td>
						<td>".$productDescription."</td>
						<td>".$productPrice."</td>
						<td>".$totalPrice."</td>
						<td>".$itemRemarks."</td>
					</tr>
				";
			}
			
			$data[] = $nestedData;
		}
	}
	
	if($exportFlag=='')
	{	
		$json_data = array(
					"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
					"recordsTotal"    => intval( $totalData ),  // total number of records
					"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
					"data"            => $data   // total data array
					);

		echo json_encode($json_data);  // send data as json format
	}
	else
	{
		echo "</tbody></table>";
	}
?>
