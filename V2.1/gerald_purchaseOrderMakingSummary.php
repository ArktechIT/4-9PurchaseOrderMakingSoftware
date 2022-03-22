<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);    
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	include("PHP Modules/rose_prodfunctions.php");
	ini_set("display_errors", "on");
	
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
						<th>".displayText('L45')."</th>
						<th>".displayText('L246')."</th>
						<th>".displayText('L247')."</th>
						<th>".displayText('L4175')."</th>
						<th>".displayText('L1247')."</th>
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
				//~ location.reload();
				location.href="<?php echo $_SERVER['PHP_SELF'];?>";
			</script>
			<?php
		}
		
		exit(0);
	}	
	
	$ctrl = new PMSDatabase;
	$tpl = new PMSTemplates;

	$title = displayText('L4171','utf8',0,1,1);
	PMSTemplates::includeHeader($title);

    $refreshButton = $tpl->setDataValue("L436")
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "",
                        "type"  => "",
                        "onclick"  => "location.href=''"
                   ])
                   ->addClass("") // Optional
                   ->createButton();
                   
    $printButton = $tpl->setDataValue("L1201")
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "",
                        "type"  => "submit",
                        "form"  => "printFormId"
                   ])
                   ->addClass("") // Optional
                   ->createButton();
                       
    $purchaseReviewButton = $tpl->setDataValue(displayText('L1313', 'utf8', 0, 1))
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "",
                        "type"  => "",
                        "onclick"  => "location.href='gerald_purchaseOrderReview.php'"
                   ])
                   ->addClass("w3-btn") // Optional
                   ->addClass("w3-round") // Optional
                   ->addClass("w3-dark-grey") // Optional
                   ->createButton();
                   
    $officialPOButton = $tpl->setDataValue('PRINT OFFICIAL PO')
                   ->setAttribute([
                        "name"  => "",
                        "id"    => "",
                        "type"  => "",
                        "onclick"  => "location.href='gerald_forOfficialPOSummary.php'"
                   ])
                   ->addClass("w3-btn") // Optional
                   ->addClass("w3-round") // Optional
                   ->addClass("w3-dark-grey") // Optional
                   ->createButton();
        

	$displayId = "L4171";
	$version = "";
	$previousLink = "gerald_poPreparationList.php";
	createHeader($displayId, $version, $previousLink);
?>
<form method='POST' action='<?php echo ($_GET['country']==2) ? 'rose_printOut.php' : 'gerald_purchaseOrderStatus.php';?>' id='printFormId'></form>
<div class='container-fluid'>
    <div class='row w3-padding-top'> <!-- row 1 -->
        <div class='col-md-12'>
			<div class='w3-right'>
            <!-- Code Here.. -->
			<?php
				if($_GET['country']==1)
				{
					echo $purchaseReviewButton.$officialPOButton;
				}
				
				echo $printButton.$refreshButton;
			?>   
			</div>  
        </div>
    </div>
    <div class='row w3-padding-top'>  <!-- row 2 -->
        <div class='col-md-12'>
            <!-- TABLE TEMPLATE -->
<!--
            <label><?php echo displayText("L41", "utf8", 0, 0, 1)." : ". $totalRecords; ?></label>
-->
			<table id='mainTableId' class="table table-bordered table-striped table-condensed">
				<thead class='w3-indigo' style='text-transform:uppercase;'>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L843');?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L367');?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L743');?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L1049');?></th>
                    <th class='w3-center' style='vertical-align:middle;'></th>
				</thead>
				<tbody class='w3-center'>
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
				<tfoot class='w3-indigo' >
                    <tr>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                    </tr>
				</tfoot>
			</table>
        </div>
    </div>
</div>
<div id='modal-izi'><span class='izimodal-content'></span></div>
<div id='modal-izi-pdf'><span class='izimodal-content-pdf'></span></div>
<?php
PMSTemplates::includeFooter();
?>
<script>
function viewPDF(link)
{
    //alert(link);
    $("#modal-izi-pdf").iziModal({
        title                   : '<i class="fa fa-file"></i> PDF',
        headerColor             : '#1F4788',
        subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
        width                   : 1200,
        fullscreen              : false,
        iframe                  : true,
        iframeURL               : link,
        iframeHeight            : 500,
        transitionIn            : 'comingIn',
        transitionOut           : 'comingOut',
        padding                 : 20,
        radius                  : 0,
        top                     : 100,
        restoreDefaultContent   : true,
        closeOnEscape           : true,
        closeButton             : true,
        overlayClose            : false,
        onOpening               : function(modal){

                                },
            onClosed            : function(modal){
                                    $("#modal-izi-pdf").iziModal("destroy");
                    }
    });

    $("#modal-izi-pdf").iziModal("open");
}	
	
// script here
$(document).ready(function(){
	var dataTable = $('#mainTableId').DataTable( {
		"processing"    : true,
		"ordering"      : true,
		"searching"     : false,
		"bInfo" 		: false,
		fixedColumns:   {
				leftColumns: 0
		},
		scrollY     	: 600,
		scrollX     	: false,
		scrollCollapse	: false,
		scroller    	: {
			loadingIndicator    : true
		},
		stateSave   	: false
	});	
	
	$('img.inputForm').click(function(){
		var key = $(this).attr('name');
		var src = "<?php echo ($_GET['country']=='2') ? 'rose_purchaseOrderConverterJapan.php' : 'gerald_purchaseOrderConverter.php';?>";
		//~ $("iframe[name=boxName]").attr('src',src+'?key='+$(this).attr('name'));
		//~ document.getElementById('modal01').style.display='block';
		
		viewPDF(src+'?key='+key);
	});
	
	$('span.quantityClass').click(function(){
		var key = $(this).data('key');
		
		$("#modal-izi").iziModal({
			title                   : ' ',
			headerColor             : '#1F4788',
			subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
			width                   : 600,
			fullscreen              : false,
			transitionIn            : 'comingIn',
			transitionOut           : 'comingOut',
			padding                 : 20,
			radius                  : 0,
			top                     : 10,
			restoreDefaultContent   : true,
			closeOnEscape           : true,
			closeButton             : true,
			overlayClose            : false,
			onOpening               : function(modal){
										modal.startLoading();
										$.ajax({
											url         : 'gerald_purchaseOrderMakingSummary.php?key='+key,
											type        : 'POST',
											data        : {
															
											},
											success     : function(data){
															$( ".izimodal-content" ).html(data);
															modal.stopLoading();
											}
										});
									},
				onClosed            : function(modal){
										$("#modal-izi").iziModal("destroy");
						}
		});

		$("#modal-izi").iziModal("open");		
	});	
});
</script>
