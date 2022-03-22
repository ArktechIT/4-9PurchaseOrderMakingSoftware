<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	if(isset($_GET['listId']))
	{
		$key = $_GET['key'];
		$listId = $_GET['listId'];
		
		$sql = "SELECT lotNumber FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
		$queryForPurchaseOrder = $db->query($sql);
		if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
		{
			$resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc();
			$lotNumber = $resultForPurchaseOrder['lotNumber'];
			
			$sql = "DELETE FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."'";
			$queryDelete = $db->query($sql);
			
			$sql = "UPDATE ppic_workschedule SET status = 0, quantity = 0, employeeId = '',employeeIdStart = '', actualStart = '0000-00-00 00:00:00' , actualEnd = '0000-00-00 00:00:00', actualFinish = '0000-00-00' WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 597 AND status = 1";
			$queryUpdate = $db->query($sql);
		}
		
		header('location:'.$_SERVER['PHP_SELF'].'?key='.$key);
		
		exit(0);
	}
	else if(isset($_GET['key']))
	{
		$key = $_GET['key'];
		
		$keyPart = explode("-",$key);
		$supplierPart = explode("`",$keyPart[0]);
		$supplierId = $supplierPart[0];
		$supplierType = $supplierPart[1];
		$poCurrency = $keyPart[1];
		
		$sql = "SELECT listId, lotNumber, processRemarks, itemName, itemDescription, itemQuantity, itemPrice FROM purchasing_forpurchaseorder WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency."";
		$queryForPurchaseOrder = $db->query($sql);
		if($queryForPurchaseOrder AND $queryForPurchaseOrder->num_rows > 0)
		{
			echo "
				<table border='1'>
					<tr>
						<th></th>
						<th>Lot Number</th>
						<th>Item Name</th>
						<th>Item Description</th>
						<th>Item Quantity</th>
						<th>Item Price</th>
						<th></th>
					</tr>
			";
			
			while($resultForPurchaseOrder = $queryForPurchaseOrder->fetch_assoc())
			{
				$listId = $resultForPurchaseOrder['listId'];
				$lotNumber = $resultForPurchaseOrder['lotNumber'];
				$processRemarks = $resultForPurchaseOrder['processRemarks'];
				$itemName = $resultForPurchaseOrder['itemName'];
				$itemDescription = $resultForPurchaseOrder['itemDescription'];
				$itemQuantity = $resultForPurchaseOrder['itemQuantity'];
				$itemPrice = $resultForPurchaseOrder['itemPrice'];
				
				$removeButton = "<img onclick=\" location.href='".$_SERVER['PHP_SELF']."?key=".$key."&listId=".$listId."' \" src='/".v."/Common Data/Templates/images/close1.png' height='15'>";
				
				echo "
					<tr>
						<td>".++$count."</td>
						<td>".$lotNumber."</td>
						<td>".$itemName."</td>
						<td>".$itemDescription."</td>
						<td>".$itemQuantity."</td>
						<td>".$itemPrice."</td>
						<td>".$removeButton."</td>
					</tr>
				";
			}
			echo "</table>";
		}
		else
		{
			?>
			<script>
				parent.location.reload();
			</script>
			<?php
		}
		
		exit(0);
	}
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L4171', 'utf8', 0, 1);?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v;?>/Common Data/Templates/api.css">
	<script src="/<?php echo v; ?>/Common Data/Templates/api.js"></script>
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
	// createHeader('L4171','','gerald_purchaseOrderMakingList.php');
	$displayId = "L4171";
    $version = "";
    //~ $previousLink = "/V3/4-14%20Purchasing%20Software/raymond_purchasingSoftware.php";
    $previousLink = "gerald_poPreparationList.php";
    createHeader($displayId, $version, $previousLink);
?>
	<form action='gerald_purchaseOrderListExport.php' method='post' id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
    	
		<div class="api-top api-col api-left-buttons" style='width:30%'>
        <!--
			<button class='api-btn' onclick="location.href='gerald_updatePurchaseOrderMakingRemarks.php';" data-api-title='<?php echo displayText('L1054'); //UPDATE?>' <?php //echo toolTip('L1072');?>></button>
		-->
		</div>
		
		
		<div class="api-top api-col api-title" style='width:40%;'>
			<h2><?php //echo displayText('L1174');?></h2>
		</div>
<!--
		<form method="POST" action="rose_printOut.php" id='printFormId'></form>
-->
		<?php
			//~ if($_SESSION['idNumber']=='0346')
			if($_SESSION['idNumber']==true)
			{
				echo "<form method='POST' action='gerald_purchaseOrderStatus.php' id='printFormId'></form>";
			}
			else
			{
				echo "<form method='POST' action='rose_printOut.php' id='printFormId'></form>";
			}
		?>
		<div class="api-top api-col api-right-buttons" style='width:30%'>
				<?php
					if($_GET['country']==1)
					{
						?><button class='api-btn' onclick="location.href='gerald_purchaseOrderReview.php'" style='width:33%' data-api-title='<?php echo displayText('L1313', 'utf8', 0, 1);?>' <?php echo toolTip('L1313');?>></button><?php
					}
				?>
				<button class='api-btn' type='submit' value ='Print' title='Print' style='width:33%' name='Print' data-api-title='<?php echo "Print";?>' <?php echo "PRINT";?> form='printFormId'></button>
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
							$lotNumberArray = $totalPriceArray = $rose_SupplierAlias = $rose_Supplier = $rose_SupplierCurr = $rose_itemCountPerSupplierCurr = $rose_itemPricePerSupplierCurr = $rose_supplierIdBlock =  array();
							$count = 0;
							//$sql = "SELECT id, lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 1 ORDER BY targetFinish";
							$sql = "SELECT * FROM purchasing_forpurchaseorder ORDER BY supplierId, supplierType, poCurrency";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
								{
                                		$sqlA = "SELECT id FROM view_workschedule WHERE processCode = 597 AND lotNumber like'".$resultWorkSchedule['lotNumber']."' AND processRemarks LIKE '".$resultWorkSchedule['processRemarks']."'";
                            			$queryWorkScheduleA = $db->query($sqlA);
                            			if($queryWorkScheduleA AND $queryWorkScheduleA->num_rows > 0)
                           				{
                                        $rose_supplierIdBlock[]=$resultWorkSchedule['supplierId'];
                                        }
                                	
                                if(in_array($resultWorkSchedule['supplierId'], $rose_supplierIdBlock) and count($rose_supplierIdBlock)>0)
                                {
                                }
                                else
                                {
									$lotNumber = $resultWorkSchedule['lotNumber'];
									$supplierId = $resultWorkSchedule['supplierId'];
									$supplierType = $resultWorkSchedule['supplierType'];
									$poCurrency = $resultWorkSchedule['poCurrency'];
									$itemQuantity = $resultWorkSchedule['itemQuantity'];
									$itemPrice = $resultWorkSchedule['itemPrice'];
									
									$rose_Supplier[] = $supplierId."`".$supplierType;									
									$rose_SupplierCurr[$supplierId."`".$supplierType][] = $poCurrency;									
									$rose_itemCountPerSupplierCurr[$supplierId."`".$supplierType][$poCurrency][] = $lotNumber;									
									$rose_itemPricePerSupplierCurr[$supplierId."`".$supplierType][$poCurrency][] = ($itemQuantity*$itemPrice);	
                                }
								}
							}
							
							$rose_Supplier=array_values(array_unique($rose_Supplier));
							$rose_SupplierAlias2=array();
							$rose_SupplierAlias3=array();
							for($x=0;$x<count($rose_Supplier);$x++)
							{
								
								$rose_explodeSupplier=explode("`",$rose_Supplier[$x]);
								if($rose_explodeSupplier[1]==1)
								{
									$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$rose_explodeSupplier[0]." LIMIT 1";
								}
								else if($rose_explodeSupplier[1]==2)
								{
									$sql = "SELECT subconAlias FROM purchasing_subcon WHERE subconId = ".$rose_explodeSupplier[0]." LIMIT 1";
								}
								if($sql!='')
								{
									$querySupplier = $db->query($sql);
									if($querySupplier AND $querySupplier->num_rows > 0)
									{
										$resultSupplier = $querySupplier->fetch_row();
										$rose_SupplierAlias[$rose_Supplier[$x]] = $resultSupplier[0];
										$rose_SupplierAlias2[]=$resultSupplier[0];
										$rose_SupplierAlias3[$resultSupplier[0]]=$rose_Supplier[$x];
									}
								}
								//echo "Rose".$rose_Supplier[$x].", ".$resultSupplier[0]."<br>";
							}
							
							sort($rose_SupplierAlias2);
							for($x=0;$x<count($rose_SupplierAlias2);$x++)
							{
								
								$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]]=array_values(array_unique($rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]]));
								for($y=0;$y<count($rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]]);$y++)
								{								
									// for($z=0;$z<count($rose_itemCountPerSupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y]]);$z++)
									// {
										 // echo "Rose".$rose_SupplierAlias2[$x]."<br>";
										// echo "=".$rose_SupplierAlias2[$x]."<br>";
										// echo "COUNT=".count($rose_itemCountPerSupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y]])."<br>";
										// echo "SUM=".array_sum($rose_itemPricePerSupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y]])."<br>";
										$currency=$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y];
										$totalAmount=array_sum($rose_itemPricePerSupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y]]);
										$sign = '';
										if($currency == 1)	$sign = '$';
										else if($currency == 2)	$sign = 'Php';
										else if($currency == 3)	$sign = ($_GET['country']==2) ? '¥' : '?';
										
										echo "
										<tr class='internalTrClass'>";                                	
										echo	"<td>".++$count."<input type='checkbox' name='formCheck[]' value='".$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]."-".$currency."' checked form='printFormId'></td>";
										
										echo	"<td>".$rose_SupplierAlias2[$x]."</td>
											<td align='right'>".$sign." ".number_format($totalAmount,2)."</td>
											<td align='right'><span data-key='".$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]."-".$currency."' class='quantityClass' style='color:blue;cursor:pointer;text-decoration:underline;'>".count($rose_itemCountPerSupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$rose_SupplierCurr[$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]][$y]])."</span></td>
											<td><img class='inputForm' src='/Common Data/Templates/images/print.png' height='20' name='".$rose_SupplierAlias3[$rose_SupplierAlias2[$x]]."-".$currency."' style='cursor:pointer;'></td>
										</tr>
										";
									// }
									
								}
							}
							
						?>						
					</tbody>
					<tfoot>
						<tr>
							<th><input type="checkbox" onClick="toggle(this)" /> Toggle All<br/></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
<!--
				</form>
-->
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
<script src="/<?php echo v; ?>/Common Data/Templates/jquery.js"></script>
<script src="/<?php echo v; ?>/Common Data/Templates/api.jquery.js"></script>
<script>
	$(function(){
		$("#mainTableId").apiFixedTableHeader();
		
		$('img.inputForm').click(function(){
			var src = "<?php echo ($_GET['country']=='2') ? 'rose_purchaseOrderConverterJapan.php' : 'gerald_purchaseOrderConverter.php';?>";
			$("iframe[name=boxName]").attr('src',src+'?key='+$(this).attr('name'));
			document.getElementById('modal01').style.display='block';
		});
		
		$('span.quantityClass').click(function(){
			$("iframe[name=boxName]").attr('src','gerald_purchaseOrderMakingSummary.php?key='+$(this).data('key'));
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
	;
</script>
<!-- -----------------------------------Tiny Box------------------------------------------------------------- -->
<script type="text/javascript" src="/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script type="text/javascript">
function toggle(source) {
  checkboxes = document.getElementsByName('foo');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
}
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
