<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);    
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
include("PHP Modules/rose_prodfunctions.php");
ini_set("display_errors", "on");
$ctrl = new PMSDatabase;
$tpl = new PMSTemplates;
$pms = new PMSDBController;
$rdr = new Render\PMSTemplates;

$title = "";
PMSTemplates::includeHeader($title);

$tpl->setDisplayId("") # OPTIONAL
    ->setVersion("") # OPTIONAL
    ->setPrevLink("") # OPTIONAL
    ->setHomeIcon() # OPTIONAL 0 - Default; 1 - w/o home icon
    ->createHeader();
?>
<div class='container-fluid'>
    <div class='row w3-padding-top'>  <!-- row 2 -->
        <div class='col-md-12'>
            <!-- TABLE TEMPLATE -->
            <label><?php echo displayText("L41", "utf8", 0, 0, 1)." : ". $totalRecords; ?></label>
            			<table id='mainTableId' class="table table-bordered table-striped table-condensed">
				<thead class='w3-indigo' style='text-transform:uppercase;'>
                    <th class='w3-center' style='vertical-align:middle;'>HEADER 1</th>
                    <th class='w3-center' style='vertical-align:middle;'>HEADER 2</th>
                    <th class='w3-center' style='vertical-align:middle;'>HEADER 3</th>
                    <th class='w3-center' style='vertical-align:middle;'>HEADER 4</th>
                    <th class='w3-center' style='vertical-align:middle;'>HEADER 5</th>
				</thead>
				<tbody class='w3-center'>
					
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
<?php
PMSTemplates::includeFooter();
?>
<script>
// script here
$(document).ready(function(){
    var sqlData = "<?php echo $sqlData; ?>";
    var totalRecords = "<?php echo $totalRecords; ?>";
    var dataTable = $('#mainTableId').DataTable( {
		"searching"     : false,
		"processing"    : true,
		"ordering"      : false,
		"serverSide"    : true,
		"bInfo"         : false,
		"ajax"          : {
                url     : "ajax url here...", // json datasource
                type    : "POST",  // method  , by default get
                data    : {
                            "sqlData"           : sqlData, // SQL Query POST
                            "totalRecords"      : totalRecords
                },
                error   : function(){  // error handling
                            $(".mainTableId-error").html("");
                            $("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                            $("#mainTableId_processing").css("display","none");
                }
		},
        "createdRow"    : function( row, data, index ) {
                            $(row).addClass("w3-hover-dark-grey rowClass");
                            $(row).click(function(){
                                $(".rowClass").removeClass("w3-deep-orange");
                                $(this).addClass("w3-deep-orange");
                            });
        },
		"columnDefs"    : [
		
        ],
		fixedColumns    : true,
		deferRender     : true,
		scrollY         : 530,
		scrollX         : true,
		scroller        : {
			loadingIndicator    : true
		},
		stateSave       : false
	});
});
</script>
