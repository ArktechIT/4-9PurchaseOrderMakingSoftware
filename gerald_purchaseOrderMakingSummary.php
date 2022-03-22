<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L1174', 'utf8', 0, 1);?></title>
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
	createHeader('L1174','','gerald_purchaseOrderMakingList.php');
?>
	<form action='gerald_purchaseOrderListExport.php' method='post' id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
<!--
			<button class='api-btn api-btn-back' onclick="location.href='gerald_purchaseOrderMakingList.php';" data-api-title='<?php echo displayText('L1072');?>' <?php echo toolTip('L1072');?>></button>
-->
			<button class='api-btn' onclick="location.href='gerald_updatePurchaseOrderMakingRemarks.php';" data-api-title='<?php echo displayText('L1054', 'utf8', 0, 1); //UPDATE?>' <?php //echo toolTip('L1072');?>></button>
		</div>
		
		<div class="api-top api-col api-title" style='width:40%;'>
			<h2><?php //echo displayText('L1174');?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:30%'>
			<button class='api-btn' onclick="location.href='gerald_purchaseOrderReview.php'" style='width:33%' data-api-title='<?php echo displayText('L1313', 'utf8', 0, 1);?>' <?php echo toolTip('L1313');?>></button>
			<button class='api-btn' onclick="openTinyBox('auto','auto','gerald_onGoingPo.php')" style='width:33%' data-api-title='<?php echo displayText('L1175', 'utf8', 0, 1);?>' <?php echo toolTip('L1175');?>></button>
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
							$sql = "SELECT id, lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 1 ORDER BY targetFinish";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
								{
									$id = $resultWorkSchedule['id'];
									$lotNumber = $resultWorkSchedule['lotNumber'];
									$targetFinish = $resultWorkSchedule['targetFinish'];
									$processRemarks = $resultWorkSchedule['processRemarks'];
									
									$productIds = $processRemarks;
									
									$workingQuantity = 0;
									$poId = $partId = $identifier = '';
									$sql = "SELECT poId, partId, workingQuantity, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
									$queryLotList = $db->query($sql);
									if($queryLotList AND $queryLotList->num_rows > 0)
									{
										$resultLotList = $queryLotList->fetch_assoc();
										$poId = $resultLotList['poId'];
										$partId = $resultLotList['partId'];
										$workingQuantity = $resultLotList['workingQuantity'];
										$identifier = $resultLotList['identifier'];
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
										$sql = "UPDATE ppic_workschedule SET processRemarks = '' WHERE id = ".$id." LIMIT 1";
										$queryUpdate = $db->query($sql);
										
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
									
									$sign = '';
									if($currency == 1)	$sign = '$';
									else if($currency == 2)	$sign = 'Php';
									else if($currency == 3)	$sign = ($_GET['country']==2) ? 'ﾂ･' : '¥';
									
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
											<td><img class='inputForm' src='/Common Data/Templates/images/print.png' height='20' name='".$uniqueSupplier."' style='cursor:pointer;'></td>
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
			$("iframe[name=boxName]").attr('src','gerald_purchaseOrderMakingInputForm.php?uniqueSupplier='+$(this).attr('name'));
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
<!-- -----------------------------------Tiny Box------------------------------------------------------------- -->
<script type="text/javascript" src="/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
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
