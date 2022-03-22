<?php
	$path = $_SERVER['DOCUMENT_ROOT']."/V3/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
	
	$lotNumber = (isset($_GET['lotNumber'])) ? $_GET['lotNumber'] : '';
	$index = (isset($_GET['index'])) ? $_GET['index'] : '';
	
	if($lotNumber=='17-09-9979' AND $_SESSION['idNumber']!='0346')
	{
		echo "Test Lot Number";
		exit(0);
	}
	
	$supplyId = $identifier = $supplyType = '';
	$sql = "SELECT partId, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
	$queryLotList = $db->query($sql);
	if($queryLotList AND $queryLotList->num_rows > 0)
	{
		$resultLotList = $queryLotList->fetch_assoc();
		$partId = $resultLotList['partId'];
		$identifier = $resultLotList['identifier'];
		$supplyType = $resultLotList['status'];
	}
	
	if($identifier==1 OR ($identifier==4 AND $supplyType==2))
	{
		if($identifier==1)
		{
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
						<button style='background-color:<?php echo $rfqColor;?>' onclick="location.href='gerald_prSql.php?updateRFQFlag=updateRFQFlag&<?php echo "prNumber=".$prNumber."&prId=".$prId."&productId=".$productId;?>';"><?php echo displayText('2-7','utf8',0,1,1);?></button>
					</td>
					<td border="0" width="5"></td>
					<td>
						<button style='' onclick="location.href='gerald_supplierProductLinking.php?<?php echo "lotNumber=".$lotNumber."&index=".$index."&supplyId=".$supplyId."&supplyType=".$supplyType;?>';"><?php echo displayText('L1312');?></button>
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
											
											if($continueFlag==1)	continue;
										}
									}

									$currency = '';
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
									
									//~ $onclickEvent = "location.href='gerald_prSql.php?updateSupplier=updateSupplier&lotNumber=".$lotNumber."';";
									
									$button = "";
									//~ if($price > 0)
									//~ if($price > 0 OR in_array($productId,array(1744,520,267,366,1748,1746,4554,546,15750)))
									if($price > 0 OR in_array($productId,array(259,520,546,1744,1746,1748,4554,15750,16386,16388,16390,16392,16394,16396,16422,16427,16429,16431,16433,16435,16437,17132,16365, 17572, 16359, 19282,18804,19285,19126,19125,19286 ,19304,19303,19305,19297,19325,19323,16357, 19404,19403,19324, 19460, 19127, 19567, 19696)))
									{
										$button = "<img class='pickedClass' name='".$productId."' src='/V3/Common Data/Templates/images/accept1.png' style='cursor:pointer;' height='15'>";
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
			$.ajax({
				url:'gerald_purchaseOrderMakingSql.php',
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
						//~ alert(<?php echo $index;?>);
						//~ alert($("img.linkingClass:eq(<?php echo $index;?>)", window.parent.document).length);
						$("img.linkingClass:eq(<?php echo $index;?>)", window.parent.document).hide();
						$("img.unLinkingClass:eq(<?php echo $index;?>)", window.parent.document).show();
					}
					
					$("#closeModalId", window.parent.document).click();
				}
			});
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
