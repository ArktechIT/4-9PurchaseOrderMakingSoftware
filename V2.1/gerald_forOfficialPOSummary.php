<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);    
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	include("PHP Modules/rose_prodfunctions.php");
	ini_set("display_errors", "on");
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='mergeSubPo')
		{
			$mainPo = $_POST['mainPo'];
			
			$sql = "
				INSERT INTO purchasing_podetailsnew
					(	`poNumber`,		`supplierId`, `supplierType`, `supplierAlias`, `poTerms`, `poShipmentType`, `poIncharge`, `poIssueDate`,`sendingDate`, `poTargetReceiveDate`,	`poSignatoryID`, `poRemarks`, `poRemarksOne`, `poRemarksTwo`, `poRemarksThree`, `poStatus`, `poType`, `poCurrency`, `poDiscount`, `poInputDateTime`, `checkedBy`, `approvedBy`, `emailDate`, `cancelEmailDate`, `vatFlag`)
				SELECT	'".$mainPo."',	`supplierId`, `supplierType`, `supplierAlias`, `poTerms`, `poShipmentType`, `poIncharge`, NOW(), 		`sendingDate`, '0000-00-00', 			`poSignatoryID`, `poRemarks`, `poRemarksOne`, `poRemarksTwo`, `poRemarksThree`, `poStatus`, `poType`, `poCurrency`, `poDiscount`, `poInputDateTime`, `checkedBy`, `approvedBy`, `emailDate`, `cancelEmailDate`, `vatFlag`
				FROM purchasing_podetailsnew WHERE poNumber LIKE '".$mainPo."-%' AND poStatus != 2 LIMIT 1
			";
			$queryInsert = $db->query($sql);
			
			$sql = "UPDATE purchasing_pocontents SET itemRemarks = poNumber, poNumber = '".$mainPo."' WHERE poNumber LIKE '".$mainPo."-%'";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE accounting_payablesnew SET poNumber = '".$mainPo."' WHERE poNumber LIKE '".$mainPo."-%'";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE purchasing_podetailsnew SET poStatus = 2 WHERE poNumber LIKE '".$mainPo."-%' AND poStatus != 2";
			$queryUpdate = $db->query($sql);
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
                   
	$displayId = "L4171";
	$version = ": OFFICIAL PO";
	$previousLink = "gerald_purchaseOrderMakingSummary.php";
	createHeader($displayId, $version, $previousLink);
?>
<form method='POST' action='<?php echo ($_GET['country']==2) ? 'rose_printOut.php' : 'gerald_purchaseOrderStatus.php';?>' id='printFormId'></form>
<div class='container-fluid'>
    <div class='row w3-padding-top'> <!-- row 1 -->
        <div class='col-md-12'>
			<div class='w3-right'>
            <!-- Code Here.. -->
			<?php
				//~ if($_GET['country']==1)
				//~ {
					//~ echo $purchaseReviewButton;
				//~ }
				
				echo $refreshButton;
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
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L224');?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo "PO COUNT";?></th>
                    <th class='w3-center' style='vertical-align:middle;'></th>
				</thead>
				<tbody class='w3-center'>
					<?php
						$count = 0;
						$sql = "SELECT SUBSTRING_INDEX(poNumber,'-',1) as mainPo, GROUP_CONCAT(poNumber) as poNumbers, COUNT(poNumber) as poCount, supplierAlias, supplierId, supplierType, poCurrency FROM purchasing_podetailsnew WHERE poNumber LIKE '%-%' AND poStatus != 2 GROUP BY mainPo";
						$queryPoDetailsNew = $db->query($sql);
						if($queryPoDetailsNew AND $queryPoDetailsNew->num_rows > 0)
						{
							while($resultPoDetailsNew = $queryPoDetailsNew->fetch_assoc())
							{
								$mainPo = $resultPoDetailsNew['mainPo'];
								$poNumbers = $resultPoDetailsNew['poNumbers'];
								$supplierAlias = $resultPoDetailsNew['supplierAlias'];
								$supplierId = $resultPoDetailsNew['supplierId'];
								$supplierType = $resultPoDetailsNew['supplierType'];
								$poCurrency = $resultPoDetailsNew['poCurrency'];
								$poCount = $resultPoDetailsNew['poCount'];
								
								$printButton = $tpl->setDataValue("L1201")
											   ->setAttribute([
													"name"  => "",
													"id"    => "",
													"type"  => "submit",
													"form"  => "printFormId"
											   ])
											   ->addClass("") // Optional
											   ->createButton();
								
								$finishButton = $tpl->setDataValue("L1582")
											   ->setAttribute([
													"data-main-po"  => $mainPo
											   ])
											   ->addClass("finishClass") // Optional
											   ->createButton();
																
								
								$pdfButton = $tpl->setDataValue("L1385")
											   ->setAttribute([
													"data-main-po"  => $mainPo
											   ])
											   ->addClass("inputForm") // Optional
											   ->createButton();
																
								
								//~ $pdf = "<img class='inputForm' src='/Common Data/Templates/images/print.png' height='20' data-main-po='".$mainPo."' style='cursor:pointer;'>";
								
								echo "
									<tr>
										<td>".++$count."</td>
										<td>".$supplierAlias."</td>
										<td>".$mainPo."</td>
										<td>".$poCount."</td>
										<td>".$pdfButton.$finishButton."</td>
									</tr>
								";
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
	
	$('.inputForm').click(function(){
		var mainPo = $(this).data('mainPo');
		var src = "<?php echo ($_GET['country']=='2') ? 'rose_purchaseOrderConverterJapan.php' : 'gerald_purchaseOrderConverterV2.php';?>";
		//~ $("iframe[name=boxName]").attr('src',src+'?key='+$(this).attr('name'));
		//~ document.getElementById('modal01').style.display='block';
		
		viewPDF(src+'?poNumber='+mainPo+'-%');
	});
	
	$('.finishClass').click(function(){
		var mainPo = $(this).data('mainPo');
		
		$.ajax({
			url:"<?php echo $_SERVER['PHP_SELF'];?>",
			type:"post",
			data:{
				ajaxType:'mergeSubPo',
				mainPo:mainPo
			},
			success:function(data){
				location.reload();
			}
		});
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
