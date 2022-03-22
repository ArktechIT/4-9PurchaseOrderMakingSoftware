<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
	
	$lotNumber = (isset($_GET['lotNumber'])) ? $_GET['lotNumber'] : '';
	$index = (isset($_GET['index'])) ? $_GET['index'] : '';
	$supplyId = (isset($_GET['supplyId'])) ? $_GET['supplyId'] : '';
	$supplyType = (isset($_GET['supplyType'])) ? $_GET['supplyType'] : '';
	$itemSearch = (isset($_POST['itemSearch'])) ? $_POST['itemSearch'] : '';
	
	$description = '';
	if($supplyType==3)
	{
		$sql = "SELECT CONCAT(itemName,' ',itemDescription) as supply FROM purchasing_items WHERE itemId = ".$supplyId." LIMIT 1";
		$queryItems = $db->query($sql);
		if($queryItems AND $queryItems->num_rows > 0)
		{
			$resultItems = $queryItems->fetch_assoc();
			$description = $resultItems['supply'];
		}
	}
	else if($supplyType==4)
	{
		$sql = "SELECT CONCAT(accessoryNumber,' ',accessoryName,' ',accessoryDescription) as supply FROM cadcam_accessories WHERE accessoryId = ".$supplyId." LIMIT 1";
		$queryItems = $db->query($sql);
		if($queryItems AND $queryItems->num_rows > 0)
		{
			$resultItems = $queryItems->fetch_assoc();
			$description = $resultItems['supply'];
		}
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
			$description = $materialType." ".$thickness." X ".$length." X ".$width;
		}
	}
	
	$identifier = $partId = '';
	$sql = "SELECT partId, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
	$queryLotList = $db->query($sql);
	if($queryLotList AND $queryLotList->num_rows > 0)
	{
		$resultLotList = $queryLotList->fetch_assoc();
		$partId = $resultLotList['partId'];
		$identifier = $resultLotList['identifier'];
	}
	
	$productFilter = '';
	$productIdArray = array();
	
	if($identifier==1)
	{
		$partNumber = '';
		$sql = "SELECT partNumber FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
		$queryParts = $db->query($sql);
		if($queryParts AND $queryParts->num_rows > 0)
		{
			$resultParts = $queryParts->fetch_assoc();
			$partNumber = $resultParts['partNumber'];
		}
		
		$aArray = $treatmentIdArray = array();
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
		
		$productIdArray = array();
		$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE (supplyId IN(".implode(",",$treatmentIdArray).") AND supplyType = 2) OR (supplyId IN(".implode(",",$aArray).") AND supplyType = 5)";
		$querySupplierProductLinking = $db->query($sql);
		if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
		{
			while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
			{
				$productIdArray[] = $resultSupplierProductLinking['productId'];
			}
		}
		
		$treatmentNameArray = array();
		$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId IN(".implode(",",$treatmentIdArray).")";
		$queryTreatment = $db->query($sql);
		if($queryTreatment AND $queryTreatment->num_rows > 0)
		{
			while($resultTreatment = $queryTreatment->fetch_assoc())
			{
				$treatmentNameArray[] = $resultTreatment['treatmentName'];
			}
			$description = implode(" ",$treatmentNameArray);
		}
		
		//~ echo $productFilter = " AND productId NOT IN(".implode(",",$productIdArray).") AND (productDescription = '' OR productDescription LIKE '".$partNumber."')";
		$productFilter = " AND (productDescription = '' OR productDescription LIKE '".$partNumber."')";
	}
	else if($identifier==4)
	{
		$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$supplyId." AND supplyType = ".$supplyType."";
		$querySupplierProductLinking = $db->query($sql);
		if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
		{
			while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
			{
				$productIdArray[] = $resultSupplierProductLinking['productId'];
			}
			$productFilter = " AND productId NOT IN(".implode(",",$productIdArray).")";
		}
	}

	
	if($itemSearch!='')	$description = $itemSearch;
	
	$keyWordArray = preg_split("/[\s,]+/",$description);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
	<link href="/V3/Common Data/anthony.css" rel="stylesheet" media="screen" />
	<title><?php echo displayText('L1310');?></title>
</head>
<div id='roundedDiv' style="height:92vh;border:2px solid;border-radius:25px;display:inline-block;width:98%;padding: 1%;background-color:#FFFFFF;"><!-- Rounded Border Div -->
	<table style='width:100%;' cellpadding="0" cellspacing="0" border='0'>
		<tr>
			<td>
			<!-------------------- Main Menu -------------------->
<!--
			<table style='float:left;' cellpadding="0" cellspacing="0" border='0'>
				<tr>
					<td bgcolor="LIGHTGRAY" height="40" width="100">
						<a href=""><font face = 2><center>前ページ<br><?php echo displayText('L1724');?></center></font></a>
					</td>
					<td border="0" width="5"></td>
					<td bgcolor="LIGHTGRAY" height="40" width="100">
						<a href="/dashboard.php"<font face><center>メインメニュー<br><?php echo displayText('L1');?></center></font></a>
					</td>
				</tr>
			</table>
-->
			<!------------------ End Main Menu ------------------>
			<!-------------------- Top Right Buttons -------------------->
			<table style='float:right;' cellpadding="0" cellspacing="0" border='0'>
				<tr>
					<td border="0" width="5"></td>
					<td>
						
					</td>
				</tr>
			</table>
			<!------------------ End Top Right Buttons ------------------>
			</td>
		</tr>
		<tr>
			<!-------------------- Title Header -------------------->
			<th style='color:green;font-size:20px;padding:1px;'><center><?php echo displayText('L1310');?></center></th>
			<!------------------ End Title Header ------------------>
		</tr>
		<tr>
			<!-------------------- Filter -------------------->
			<td>
				<form action='<?php echo $_SERVER['PHP_SELF']."?lotNumber=".$lotNumber."&index=".$index."&supplyId=".$supplyId."&supplyType=".$supplyType; ?>' method='post' id='formId'></form>
				<input type='text' name='itemSearch' value='<?php echo $description; ?>' form='formId'>
				<input type='submit' name='submt' value='<?php echo displayText('L491');?>' form='formId'>				
			</td>
			<!------------------ End Filter ------------------>
		</tr>
		<tr>
			<td>
				<!-------------------- Contents -------------------->
				<div class="grid_8 height400">
					<table class="fancyTable" id="myTable02" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th></th>
							<th><?php echo displayText('L367');?></th>
							<th><?php echo displayText('L1172');?></th>
							<th><?php echo displayText('L1173');?></th>
							<th><?php echo displayText('L267');?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id='results'>
					<?php
						$count = 0;
						$productIdArray = array();
						if(count($keyWordArray) > 0)
						{
							foreach($keyWordArray as $key => $keyWord)
							{
								$keyWord = trim($keyWord);
								
								if($keyWord=='') continue;
								
								$sql = "SELECT productId, productName, productDescription FROM purchasing_supplierproducts WHERE (productName LIKE '%".$keyWord."%' OR productDescription LIKE '%".$keyWord."%')".$productFilter;
								$querySupplierProducts = $db->query($sql);
								if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
								{
									while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
									{
										$productId = $resultSupplierProducts['productId'];
										$productName = $resultSupplierProducts['productName'];
										$productDescription = $resultSupplierProducts['productDescription'];
										
										if(!in_array($productId,$productIdArray))
										{
											$productIdArray[] = $productId;
											
											$hitCount = $productNameHitCount = $productDescriptionHitCount = 0;
											for ($i = 0; $i < count($keyWordArray); $i++)
											{
												$kWord = $keyWordArray[$i];
												$searchPos = (stripos($productName,$kWord));
												if($searchPos!==FALSE)
												{
													$hitCount++;
													$productNameHitCount++;
												}
												else
												{
													$searchPos = (stripos($productDescription,$kWord));
													if($searchPos!==FALSE)
													{
														$hitCount++;
														$productDescriptionHitCount++;
													}
												}
											}
											
											$hitCount += count($keyWordArray) - $key;
											
											$productHitCount[$productId] = $hitCount;
										}
									}
								}
							}
							
							if(count($productHitCount) > 0)
							{
								arsort($productHitCount);
								$productIdsArray = array_keys($productHitCount);
								
								$sql = "SELECT productId, supplierId, supplierType, productName, productDescription FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdsArray).") ORDER BY FIELD(productId,".implode(",",$productIdsArray).")";
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
										
										for ($i = 0; $i < count($keyWordArray); $i++)
										//~ for ($i = 0; $i < 1; $i++)
										{
											$kWord = $keyWordArray[$i];
											$searchPos = (stripos($productName,$kWord));
											if($searchPos!==FALSE)
											{
												//~ $productName = str_ireplace($kWord,"<mark>".$kWord."</mark>",$productName);
												$productName = substr_replace($productName, "<mark>", $searchPos, 0);
												$productName = substr_replace($productName, "</mark>", ($searchPos+strlen($kWord)+6), 0);
											}
											
											$searchPos = (stripos($productDescription,$kWord));
											if($searchPos!==FALSE)
											{
												//~ $productDescription = str_ireplace($kWord,"<mark>".$kWord."</mark>",$productDescription);
												$productDescription = substr_replace($productDescription, "<mark>", $searchPos, 0);
												$productDescription = substr_replace($productDescription, "</mark>", ($searchPos+strlen($kWord)+6), 0);
											}
										}
										
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
										
										$price = 0;
										$sql = "SELECT currency, price FROM purchasing_price WHERE productId = ".$productId." AND status = 2 LIMIT 1"; //price;
										$queryPrice = $db->query($sql);
										if($queryPrice AND $queryPrice->num_rows > 0)
										{
											$resultPrice = $queryPrice->fetch_assoc();
											$currency = $resultPrice['currency'];
											$price = $resultPrice['price'];
										}
										
										if($currency==1)		$currencySign = 'USD';
										else if($currency==2)	$currencySign = 'PHP';
										else if($currency==3)	$currencySign = 'YEN';
										
										//~ $onclickEvent = "location.href='gerald_prSql.php?updateSupplier=updateSupplier&prNumber=".$prNumber."&prId=".$prId."&productId=".$productId."&supplyId=".$supplyId."&supplyType=".$supplyType."';";
										
										$button = "";
										if($price > 0)
										{
											$button = "<img class='pickedClass' name='".$productId."' src='/V3/Common Data/Templates/images/accept1.png' style='cursor:pointer;' height='15' onclick=\" ".$onclickEvent." \">";
										}
										
										echo "
											<tr>
												<td style='width:3vw';>".++$count."</td>
												<td style='width:15vw';>".$supplierAlias."</td>
												<td style='width:25vw';>".$productName."</td>
												<td style='width:30vw';>".$productDescription."</td>
												<td style='width:vw'; align='right'>".$currencySign." ".number_format($price,4)."</td>
												<td>".$button."</td>
											</tr>
										";
									}
								}
							}
						}
					?>
					</tbody>
					<tfoot>
						<tr><th colspan='6'></th></tr>
					</tfoot>
					</table>
				</div>
				<!------------------ End Contents ------------------>
			</td>
		</tr>
	</table>
</div><!-- End Rounded Border Div -->
<link href="/V3/Common Data/Templates/tableDesign.css" rel="stylesheet" media="screen" />
<script src="/V3/Common Data/Libraries/Javascript/Table with Fixed Header/jquery.min.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/Table with Fixed Header/jquery.fixedheadertable.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
<script src="/V3/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>
<style>
.height400 {
		height: 80vh;
        overflow-x: auto;
        overflow-y: auto;
}	
</style>
<script>
	$(function(){
		$('#myTable02').fixedHeaderTable({	footer: true,});
		$('td').css('font-size','12px');
		
		$('img.pickedClass').click(function(){
			var productId = $(this).attr('name');
			
			var parentHtml = $(this).parent().parent().html();
			
			swal({
				title: '<?php echo $description; ?>',
				//~ html:"Suggested Location : <br><span style='font-size:13vh;font-weight:bold;'>aa</span><br>Is Item Fits?",
				html:"You will link this item to <br><table border='1'>"+parentHtml+"</table><br>Click yes to proceed.",
				text: 'Are you sure you want link this',
				type: 'info',
				showCancelButton: true,
				allowOutsideClick: false,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes'
			}).then(function(){
				$.ajax({
					url:'gerald_purchaseOrderMakingSql.php?<?php echo "supplyId=".$supplyId."&supplyType=".$supplyType; ?>',
					type:'post',
					dataType: 'json',
					data:{
						ajaxType:'linkedProduct',
						lotNumber:'<?php echo $lotNumber;?>',
						productId:productId
					},
					success:function(data){
						//~ $("#asd", window.parent.document).text(data.sql);
						
						var priceArray = data.price;
						var currencySignArray = data.currency;
						
						$("td.supplierAlias:eq(<?php echo $index;?>)", window.parent.document).html(data.supplierAlias);
						$("td.productName:eq(<?php echo $index;?>)", window.parent.document).html(data.productName);
						$("td.productDescription:eq(<?php echo $index;?>)", window.parent.document).html(data.productDescription);
						
						var totalPriceArray = [];
						var currencyPriceArray = [];
						
						for (var i = 0; i < priceArray.length; i++)
						{
							totalPriceArray[i] = currencySignArray[i]+" "+(parseFloat(priceArray[i]) * parseFloat($("td.workingQuantity:eq(<?php echo $index;?>)", window.parent.document).text())).toFixed(2);
							currencyPriceArray[i] = currencySignArray[i]+' '+priceArray[i];
						}
						
						$("td.price:eq(<?php echo $index;?>)", window.parent.document).html(currencyPriceArray.join('<br>'));
						$("td.totalPrice:eq(<?php echo $index;?>)", window.parent.document).html(totalPriceArray.join('<br>'));
						
						if(data.unLinkFlag=='1')
						{
							$("img.linkingClass:eq(<?php echo $index;?>)", window.parent.document).hide();
							$("img.unLinkingClass:eq(<?php echo $index;?>)", window.parent.document).show();
						}
						
						$("#closeModalId", window.parent.document).click();
					}
				});						
			})
		});		
	});
</script>
<!-- ---------------------------------------- Tiny Box Script ------------------------------------------------------------------ -->
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script type="text/javascript">
function openTinyBox(w,h,url,post,iframe,html,left,top)
{
	TINY.box.show({
		url:url,width:w,height:h,post:post,html:html,opacity:20,topsplit:3,animate:false,close:true,iframe:iframe,left:left,top:top,
		boxid:'box',
		openjs:function(){
			
		}
	});
}
</script>
<!-- ---------------------------------------- Tiny Box Script ------------------------------------------------------------------ -->
