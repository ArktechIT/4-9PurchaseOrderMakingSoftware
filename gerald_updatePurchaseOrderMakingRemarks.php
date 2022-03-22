<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	$groupNo = $_POST['groupNo'];
	$sqlFilter = $_POST['sqlFilter'];
	$queryLimit = 50;
	$queryPosition = ($groupNo * $queryLimit);
	
	$count = $queryPosition;
	$sql = "SELECT id, lotNumber, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 ORDER BY targetFinish";
	$sqlMain = $sql;
	$query = $db->query($sql);
	if($query->num_rows > 0)
	{
		//~ $tableContent = "<tr><td colspan='14'>".$sqlMain."</td></tr>";
		while($result = $query->fetch_array())
		{
			$workSchedId = $result['id'];
			$lotNumber = $result['lotNumber'];
			$productId = $result['processRemarks'];
			
			$poId = $partId = $identifier = $supplyType = '';
			$sql = "SELECT poId, partId, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$poId = $resultLotList['poId'];
				$partId = $resultLotList['partId'];
				$identifier = $resultLotList['identifier'];
				$supplyType = $resultLotList['status'];
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
				$sql = "UPDATE ppic_workschedule SET processRemarks = '' WHERE id = ".$id." LIMIT 1";
				$queryUpdate = $db->query($sql);
				
				continue;
			}
			
			$treatmentIdArray = $treatmentNameArray = array();
			if($identifier==1)
			{
				$aArray = array();
				$sql = "SELECT a, processCode FROM cadcam_subconlist WHERE partId = ".$partId."";
				$querySubconList = $db->query($sql);
				if($querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_array())
					{
						$aArray[] = $resultSubconList['a'];
						$treatmentIdArray[] = $resultSubconList['processCode'];
					}
				}
				
				$subconIdArray = array();
				$sql = "SELECT subconId FROM engineering_subconprocessor WHERE a IN(".implode(",",$aArray).")";
				$querySubconProcessor = $db->query($sql);
				if($querySubconProcessor AND $querySubconProcessor->num_rows > 0)
				{
					while($resultSubconProcessor = $querySubconProcessor->fetch_assoc())
					{
						$subconIdArray[] = $resultSubconProcessor['subconId'];
					}
					if(count($aArray)==count($subconIdArray))
					{
						$productIdArray = array();
						$sql = "SELECT productId, supplyType FROM purchasing_supplierproductlinking WHERE (supplyId IN(".implode(",",$treatmentIdArray).") AND supplyType = 2) OR (supplyId IN(".implode(",",$aArray).") AND supplyType = 5)";
						$querySupplierProductLinking = $db->query($sql);
						if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
						{
							while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
							{
								$okayFlag = 1;
								$sql = "SELECT listId FROM `system_rfq` WHERE partId = ".$partId." AND `customerId` IN(28,45) AND `dateInserted` >= '2018-02-01 00:00:00'";
								$queryRFQ = $db->query($sql);
								if($queryRFQ AND $queryRFQ->num_rows > 0 AND $resultSupplierProductLinking['supplyType']==2)
								{
									$okayFlag = 0;
								}
								
								if($okayFlag==1)
								{
									$productIdArray[] = $resultSupplierProductLinking['productId'];
								}
							}
							
							$sql = "SELECT productId FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplierId IN(".implode(",",$subconIdArray).") AND supplierType = 2";
							$querySupplierProducts = $db->query($sql);
							if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
							{
								$productIdArray = array();
								while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
								{
									$productIdArray[] = $resultSupplierProducts['productId'];
								}
								$sql = "SELECT priceId FROM purchasing_price WHERE productId IN(".implode(",",$productIdArray).") AND price != 0 LIMIT 1";
								$queryPrice = $db->query($sql);
								if($queryPrice AND $queryPrice->num_rows > 0)
								{
									if($productId=='')
									{
										$productIdsArray = array();
										$sql = "SELECT a, processCode FROM cadcam_subconlist WHERE partId = ".$partId."";
										$querySubconList = $db->query($sql);
										if($querySubconList AND $querySubconList->num_rows > 0)
										{
											while($resultSubconList = $querySubconList->fetch_array())
											{
												$a = $resultSubconList['a'];
												$treatmentId = $resultSubconList['processCode'];
												
												$sql = "SELECT DISTINCT subconId FROM engineering_subconprocessor WHERE a = ".$a."";
												$querySubconProcessor = $db->query($sql);
												if($querySubconProcessor AND $querySubconProcessor->num_rows == 1)
												{
													$resultSubconProcessor = $querySubconProcessor->fetch_assoc();
													$subconId = $resultSubconProcessor['subconId'];
													
													$productIdArray = array();
													$sql = "SELECT productId FROM purchasing_supplierproducts WHERE supplierId = ".$subconId." AND supplierType = 2";
													$querySupplierProducts = $db->query($sql);
													if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
													{
														while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
														{
															$productIdArray[] = $resultSupplierProducts['productId'];
														}
													}
													
													$productAyDi = '';
													$sql = "SELECT DISTINCT productId FROM purchasing_supplierproductlinking WHERE productId IN(".implode(",",$productIdArray).") AND ((supplyId = ".$treatmentId." AND supplyType = 2) OR (supplyId = ".$a." AND supplyType = 5))";
													$querySupplierProductLinking = $db->query($sql);
													if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows == 1)
													{
														$resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc();
														$productAyDi = $resultSupplierProductLinking['productId'];
														
														$price = 0;
														$sql = "SELECT price FROM purchasing_price WHERE productId = ".$productAyDi." AND status = 2 LIMIT 1"; //price;
														$queryPrice = $db->query($sql);
														if($queryPrice AND $queryPrice->num_rows > 0)
														{
															$resultPrice = $queryPrice->fetch_assoc();
															$price = $resultPrice['price'];
														}
														
														if($price > 0)
														{
															$productIdsArray[] = $productAyDi;
														}
													}
												}
											}
											
											if(count($productIdsArray) > 0)
											{
												$productId = implode(",",$productIdsArray);
												
												$sql = "UPDATE ppic_workschedule SET processRemarks = '".$productId."', availability = 1 WHERE id = ".$workSchedId." LIMIT 1";
												$queryWorkSchedule = $db->query($sql);
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
	}
	
	header('location:gerald_purchaseOrderMakingSummary.php');
					
?>
