<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	if(isset($_GET['uniqueSupplier']) OR isset($_GET['notificationId']))
	{
		$notificationId = $_GET['notificationId'];
		$uniqueSupplier = $_GET['uniqueSupplier'];
		
		if($notificationId!='')
		{
			$sql = "SELECT notificationKey FROM system_notificationdetails WHERE notificationId = ".$notificationId." LIMIT 1";
			$queryNotificationIdDetails = $db->query($sql);
			if($queryNotificationIdDetails AND $queryNotificationIdDetails->num_rows > 0)
			{
				$resultNotificationIdDetails = $queryNotificationIdDetails->fetch_assoc();
				$uniqueSupplier = $resultNotificationIdDetails['notificationKey'];
			}
		}
		
		$supplierExplode = explode("-",$uniqueSupplier);
		$supplierId = $supplierExplode[0];
		$supplierType = $supplierExplode[1];
		$currency = $supplierExplode[2];
		
		$consumptionMonthArray = array();
		$i = 3;
		while($i-- > 0)
		{
			$consumptionMonthArray[] = date('Y-m',strtotime(date('Y-m-01').'-'.$i.' months'));
		}
		
		$supplierAlias = '';
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
		
		echo "
			<h2>Supplier : ".$supplierAlias."</h2>
			<table border='1'>
				<tr>
					<th></th>
					<th>".displayText('L246')."</th>
					<th>".displayText('L247')."</th>
					<th>".displayText('L267')."</th>
					<th>For PO</th>
					<th>Amount</th>
					<th>Purpose</th>
					<th>".displayText('L613')."</th>
					<th>".displayText('L2093')."</th>
					<th>Open PO</th>
					<th>".$consumptionMonthArray[0]."</th>
					<th>".$consumptionMonthArray[1]."</th>
					<th>".$consumptionMonthArray[2]."</th>
					<th></th>
					<th></th>
				</tr>
		";
		$totalAmount = 0;
		$lotNumberInput = '';
		$lotNumberArray = array();
		$sql = "SELECT lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 0 ORDER BY targetFinish";
		//~ if($_SESSION['idNumber']=='0346') $sql = "SELECT lotNumber, targetFinish, processRemarks FROM ppic_workschedule WHERE processCode = 461 AND processSection = 5 AND lotNumber LIKE '20-10-2227' ORDER BY targetFinish";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$targetFinish = $resultWorkSchedule['targetFinish'];
				$processRemarks = $resultWorkSchedule['processRemarks'];
				
				$productIds = $processRemarks;
				
				$productIdsCount = count(explode(",",$productIds));
				$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId IN(".$productIds.") AND itemStatus != 2";
				$queryPoContents = $db->query($sql);
				$poProductIdCount = ($queryPoContents AND $queryPoContents->num_rows) ? $queryPoContents->num_rows : 0;
				if($poProductIdCount >= $productIdsCount)
				{
					//~ if($_SESSION['idNumber']!='0346') continue;
					continue;
				}
				if($_SESSION['idNumber']=='0346') echo "<br>".$lotNumber;
				$supplyId = $workingQuantity = $identifier = $supplyType = '';
				$sql = "SELECT partId, workingQuantity, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					$resultLotList = $queryLotList->fetch_assoc();
					$supplyId = $resultLotList['partId'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$supplyType = $resultLotList['status'];
				}
				
				$sql = "SELECT productId, productName, productDescription, productUnit, productMOQ FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$currency.") AND supplierId = ".$supplierId." AND supplierType = 1 LIMIT 1";
				$querySupplierProducts = $db->query($sql);
				if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
				{
					$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
					$productId = $resultSupplierProducts['productId'];
					$productName = $resultSupplierProducts['productName'];
					$productDescription = $resultSupplierProducts['productDescription'];
					$productUnit = $resultSupplierProducts['productUnit'];
					$productMOQ = $resultSupplierProducts['productMOQ'];
					
					$inventoryQuantity = 0;
					$inventoryIdArray = array();
					$sql = "SELECT inventoryId, inventoryQuantity FROM warehouse_inventory WHERE supplyId = ".$supplyId." AND type = ".$supplyType."";
					$queryInventory = $db->query($sql);
					if($queryInventory AND $queryInventory->num_rows > 0)
					{
						while($resultInventory = $queryInventory->fetch_assoc())
						{
							$inventoryIdArray[] = "'".$resultInventory['inventoryId']."'";
							$inventoryQuantity += $resultInventory['inventoryQuantity'];
						}
					}
					
					$totalStock = $inventoryQuantity;
					
					if(count($inventoryIdArray) > 0)
					{
						if($supplyType==1)
						{
							$sql = "SELECT IFNULL(SUM(withdrawMaterialQuantity),0) AS totalWithdrawQty FROM warehouse_materialwithdrawal WHERE withdrawMaterialId IN(".implode(",",$inventoryIdArray).")";
							$queryMaterialwithdrawal = $db->query($sql);
							if($queryMaterialwithdrawal AND $queryMaterialwithdrawal->num_rows > 0)
							{
								$resultMaterialwithdrawal = $queryMaterialwithdrawal->fetch_assoc();
								$totalWithdrawQty = $resultMaterialwithdrawal['totalWithdrawQty'];
							}
							
							$totalStock -= $totalWithdrawQty;
						}
						else if($supplyType==3)
						{
							$sql = "SELECT IFNULL(SUM(suppliesWithdrawalQuantity),0) AS totalWithdrawQty FROM warehouse_supplieswithdrawal WHERE suppliesWithdrawalId IN(".implode(",",$inventoryIdArray).")";
							$querySupplieswithdrawal = $db->query($sql);
							if($querySupplieswithdrawal AND $querySupplieswithdrawal->num_rows > 0)
							{
								$resultSupplieswithdrawal = $querySupplieswithdrawal->fetch_assoc();
								$totalWithdrawQty = $resultSupplieswithdrawal['totalWithdrawQty'];
							}
							
							$totalStock -= $totalWithdrawQty;
						}
						else if($supplyType==4)
						{
							$sql = "SELECT IFNULL(SUM(accessoryWithdrawalQuantity),0) AS totalWithdraw FROM warehouse_accessorywithdrawal WHERE accessoryWithdrawalId IN(".implode(",",$inventoryIdArray).")";
							$queryAccessorieswithdrawal = $db->query($sql);
							if($queryAccessorieswithdrawal AND $queryAccessorieswithdrawal->num_rows > 0)
							{
								$resultAccessorieswithdrawal = $queryAccessorieswithdrawal->fetch_assoc();
								$totalWithdraw = $resultAccessorieswithdrawal['totalWithdraw'];
							}
							
							$totalStock -= $totalWithdraw;
						}
					}
					
					$sql = "SELECT inventoryId, inventoryQuantity FROM warehouse_inventoryhistory WHERE supplyId = ".$supplyId." AND type = ".$supplyType."";
					$queryInventory = $db->query($sql);
					if($queryInventory AND $queryInventory->num_rows > 0)
					{
						while($resultInventory = $queryInventory->fetch_assoc())
						{
							$inventoryIdArray[] = "'".$resultInventory['inventoryId']."'";
						}
					}
					
					if(count($inventoryIdArray) > 0)
					{
						if($supplyType==1)
						{
							$totalWithdrawQtyArray = array();
							$sql = "SELECT SUBSTRING_INDEX(withdrawMaterialDate,'-',2) AS withdrawMonth, IFNULL(SUM(withdrawMaterialQuantity),0) AS totalWithdrawQty FROM warehouse_materialwithdrawal WHERE withdrawMaterialId IN(".implode(",",$inventoryIdArray).") AND withdrawMaterialDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$queryMaterialwithdrawal = $db->query($sql);
							if($queryMaterialwithdrawal AND $queryMaterialwithdrawal->num_rows > 0)
							{
								while($resultMaterialwithdrawal = $queryMaterialwithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultMaterialwithdrawal['withdrawMonth']] = $resultMaterialwithdrawal['totalWithdrawQty'];
								}
							}
							
							$sql = "SELECT SUBSTRING_INDEX(oldWithdrawMaterialDate,'-',2) AS withdrawMonth, IFNULL(SUM(oldWithdrawMaterialQuantity),0) AS totalWithdrawQty FROM warehouse_materialwithdrawalhistory WHERE oldWithdrawMaterialId IN(".implode(",",$inventoryIdArray).") AND oldWithdrawMaterialDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$querySupplieswithdrawal = $db->query($sql);
							if($querySupplieswithdrawal AND $querySupplieswithdrawal->num_rows > 0)
							{
								while($resultSupplieswithdrawal = $querySupplieswithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultSupplieswithdrawal['withdrawMonth']] += $resultSupplieswithdrawal['totalWithdrawQty'];
								}
							}
						}						
						else if($supplyType==3)
						{
							$totalWithdrawQtyArray = array();
							$sql = "SELECT SUBSTRING_INDEX(suppliesWithdrawalDate,'-',2) AS withdrawMonth, IFNULL(SUM(suppliesWithdrawalQuantity),0) AS totalWithdrawQty FROM warehouse_supplieswithdrawal WHERE suppliesWithdrawalId IN(".implode(",",$inventoryIdArray).") AND suppliesWithdrawalDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$querySupplieswithdrawal = $db->query($sql);
							if($querySupplieswithdrawal AND $querySupplieswithdrawal->num_rows > 0)
							{
								while($resultSupplieswithdrawal = $querySupplieswithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultSupplieswithdrawal['withdrawMonth']] = $resultSupplieswithdrawal['totalWithdrawQty'];
								}
							}
							
							$sql = "SELECT SUBSTRING_INDEX(suppliesWithdrawalHistoryDate,'-',2) AS withdrawMonth, IFNULL(SUM(suppliesWithdrawalHistoryQuantity),0) AS totalWithdrawQty FROM warehouse_supplieswithdrawalhistory WHERE suppliesWithdrawalHistoryId IN(".implode(",",$inventoryIdArray).") AND suppliesWithdrawalHistoryDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$querySupplieswithdrawal = $db->query($sql);
							if($querySupplieswithdrawal AND $querySupplieswithdrawal->num_rows > 0)
							{
								while($resultSupplieswithdrawal = $querySupplieswithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultSupplieswithdrawal['withdrawMonth']] += $resultSupplieswithdrawal['totalWithdrawQty'];
								}
							}
						}
						else if($supplyType==4)
						{
							$totalWithdrawQtyArray = array();
							$sql = "SELECT SUBSTRING_INDEX(accessoryWithdrawalDate,'-',2) AS withdrawMonth, IFNULL(SUM(accessoryWithdrawalQuantity),0) AS totalWithdrawQty FROM warehouse_accessorywithdrawal WHERE accessoryWithdrawalId IN(".implode(",",$inventoryIdArray).") AND accessoryWithdrawalDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$queryAccessorieswithdrawal = $db->query($sql);
							if($queryAccessorieswithdrawal AND $queryAccessorieswithdrawal->num_rows > 0)
							{
								while($resultAccessorieswithdrawal = $queryAccessorieswithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultAccessorieswithdrawal['withdrawMonth']] = $resultAccessorieswithdrawal['totalWithdrawQty'];
								}
							}
							
							$sql = "SELECT SUBSTRING_INDEX(accessoryWithdrawalHistoryDate,'-',2) AS withdrawMonth, IFNULL(SUM(accessoryWithdrawalHistoryQuantity),0) AS totalWithdrawQty FROM warehouse_accessorywithdrawalhistory WHERE accessoryWithdrawalHistoryId IN(".implode(",",$inventoryIdArray).") AND accessoryWithdrawalHistoryDate >= '".date('Y-m-d',strtotime(date('Y-m-01').'-2 months'))."' GROUP BY withdrawMonth";
							$queryAccessorieswithdrawal = $db->query($sql);
							if($queryAccessorieswithdrawal AND $queryAccessorieswithdrawal->num_rows > 0)
							{
								while($resultAccessorieswithdrawal = $queryAccessorieswithdrawal->fetch_assoc())
								{
									$totalWithdrawQtyArray[$resultAccessorieswithdrawal['withdrawMonth']] += $resultAccessorieswithdrawal['totalWithdrawQty'];
								}
							}
						}
					}
					
					$consumptionPerMonthArray = array();
					foreach($consumptionMonthArray as $consumptionMonth)
					{
						$consumptionPerMonthArray[] = (isset($totalWithdrawQtyArray[$consumptionMonth])) ? $totalWithdrawQtyArray[$consumptionMonth] : 0;
					}
					
					$lotNumberInput .= "<input type='hidden' name='lotNumbers[]' value='".$lotNumber."' form='purchaseReviewForm'>";
					
					$removeButton = "";
					if($notificationId!='')
					{
						$removeButton = "<img src='/".v."/Common Data/Templates/images/close1.png' height='20' style='cursor:pointer;' onclick=\" location.href='gerald_purchaseOrderMakingSql.php?purchaseReviewDelete=1&lotNumber=".$lotNumber."' \">";
					}
					
					$actionButtons = '';
					if($identifier==4 AND $supplyType==1)
					{
						$dateNeeded = '';
						$sql = "SELECT dateNeeded FROM purchasing_prcontent WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryPrContent = $db->query($sql);
						if($queryPrContent AND $queryPrContent->num_rows > 0)
						{
							$resultPrContent = $queryPrContent->fetch_assoc();
							$dateNeeded = $resultPrContent['dateNeeded'];
						}
						
						$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryMaterialComputation = $db->query($sql);
						if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
						{
							$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
							$dateNeeded = $resultMaterialComputation['dateNeeded'];
						}
						
						$actionButtons .= "<a title='Details' onclick=\"TINY.box.show({url:'rhay_viewMaterialDetails.php?lotNumber=".$lotNumber."&dateNeeded=".$dateNeeded."', width:550, height:500, opacity:20,top:1,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/".v."/Common Data/Templates/images/details.png' height='20' ></a>";
					}
					
					$unitPrice = $priceCount = $breakFlag = 0;
					$sql = "SELECT priceLowerRange, priceUpperRange, price FROM purchasing_price WHERE productId = ".$productId." AND currency = ".$currency."";
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
								//~ echo " ($workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange)";
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
										$partId = $supplyId;
										
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
					
					$purpose = '';
					$sql = "SELECT purpose FROM purchasing_prcontent WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryPrContent = $db->query($sql);
					if($queryPrContent AND $queryPrContent->num_rows > 0)
					{
						$resultPrContent = $queryPrContent->fetch_assoc();
						$purpose = $resultPrContent['purpose'];
					}
					
					$amount = $unitPrice * $workingQuantity;
					
					$totalAmount += $amount;
					
					echo "
						<tr>
							<td>".++$count."</td>
							<td>".$productName."</td>
							<td>".$productDescription."</td>
							<td align='right'>".$unitPrice."</td>
							<td align='right'>".$workingQuantity."</td>
							<td align='right'>".$amount."</td>
							<td>".$purpose."</td>
							<td>".$productMOQ."</td>
							<td>".$totalStock."</td>
							<td></td>
							<td>".$consumptionPerMonthArray[0]."</td>
							<td>".$consumptionPerMonthArray[1]."</td>
							<td>".$consumptionPerMonthArray[2]."</td>
							<td>".$actionButtons."</td>
							<td>".$removeButton."</td>
						</tr>
					";
				}
			}
		}
		echo "
			<tr>
				<th colspan='5'></th>
				<th>".$totalAmount."</th>
				<th colspan='9'></th>
			</tr>
		";
		if($notificationId!='')
		{
			echo "<form action='gerald_purchaseOrderMakingSql.php?notificationId=".$notificationId."' method='post' id='purchaseReviewForm'></form>";
			echo $lotNumberInput;
			
			echo "
				<tr>
					<th colspan='15'><input type='submit' name='purchaseReviewConfirmation' form='purchaseReviewForm' value='OKAY'></th>
				</tr>
			";
		}
		else
		{
			$notificationIdArray = array();
			$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey LIKE '".$uniqueSupplier."'";
			$queryNotificationIdDetails = $db->query($sql);
			if($queryNotificationIdDetails AND $queryNotificationIdDetails->num_rows > 0)
			{
				while($resultNotificationIdDetails = $queryNotificationIdDetails->fetch_assoc())
				{
					$notificationIdArray[] = $resultNotificationIdDetails['notificationId'];
				}
			}
			
			$reviewButtonFlag = 1;
			$sql = "SELECT notificationId FROM system_notification WHERE notificationId IN(".implode(",",$notificationIdArray).") AND notificationStatus = 0 LIMIT 1";
			$queryNotification = $db->query($sql);
			if($queryNotification AND $queryNotification->num_rows > 0)
			{
				$reviewButtonFlag = 0;
			}
			
			if($reviewButtonFlag==1)
			{
				echo "
					<tr>
						<th colspan='15'><button onclick=\" location.href='gerald_purchaseOrderMakingSql.php?uniqueSupplier=".$uniqueSupplier."'; \">For Review</button></th>
					</tr>
				";
			}
		}
		
		echo "</table>";
		goto hell;
	}
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L1313', 'utf8', 0, 1);?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v;?>//Common Data/Templates/api.css">
	<script src="/<?php echo v;?>//Common Data/Templates/api.js"></script>
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
	createHeader('L1313','','gerald_purchaseOrderMakingSummary.php');
?>
	<form action='gerald_purchaseOrderListExport.php' method='post' id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
<!--
			<button class='api-btn api-btn-back' onclick="location.href='gerald_purchaseOrderMakingSummary.php';" data-api-title='<?php echo displayText('L1072');?>' <?php echo toolTip('L1072');?>></button>
-->
		</div>
		
		<div class="api-top api-col api-title" style='width:40%;'>
			<h2><?php //echo displayText('L1313');?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:30%'>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436', 'utf8', 0, 1);?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			
			<!------------------ End Filters ------------------>
			
			<!-------------------- Contents -------------------->
			
			
			<div style='height: 89%;'><!-- Adjust height if browser had a vertical scroll -->
				<table id='mainTableId' class="api-table-fixedheader api-table-design2" data-counter='-1' data-detail-type='left'>
					<thead>
						<tr>
							<th style='width:vw;'><?php echo displayText('L843');?></th>
							<th style='width:vw;'><?php echo displayText('L367');?></th>
							<th style='width:vw;'><?php echo displayText('L743');?></th>
							<th style='width:vw;'><?php echo displayText('L1049');?></th>
							<th style='width:vw;'></th>
						</tr>
					</thead>
					<tbody>
						<?php
							$lotNumberArray = $totalPriceArray = array();
							$count = 0;
							$sql = "SELECT lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 0 ORDER BY targetFinish";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
								{
									$lotNumber = $resultWorkSchedule['lotNumber'];
									$targetFinish = $resultWorkSchedule['targetFinish'];
									$processRemarks = $resultWorkSchedule['processRemarks'];
									
									$productIds = $processRemarks;
									
									$workingQuantity = 0;
									$partId = $identifier = '';
									$sql = "SELECT partId, workingQuantity, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
									$queryLotList = $db->query($sql);
									if($queryLotList AND $queryLotList->num_rows > 0)
									{
										$resultLotList = $queryLotList->fetch_assoc();
										$partId = $resultLotList['partId'];
										$workingQuantity = $resultLotList['workingQuantity'];
										$identifier = $resultLotList['identifier'];
									}
									
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
									
									$sql = "SELECT productId, supplierId, supplierType FROM purchasing_supplierproducts WHERE productId IN(".$productIds.")";
									$querySupplierProducts = $db->query($sql);
									if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
									{
										while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
										{
											$productId = $resultSupplierProducts['productId'];
											$supplierId = $resultSupplierProducts['supplierId'];
											$supplierType = $resultSupplierProducts['supplierType'];
											
											$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId = ".$productId." AND itemStatus != 2";
											$queryPoContents = $db->query($sql);
											if($queryPoContents AND $queryPoContents->num_rows > 0)
											{
												continue;
											}
											
											$surfaceArea = $packingCost = 0;
											if($supplierType==2)
											{
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
														$sql = '';
														if($supType==2)
														{
															$sql = "SELECT surfaceArea FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$supplyId." LIMIT 1";
														}
														else if($supType==5)
														{
															$sql = "SELECT surfaceArea FROM cadcam_subconlist WHERE a = ".$supplyId." LIMIT 1";
														}
														if($sql!='')
														{
															$querySubconList = $db->query($sql);
															if($querySubconList AND $querySubconList->num_rows > 0)
															{
																$resultSubconList = $querySubconList->fetch_assoc();
																$surfaceArea = $resultSubconList['surfaceArea'];
															}
														}
														
														//~ $packingCost = (in_array($supplyId,array(270,272))) ? ($totalSurfaceClear * 2) * 0.0031 : 0 ;
														$packingCost = ($supplyId==270) ? ($surfaceArea * 0.0031) : 0 ;
													}
													else if($identifier==4)
													{
														$packingCost = 0.61;
													}
												}
											}
											
											$currency = '';
											$price = $totalPrice = 0;
											$breakFlag = 0;
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
														$itemPrice = $price;
														if($supplierType==2)
														{
															if($surfaceArea > 0)
															{
																$itemPrice = ($price * $surfaceArea)+$packingCost;
															}
														}
														
														$totalPrice = round(round($itemPrice,4) * $workingQuantity,2);
														break;
													}
												}
											}
											
											$uniqueSupplier = $supplierId."-".$supplierType."-".$currency;
											
											if(!isset($lotNumberArray[$uniqueSupplier]))	$lotNumberArray[$uniqueSupplier] = array();
											
											if(!in_array($lotNumber,$lotNumberArray[$uniqueSupplier]))
											{
												$lotNumberArray[$uniqueSupplier][] = $lotNumber;
											}
											
											$totalPriceArray[$uniqueSupplier] += $totalPrice;
										}
									}
								}
							}
							
							if(count($lotNumberArray) > 0)
							{
								foreach($lotNumberArray as $uniqueSupplier => $lotNumbers)
								{
									$supplierExplode = explode("-",$uniqueSupplier);
									$supplierId = $supplierExplode[0];
									$supplierType = $supplierExplode[1];
									$currency = $supplierExplode[2];
									
									$totalAmount = $totalPriceArray[$uniqueSupplier];
									//~ print_r($lotNumbers);
									$sign = '';
									if($currency == 1)	$sign = '$';
									else if($currency == 2)	$sign = 'Php';
									else if($currency == 3)	$sign = '??';
									
									$supplierAlias = '';
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
									
									echo "
										<tr class='internalTrClass'>
											<td>".++$count."</td>
											<td>".$supplierAlias."</td>
											<td align='right'>".$sign." ".number_format($totalAmount,2)."</td>
											<td align='right'>".count($lotNumbers)."</td>
											<td><img class='inputForm' src='/".v."/Common Data/Templates/images/view1.png' height='20' name='".$uniqueSupplier."' style='cursor:pointer;'></td>
										</tr>
									";
								}
							}
						?>						
					</tbody>
					<tfoot>
						<tr>
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
				<iframe name='boxName' style='width:100%;height:80vh;border:none;'></iframe>
			</div>
		</div>
	</div>
</body>
<script src="/<?php echo v;?>//Common Data/Templates/jquery.js"></script>
<script src="/<?php echo v;?>//Common Data/Templates/api.jquery.js"></script>
<script>
	$(function(){
		$("#mainTableId").apiFixedTableHeader();
		
		$('img.inputForm').click(function(){
			$("iframe[name=boxName]").attr('src','gerald_purchaseOrderReview.php?uniqueSupplier='+$(this).attr('name'));
			document.getElementById('modal01').style.display='block';
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
<?php hell:?>
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
