<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='linkedProduct')
		{
			$workSchedId = $_POST['workSchedId'];
			$productId = $_POST['productId'];
			$supplierId = $_POST['supplierId'];
			$supplierType = $_POST['supplierType'];
			$poCurrency = $_POST['poCurrency'];
			$itemPrice = $_POST['price'];
			$itemQuantity = $_POST['quantity'];
			$lotNumber = $_POST['lotNumber'];
			
			$itemName = $itemDescription = $itemUnit = $itemContentQuantity = $itemContentUnit = '';
			$sql = "SELECT productName, productDescription, productUnit, productContentQuantity, productContentUnit FROM purchasing_supplierproducts WHERE productId = ".$productId." LIMIT 1";
			$querySupplierProducts = $db->query($sql);
			if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
			{
				$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
				$itemName = $resultSupplierProducts['productName'];
				$itemDescription = $resultSupplierProducts['productDescription'];
				$itemUnit = $resultSupplierProducts['productUnit'];
				$itemContentQuantity = $resultSupplierProducts['productContentQuantity'];
				$itemContentUnit = $resultSupplierProducts['productContentUnit'];
			}

			$itemName = $db->real_escape_string($itemName);
			$itemDescription = $db->real_escape_string($itemDescription);
			
			$dateNeeded = '';
			$sql = "SELECT dateNeeded FROM purchasing_prcontent WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryPrContent = $db->query($sql);
			if($queryPrContent AND $queryPrContent->num_rows > 0)
			{
				$resultPrContent = $queryPrContent->fetch_assoc();
				$dateNeeded = $resultPrContent['dateNeeded'];
			}			
			
			$itemFlag = '';
			
			$sql = "SELECT identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$identifier = $resultLotList['identifier'];
				$status = $resultLotList['status'];
				
				if($identifier==1)
				{
					$treatmentName = '';
					$sql = "SELECT processRemarks FROM ppic_workschedule WHERE id = ".$workSchedId." LIMIT 1";
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
						$treatmentName = $resultWorkSchedule['processRemarks'];
					}
					
					$treatmentId = '';
					$sql = "SELECT treatmentId FROM engineering_treatment WHERE treatmentName LIKE '".$treatmentName."' LIMIT 1";
					$queryTreatment = $db->query($sql);
					if($queryTreatment AND $queryTreatment->num_rows > 0)
					{
						$resultTreatment = $queryTreatment->fetch_assoc();
						$treatmentId = $resultTreatment['treatmentId'];
					}
					
					$processOrder = '';
					//~ $sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) AND processRemarks LIKE '".$treatmentName."' LIMIT 1";
					$sql = "SELECT processOrder, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$treatmentId." LIMIT 1";
					$queryWorkschedule = $db->query($sql);
					if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
					{
						$resultWorkschedule = $queryWorkschedule->fetch_assoc();
						$processOrder = $resultWorkschedule['processOrder'];
						$dateNeeded = $resultWorkschedule['targetFinish'];
					}
					
					$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,229) AND processOrder >= ".$processOrder." LIMIT 1";
					$queryWorkschedule = $db->query($sql);
					if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
					{
						$resultWorkschedule = $queryWorkschedule->fetch_assoc();
						$dateNeeded = $resultWorkschedule['targetFinish'];
					}
				}
				else if($identifier==4)
				{
					if($status==1)
					{
						$sql = "SELECT pvc FROM system_confirmedmaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryConfirmedMaterialPo = $db->query($sql);
						if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
						{
							$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
							$itemFlag = $resultConfirmedMaterialPo['pvc'];
						}
						
						$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryMaterialComputation = $db->query($sql);
						if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
						{
							$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
							$dateNeeded = $resultMaterialComputation['dateNeeded'];
						}
					}
				}
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
			
			exit(0);
		}
	}
	
	$workSchedId = (isset($_POST['workSchedId'])) ? $_POST['workSchedId'] : '';
	$type = (isset($_POST['type'])) ? $_POST['type'] : 0;
	
	$lotNumber = $processRemarks = '';
	$sql = "SELECT lotNumber, processRemarks FROM ppic_workschedule WHERE id = ".$workSchedId." LIMIT 1";
	$queryWorkSchedule = $db->query($sql);
	if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
	{
		$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
		$lotNumber = $resultWorkSchedule['lotNumber'];
		$processRemarks = $resultWorkSchedule['processRemarks'];
	}
	
	if($type==1)
	{
		$sql = "DELETE FROM purchasing_forpurchaseorder WHERE lotNumber = '".$lotNumber."' AND processRemarks = '".$processRemarks."' LIMIT 1";
		$queryDelete = $db->query($sql);
		exit(0);
	}
	
	if($lotNumber=='17-09-9979' AND $_SESSION['idNumber']!='0346')
	{
		echo "Test Lot Number";
		exit(0);
	}
	
	$supplyId = $identifier = $supplyType = $workingQuantity = '';
	$sql = "SELECT partId, identifier, status, workingQuantity FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
	$queryLotList = $db->query($sql);
	if($queryLotList AND $queryLotList->num_rows > 0)
	{
		$resultLotList = $queryLotList->fetch_assoc();
		$partId = $resultLotList['partId'];
		$identifier = $resultLotList['identifier'];
		$supplyType = $resultLotList['status'];
		$workingQuantity = $resultLotList['workingQuantity'];
	}
	
	if($identifier==1 OR ($identifier==4 AND $supplyType==2))
	{
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
			
			$productIdArray = array();
			//~ $sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE (supplyId IN(".implode(",",$treatmentIdArray).") AND supplyType = 2) OR (supplyId IN(".implode(",",$aArray).") AND supplyType = 5)";
			$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId IN(".implode(",",$aArray).") AND supplyType = 5";
			$querySupplierProductLinking = $db->query($sql);
			if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
			{
				while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
				{
					$productIdArray[] = $resultSupplierProductLinking['productId'];
				}
			}
		}
		else if($identifier==4)
		{
			$sql = "SELECT lotNumber FROM view_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 92 LIMIT 1";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				echo "You cannot PO this yet! Please finish Middle QC Inspection first!";
				exit(0);
			}
			
			$materialId = $cadamTreatmentId = '';
			$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
			$querySubconMaterial = $db->query($sql);
			if($querySubconMaterial->num_rows > 0)
			{
				$resultSubconMaterial = $querySubconMaterial->fetch_array();
				$materialId = $resultSubconMaterial['materialId'];
				$cadamTreatmentId = $resultSubconMaterial['treatmentId'];
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
			
			$productIdArray = array();
			$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId IN(".implode(",",$treatmentIdArray).") AND supplyType = 2";
			$querySupplierProductLinking = $db->query($sql);
			if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
			{
				while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
				{
					$productIdArray[] = $resultSupplierProductLinking['productId'];
				}
			}
		}
	}
	else if($identifier==4)
	{
		$supplyId = $partId;
		
		$productIdArray = array();
		$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$supplyId." AND supplyType = ".$supplyType."";
		$querySupplierProductLinking = $db->query($sql);
		if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
		{
			while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
			{
				$productIdArray[] = $resultSupplierProductLinking['productId'];
			}
		}
		
		if($_GET['country']==1)
		{
			if($supplyType==3)
			{
				$itemName = $itemDescription = '';
				$sql = "SELECT itemName, itemDescription FROM purchasing_items WHERE itemId = ".$supplyId." LIMIT 1";
				$queryItems = $db->query($sql);
				if($queryItems AND $queryItems->num_rows > 0)
				{
					$resultItems = $queryItems->fetch_assoc();
					$itemName = $resultItems['itemName'];
					$itemDescription = $resultItems['itemDescription'];
				}
				
				$metalWebMaterialArray = array('1050A H14','SUS304 SGB3','SUS 304L 2B','2024-T3','SUS 304L','304L2B Finish','MS2007','MS2009','6082T6','Alu Hex-Hole Perforated S','1050AH14');
				
				foreach($metalWebMaterialArray as $metalWebMaterial)
				{
					if(stristr($itemName,$metalWebMaterial)!==FALSE OR stristr($itemDescription,$metalWebMaterial)!==FALSE)
					{
						echo "Please purchase this in material";
						exit(0);
					}
				}
			}
		}
	}
	
	$rfqColor = '';
	if($requestForQuotation==1)	$rfqColor = 'red';	
	
	$tableId = $_SESSION['idNumber'].date('YmdHis');
	//~ $tableId = "mainTableId2";
?>
<div>
	<div class='col-md-12'>
<!--
		<table class='table table-bordered table-condensed table-striped' id="mainTableId2">
-->
		<table style='width:100%' class='table table-bordered table-condensed table-striped' id="<?php echo $tableId;?>">
			<thead class='w3-indigo thead'>
			<tr>
				<!--th class='w3-center'><input type="checkbox" id="select_all"></th-->
				<th class='w3-center'>#</th>
				<th class='w3-center'><?php echo displayText('L367');?></th>
				<th class='w3-center'><?php echo displayText('L1172');?></th>
				<th class='w3-center'><?php echo displayText('L1173');?></th>							
				<th class='w3-center'><?php echo displayText('L267');?></th>
				<th class='w3-center'></th>
				<th class='w3-center'></th>
			</tr>
			</thead>
			<tbody class='tbody'>
				<?php
					$count = $noSubconFlag = 0;
					if(count($productIdArray) > 0)
					{
						$sql = "SELECT productId, supplierId, supplierType, productName, productDescription FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") ORDER BY productName, productDescription";
						$querySupplierProducts = $db->query($sql);
						if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
						{
							while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
							{
								$productId = $resultSupplierProducts['productId'];
								$supplierId = $resultSupplierProducts['supplierId'];
								$supplierType = $resultSupplierProducts['supplierType'];
								$productName = $resultSupplierProducts['productName'];
								$productDescription = $resultSupplierProducts['productDescription'];
								
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
									
									if($identifier==1)
									{
										$subconListIdArray = array();
										$sql = "SELECT supplyId, supplyType FROM purchasing_supplierproductlinking WHERE productId = ".$productId."";
										$querySupplierProductLinking = $db->query($sql);
										if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
										{
											while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
											{
												$supplyId = $resultSupplierProductLinking['supplyId'];
												$supplyType = $resultSupplierProductLinking['supplyType'];
												
												if($supplyType==2)
												{
													$sql = "SELECT a FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$supplyId." LIMIT 1";
													$querySubconList = $db->query($sql);
													if($querySubconList->num_rows > 0)
													{
														$resultSubconList = $querySubconList->fetch_assoc();
														$subconListIdArray[] = $resultSubconList['a'];
													}
												}
												else if($supplyType==5)
												{
													$sql = "SELECT a FROM cadcam_subconlist WHERE a = ".$supplyId." AND partId = ".$partId." LIMIT 1";
													$querySubconList = $db->query($sql);
													if($querySubconList->num_rows > 0)
													{
														$resultSubconList = $querySubconList->fetch_assoc();
														$subconListIdArray[] = $resultSubconList['a'];
													}
												}
											}
										}
										
										$continueFlag = 1;
										if(count($subconListIdArray) > 0)
										{
											$sql = "SELECT listId FROM `engineering_subconprocessor` WHERE `a` IN (".implode(",",$subconListIdArray).") AND subconId = ".$supplierId." LIMIT 1";
											$querySubconProcessor = $db->query($sql);
											if($querySubconProcessor AND $querySubconProcessor->num_rows > 0)
											{
												$continueFlag = 0;
												
												$sql = "SELECT listId FROM `system_rfq` WHERE partId = ".$partId." AND `customerId` IN(28,45) AND `dateInserted` >= '2018-02-01 00:00:00'";
												$queryRFQ = $db->query($sql);
												if($queryRFQ AND $queryRFQ->num_rows > 0 AND $supplyType==2)
												{
													$continueFlag = 1;
												}
											}
										}
										
										if($continueFlag==1)
										{
											$noSubconFlag = 1;
											continue;
										}
									}
								}

								$proceedFlag = 0;
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
											//~ echo "<br>".$price;
											//~ echo "<br>".$price." $workingQuantity >= $priceLowerRange AND $workingQuantity <= $priceUpperRange";
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
										
										if($breakFlag==1)
										{
											$productPrice = $price;
											if($price==0 AND $supplierId==3 AND $productDescription=='Tapping')//$supplierId==3 TGS
											{
												$proceedFlag = 1;
											}
											break;
										}
									}
								}
								
								if($currency==1)		$currencySign = 'USD';
								else if($currency==2)	$currencySign = 'PHP';
								else if($currency==3)	$currencySign = 'YEN';
								
								//~ $onclickEvent = "location.href='gerald_prSql.php?updateSupplier=updateSupplier&lotNumber=".$lotNumber."';";
								
								$button = "";
								//~ if($price > 0)
								//~ if($price > 0 OR in_array($productId,array(1744,520,267,366,1748,1746,4554,546,15750)))
								//echo $productId;
								if($productPrice > 0 OR in_array($productId,array(259,520,546,1744,1746,1748,4554,15750,16386,16388,16390,16392,16394,16396,16422,16427,16429,16431,16433,16435,16437,17132,16365, 17572, 16359, 19282,18804,19285,19126,19125,19286 ,19304,19303,19305,19297,19325,19323,16357, 19404,19403,19324, 19460, 19127, 19567, 19696, 25083, 25388,25391, 25384,25385,25386, 26454,26456,26458, 26472,26475,26477,26479)) OR ($supplierId==28 AND $supplierType==2) OR $proceedFlag==1)
								{
									$button = "<img class='pickedClass' data-product-id='".$productId."' data-supplier-id='".$supplierId."' data-supplier-type='".$supplierType."' data-po-currency='".$currency."' data-price='".$productPrice."' src='/".v."/Common Data/Templates/images/accept1.png' style='cursor:pointer;' height='15'>";
								}
								
								$button2 = "<img onclick=\" location.href='/".v."/4-D Purchasing Price List/gerald_priceV2.php?supplyType=".$supplyType."&listId=".$productId."' \" src='/".v."/Common Data/Templates/images/view1.png' style='cursor:pointer;' height='15' title='Price List' >";
								
								echo "
									<tr>
										<td>".++$count."</td>
										<td>".$supplierAlias."</td>
										<td>".$productName."</td>
										<td>".$productDescription."</td>
										<td>".$currencySign." ".number_format($productPrice,4)."</td>
										<td>".$button."</td>
										<td>".$button2."</td>
									</tr>
								";
							}
						}
					}
					
					//~ if($count == 0 AND $noSubconFlag==1)
					if($count == 0)
					{
						echo "
							<tr>
								<td colspan='7'><a href='/".v."/2-C%20Parts%20Management%20Software/anthony_editProduct.php?partId=".$partId."&src=subcon&patternId=0' target='_blank'>No subcon link</a></td>
							</tr>
						";
					}
				?>
			</tbody>
		</table>
	</div>
</div>
<script>
	$(function(){
		//~ var dataTable = $('#mainTableId2').DataTable( {
		var dataTable = $('#<?php echo $tableId;?>').DataTable( {
			"processing"    : true,
			"ordering"      : true,
			"searching"     : false,
			"bInfo" 		: false,
			//~ fixedColumns:   {
					//~ leftColumns: 0
			//~ },
			scrollY     	: 300,
			scrollX     	: false,
			scrollCollapse	: false,
			scroller    	: {
				loadingIndicator    : true
			},
			stateSave   	: false
		});
		
		$('img.pickedClass').click(function(){
			var productId = $(this).data('productId');
			var supplierId = $(this).data('supplierId');
			var supplierType = $(this).data('supplierType');
			var poCurrency = $(this).data('poCurrency');
			var price = $(this).data('price');
			var lotNumber = "<?php echo $lotNumber; ?>";
			var quantity = "<?php echo $workingQuantity; ?>";
			
			//~ alert(productId+' '+supplierId+' '+supplierType+' '+poCurrency);
			
			$.ajax({
				url:'gerald_searchProductList.php',
				type:'post',
				data:{
					ajaxType:'linkedProduct',
					workSchedId:'<?php echo $workSchedId;?>',
					productId:productId,
					supplierId:supplierId,
					supplierType:supplierType,
					poCurrency:poCurrency,
					price:price,
					lotNumber:lotNumber,
					quantity:quantity
				},
				success:function(data){
					//~ alert(data);
					console.log(data);
					window.parent.location.reload();
					//~ $("#closeModalId", window.parent.document).click();
				}
			});
		});
	});
</script>
