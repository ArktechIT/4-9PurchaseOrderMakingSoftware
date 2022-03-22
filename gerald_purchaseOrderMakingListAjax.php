<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	$groupNo = $_POST['groupNo'];
	$sqlFilter = $_POST['sqlFilter'];
	$queryLimit = 50;
	$queryPosition = ($groupNo * $queryLimit);
	
	$count = $queryPosition;
	$sql = "SELECT id, lotNumber, processRemarks, targetFinish FROM view_workschedule ".$sqlFilter." ORDER BY targetFinish LIMIT ".$queryPosition.", ".$queryLimit;
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
				if($productId!='')
				{
					$sql = "UPDATE ppic_workschedule SET processRemarks = '' WHERE id = ".$workSchedId." LIMIT 1";
					$queryUpdate = $db->query($sql);
				}
				
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
			
			$color = "";
			$noSubconLinkFlag = "";
			$treatmentIdArray = $treatmentNameArray = array();
			if($identifier==1 OR ($identifier==4 AND $supplyType==2))
			{
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
					
					//~ $sql = "SELECT listId FROM engineering_subconprocessor WHERE a IN(".implode(",",$aArray).")";
					//~ $querySubconProcessor = $db->query($sql);
					//~ if($querySubconProcessor AND $querySubconProcessor->num_rows != count($aArray))
					//~ {
							//~ $noSubconLinkFlag = 1;
					//~ }
					
					$asd = '';
					
					$noSubconLinkFlag = 1;
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
							$noSubconLinkFlag = 0;
							
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
									if(!($queryPrice AND $queryPrice->num_rows > 0))
									{
										$color = "background-color:red;";
									}
									else
									{
										//~ if($_SESSION['idNumber']=='0346')
										//~ {
											if($productId=='')
											{
												$productIdsArray = array();
												if($holdFlag==0)
												{
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
										//~ }
									}
								}
								else
								{
									$color = "background-color:red;";
								}
							}
							else
							{
								$color = "background-color:red;";
							}
						}
					}
				}
				else if($identifier==4)
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
					
					$materialType = '';
					$sql = "SELECT `materialType` FROM `engineering_materialtype` WHERE `materialTypeId` = ".$materialTypeId." LIMIT 1";
					$queryMaterialType = $db->query($sql);
					if($queryMaterialType->num_rows > 0)
					{
						$resultMaterialType = $queryMaterialType->fetch_array();
						$materialType = $resultMaterialType['materialType'];
					}
					
					$sql = "SELECT processCode FROM engineering_subcontreatment WHERE treatmentId = ".$cadamTreatmentId."";
					$querySubconTreatment = $db->query($sql);
					if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
					{
						while($resultSubconTreatment = $querySubconTreatment->fetch_assoc())
						{
							$treatmentIdArray[] = $resultSubconTreatment['processCode'];
						}
					}
					
					$partNumber = $materialType." t".$thickness."X".$length."X".$width;
				}
				
				if(count($treatmentIdArray)>0)
				{
					$sql = "SELECT treatmentId, treatmentName FROM engineering_treatment WHERE treatmentId IN(".implode(",",$treatmentIdArray).")";
					$queryTreatment = $db->query($sql);
					if($queryTreatment->num_rows > 0)
					{
						while($resultTreatment = $queryTreatment->fetch_array())
						{
							$treatmentNameArray[] = $resultTreatment['treatmentName'];
						}
					}
				}
				
				$treatmentProcess = (count($treatmentNameArray) > 0) ? implode(",",$treatmentNameArray) : '';										
				
				$description = $partNumber." rev ".$revisionId."<br>".$treatmentProcess;
				
				$supplyTypeName = 'Subcon';
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
				
				if($_SESSION['idNumber']=='0346.')
				{
					if($productId=='')
					{
						if($identifier!=4)
						{
							$productAyDi = '';
							$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$supplyId." AND supplyType = ".$supplyType."";
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
									$productId = $productAyDi;
									
									$sql = "UPDATE ppic_workschedule SET processRemarks = '".$productId."', availability = 1 WHERE id = ".$workSchedId." LIMIT 1";
									//~ $queryWorkSchedule = $db->query($sql);
								}
							}
						}
					}
				}				
				
				$editTableNumber = "editTableNumber";
			}
			
			$displayLink = '';
			$displayUnLink = 'display:none;';
			
			$productMOQ = 'N/A';
			$supplierAliasArray = $productNameArray = $productDescriptionArray = $priceArray = $totalPriceArray = array();
			$sql = "SELECT productId, supplierId, supplierType, productName, productDescription, productMOQ FROM purchasing_supplierproducts WHERE productId IN(".$productId.")";
			$querySupplierProducts = $db->query($sql);
			if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
			{
				while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
				{
					$prodId = $resultSupplierProducts['productId'];
					$supplierId = $resultSupplierProducts['supplierId'];
					$supplierType = $resultSupplierProducts['supplierType'];
					$productName = $resultSupplierProducts['productName'];
					$productDescription = $resultSupplierProducts['productDescription'];
					$productMOQ = $resultSupplierProducts['productMOQ'];
					
					$currency = $price = $statusPrice = $totalPrice = '';
					$sql = "SELECT currency, price, status FROM purchasing_price WHERE productId = ".$prodId." LIMIT 1"; //price;
					$queryPrice = $db->query($sql);
					if($queryPrice AND $queryPrice->num_rows > 0)
					{
						$resultPrice = $queryPrice->fetch_assoc();
						$currency = $resultPrice['currency'];
						$price = $resultPrice['price'];
						$statusPrice = $resultPrice['status'];
						
						$totalPrice = ($price * $workingQuantity);
						
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
						$productMOQ = 'N/A';
					}
					
					if(!in_array($supplierAlias,$supplierAliasArray))	$supplierAliasArray[] = $supplierAlias;
					if(!in_array($productName,$productNameArray))	$productNameArray[] = $productName;
					if(!in_array($productDescription,$productDescriptionArray))	$productDescriptionArray[] = $productDescription;
					$priceArray[] = $currencySign." ".$price;
					$totalPriceArray[] = $currencySign." ".$totalPrice;
				}
			}
			//~ $asd = '';
			$displayRemove = '';
			if(!in_array($_SESSION['idNumber'],array('0346','0446')))	$displayRemove = 'display:none;';
			if($identifier==1 OR ($identifier==4 AND $supplyType==2))
			{
				//~ if($_SESSION['idNumber']=='0346')
				//~ {
					//~ $asd = "gerald".count($treatmentNameArray)." ".count($productNameArray)." ".implode(",",$productNameArray);
				//~ }
				$displayRemove = 'display:none;';
				if(count($treatmentNameArray) > 0 AND (count($treatmentNameArray) == count($productNameArray) OR count($treatmentNameArray) == count($productDescriptionArray)))
				{
					$displayLink = 'display:none;';
					$displayUnLink = '';
				}
			}
			else
			{
				if(count($productNameArray) > 0)
				{
					$displayLink = 'display:none;';
					$displayUnLink = '';
					$displayRemove = 'display:none;';
				}
			}			
			
			$poNumber = "";
			$supplierColor = "";
			$actionButtons = "";
			$sql = "SELECT poNumber FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND itemStatus != 2 LIMIT 1";
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows == 0)
			{
				if($noSubconLinkFlag==1 OR $holdFlag==1)
				{
					$displayLink = $displayUnLink = $displayRemove = 'display:none;';
				}
			}
			else
			{
				$resultPoContents = $queryPoContents->fetch_assoc();
				$poNumber = $resultPoContents['poNumber'];
				
				$displayLink = $displayUnLink = $displayRemove = 'display:none;';
				$editTableNumber = "";
			}
			
			if(in_array($_SESSION['departmentId'],array(4,5)) OR $_GET['country']==2 OR ($_SESSION['idNumber']=='0449' AND in_array(date('Y-m-d'),array('2017-10-18','2017-10-24','2017-10-30'))) OR ($_SESSION['idNumber']=='0048' AND in_array(date('Y-m-d'),array('2017-11-17'))) OR ($_SESSION['idNumber']=='0470' AND in_array(date('Y-m-d'),array('2018-01-11'))) OR $_SESSION['idNumber']=='0466')
			{
				$actionButtons = $asd."
					<img class='linkingClass' src='/V3/Common Data/Templates/images/view1.png' height='20' name='".$lotNumber."' style='cursor:pointer;".$displayLink."'>
					<img class='unLinkingClass' src='/V3/Common Data/Templates/images/close1.png' height='20' name='".$lotNumber."' style='cursor:pointer;".$displayUnLink."'>
					<img class='removeFromListClass' src='/V3/Common Data/Templates/images/trash1.png' height='20' name='".$lotNumber."' style='cursor:pointer;".$displayRemove."'>
				";
			}
			
			if($_SESSION['idNumber']=='0446')
			{
				$actionButtons = "
					<img class='removeFromListClass' src='/V3/Common Data/Templates/images/trash1.png' height='20' name='".$lotNumber."' style='cursor:pointer;".$displayRemove."'>
				";				
			}
			
			$supplierAlias = (count($supplierAliasArray)) ? implode("<br>",$supplierAliasArray) : '';
			$productName = (count($productNameArray)) ? implode("<br>",$productNameArray) : '';
			$productDescription = (count($productDescriptionArray)) ? implode("<br>",$productDescriptionArray) : '';
			$price = (count($priceArray)) ? implode("<br>",$priceArray) : '';
			$totalPrice = (count($totalPriceArray)) ? implode("<br>",$totalPriceArray) : '';
			
			if($noSubconLinkFlag==1)
			{
				$supplierAlias = "No Assigned Subcon";
				$supplierColor = "color:red;";
				
				if($_GET['country']==2)
				{
					//~ $supplierAlias = "<a href='/2-C Parts Management Software/anthony_editProduct.php?partId=".$partId."&src=subcon'>No Assigned Subcon</a>";
					$supplierAlias = "<span style='cursor:pointer;' onclick=\" window.open('/V3/2-C Parts Management Software/anthony_editProduct.php?partId=".$partId."&src=subcon','newWindow','left=20,screenX=300,screenY=30,resizable,scrollbars,status,width=2000,height=650'); \">No Assigned Subcon</span>";
				}
			}
			
			//~ $class = (($count%2)==0) ? "class='odd'" : "";
			if($_GET['country']==1)
			{
				if($supplyTypeName == 'Material')
				{
					if($poNumber=='')
					{
						if($dateNeeded != "")
						{
							$actionButtons .= "<a title='Details' onclick=\"TINY.box.show({url:'rhay_viewMaterialDetails.php?lotNumber=".$lotNumber."&dateNeeded=".$dateNeeded."', width:550, height:500, opacity:20,top:1,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/V3/Common Data/Templates/images/details.png' height='20' ></a>";
							if($_SESSION['userType'] == 0)
							{
								$actionButtons .= "<a href = '#'title='Remove' onclick=\"TINY.box.show({url:'rhay_deleteConfirmedMaterialPo.php?lote=".$lotNumber."&dateNeeded=".$dateNeeded."', width:300, height:200, opacity:20,top:100,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/V3/Common Data/Templates/images/cross1.png' height='20' ></a>"; 
							}
						} 
						else
						{
							$actionButtons .= "<a title='Details' onclick=\"TINY.box.show({url:'rhay_viewMaterialDetails.php?lotNumber=".$lotNumber."&dateNeeded=".$dateNeeded."', width:300, height:200, opacity:20,top:100,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/V3/Common Data/Templates/images/details.png' height='20' ></a>";
							if($_SESSION['userType'] == 0)
							{
								$actionButtons .= "<a href = '#'title='Remove' onclick=\"TINY.box.show({url:'rhay_deleteConfirmedMaterialPo.php?lote=".$lotNumber."&dateNeeded=".$dateNeeded."', width:300, height:200, opacity:20,top:100,animate:true,close:true,openjs:function(){myFunction()}});\"><img src='/V3/Common Data/Templates/images/cross1.png' height='20' ></a>"; 
							}
						}
					}
				}
			}
			
			$holdSpan = '';
			if($holdFlag==1)
			{
				$holdSpan = "<br><span style='color:red;font-size:8px;'>(Hold Lot Number)</span>";
			}
			
			$poNumberSpan = '';
			if($poNumber!='')
			{
				$poNumberSpan = "<br><span style='color:red;'>(Ongoing PO : ".$poNumber.")</span>";
			}
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td>".++$count."<br><input type='checkbox'></td>
					<td title='".$dateGenerated."'>".$lotNumber.$holdSpan."</td>
					<td>".$description."</td>
					<td class='workingQuantity ".$editTableNumber."' title='Double Click to Edit' data-lote='".$lotNumber."'>".$workingQuantity."</td>
					<td>".$productMOQ."</td>
					<td>".$supplyTypeName."</td>
					<td>".$dateNeeded."</td>
					<td>".$targetFinish."</td>
					<td class='supplierAlias' style='".$supplierColor."'>".$supplierAlias.$poNumberSpan."</td>
					<td class='productName'>".$productName."</td>
					<td class='productDescription'>".$productDescription."</td>
					<td class='price' align='right'>".$price."</td>
					<td class='totalPrice' align='right'>".$totalPrice."</td>										
					<td style='".$color."' align='center'>".$actionButtons."</td>
				</tr>
			";
		}
		echo $tableContent;
	}
					
?>
