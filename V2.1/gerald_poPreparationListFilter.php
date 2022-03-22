<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");

	$sqlData = (isset($_POST['sqlData'])) ? $_POST['sqlData'] : '';
	$postVariable = (isset($_POST['postVariable'])) ? $_POST['postVariable'] : '';
	if($postVariable!='')
	{
		$postVariable = str_replace("'",'"',$postVariable);
		$_POST = json_decode($postVariable,true);
	}
	
	$lotNumber = (isset($_POST['lotNumber'])) ? $_POST['lotNumber'] : '';
	$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
	$supplyType = (isset($_POST['supplyType'])) ? $_POST['supplyType'] : '';
	$supplierId = (isset($_POST['supplierId'])) ? $_POST['supplierId'] : '';
	$itemName = (isset($_POST['itemName'])) ? $_POST['itemName'] : '';
	$itemDescription = (isset($_POST['itemDescription'])) ? $_POST['itemDescription'] : '';
	$desc = (isset($_POST['desc'])) ? $_POST['desc'] : '';
	
	$fromSql = strstr($sqlData,'FROM');
	$fromSql = strstr($fromSql,'ORDER BY',true);
	
	$lotNumberArray = $dataArray = array();
	$sql = "SELECT DISTINCT lotNumber ".$fromSql." ORDER BY lotNumber";
	$query = $db->query($sql);
	if($query AND $query->num_rows > 0)
	{
		while($result = $query->fetch_assoc())
		{
			$lotNumberArray[] = $result['lotNumber'];
		}
	}

	$processRemarksArray = Array();
	$sql = $sqlData;
	$query = $db->query($sql);
	if($query AND $query->num_rows > 0)
	{
		while($result = $query->fetch_assoc())
		{
			$workSchedId = $result['id'];
			$processRemarks = $result['processRemarks'];
			$targetFinish = $result['targetFinish'];
			
			$processRemarksArray[] = $processRemarks;
		}
	}

	
	//~ echo $sql = "SELECT DISTINCT supplierId FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 2";
	
	echo "<div class='row'>";
		echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>".displayText('L45')."</label>";
			echo "<input type='text' class='w3-input w3-border' name='lotNumber' list='lotNumber' value='".$lotNumber."' form='formFilter'>";
			echo "<datalist id='lotNumber'>";
				$sql = "SELECT DISTINCT lotNumber ".$fromSql." ORDER BY lotNumber";
				$query = $db->query($sql);
				if($query AND $query->num_rows > 0)
				{
					while($result = $query->fetch_assoc())
					{
						$selected = ($lotNumber==$result['lotNumber']) ? 'selected' : '';
						echo "<option value='".$result['lotNumber']."' ".$selected.">".$result['lotNumber']."</option>";
					}
				}
			echo "</datalist>";
		echo "</div>";
		echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>DESCRIPTION</label>";
			echo "<input type='list' class='w3-input w3-border' name='desc' list='desc' value='".$desc."' form='formFilter' autocomplete='off'>";
			echo "<datalist id='desc'>";
				echo "<option></option>";
				$processRemarksArray = array_unique($processRemarksArray);
				sort($processRemarksArray);
				foreach ($processRemarksArray as $key) 
				{
					// $selectedDesc = ($key == $desc) ? "selected" : "";
					echo "<option ".$selectedDesc." >".$key."</option>";
				}
			echo "</sdatalistelect>";
		echo "</div>";
		echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>".displayText('L111')."</label>";
			echo "<select class='w3-input w3-border' id='supplyType' name='supplyType' form='formFilter'>";
				echo "<option></option>";
				$supplyNameArray = array('Material','Subcon','Item','Accessory');
				foreach($supplyNameArray as $key => $valueCaption)
				{
					$valueColumn = ($key+1);
					
					$selected = ($supplyType==$valueColumn) ? 'selected' : '';
					
					echo "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
				}
				
			echo "</select>";
		echo "</div>";
		echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>".displayText('L367')."</label>";
			echo "<select class='w3-input w3-border' id='supplierId' name='supplierId' form='formFilter'>";
				echo "<option></option>";
				if($supplyType==2)
				{
					$supplierIdArray = array();
					$sql = "SELECT DISTINCT supplierId FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 2";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$supplierIdArray[] = $result['supplierId'];
						}
					}
					
					$sql = "SELECT subconId, subconAlias FROM purchasing_subcon WHERE subconId IN(".implode(",",$supplierIdArray).") ORDER BY subconAlias";
					$querySubcon = $db->query($sql);
					if($querySubcon AND $querySubcon->num_rows > 0)
					{
						while($resultSubcon = $querySubcon->fetch_assoc())
						{
							$valueColumn = $resultSubcon['subconId'];
							$valueCaption = $resultSubcon['subconAlias'];
							
							$selected = ($supplierId==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
						}
					}
				}
				else
				{
					$supplierIdArray = array();
					$sql = "SELECT DISTINCT supplierId FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 1";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$supplierIdArray[] = $result['supplierId'];
						}
					}
					
					$sql = "SELECT supplierId, supplierAlias FROM purchasing_supplier WHERE supplierId IN(".implode(",",$supplierIdArray).") ORDER BY supplierAlias";
					$querySupplier = $db->query($sql);
					if($querySupplier AND $querySupplier->num_rows > 0)
					{
						while($resultSupplier = $querySupplier->fetch_assoc())
						{
							$valueColumn = $resultSupplier['supplierId'];
							$valueCaption = $resultSupplier['supplierAlias'];
							
							$selected = ($supplierId==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
						}
					}
				}
				
			echo "</select>";
		echo "</div>";
		echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>".displayText('L1172')."</label>";
			echo "<input type='text' class='w3-input w3-border' name='itemName' list='itemName' value='".$itemName."' form='formFilter'>";
			echo "<datalist id='itemName'>";
				if($supplyType==2)
				{
					$sql = "SELECT DISTINCT itemName FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 2 ORDER BY itemName";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$valueColumn = $resultSubcon['itemName'];
							
							$selected = ($itemName==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueColumn."</option>";
						}
					}
				}
				else
				{
					$sql = "SELECT DISTINCT itemName FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 1 ORDER BY itemName";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$valueColumn = $resultSubcon['itemName'];
							
							$selected = ($itemName==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueColumn."</option>";
						}
					}
				}
			echo "</datalist>";
		echo "</div>";
						echo "<div class='col-md-2'>";
			echo "<label class='w3-tiny'>".displayText('L1173')."</label>";
			echo "<input type='text' class='w3-input w3-border' name='itemDescription' list='itemDescription' value='".$itemDescription."' form='formFilter'>";
			echo "<datalist id='itemDescription'>";
				if($supplyType==2)
				{
					$sql = "SELECT DISTINCT itemDescription FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 2 ORDER BY itemDescription";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$valueColumn = $resultSubcon['itemDescription'];
							
							$selected = ($itemDescription==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueColumn."</option>";
						}
					}
				}
				else
				{
					$sql = "SELECT DISTINCT itemDescription FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierType = 1 ORDER BY itemDescription";
					$query = $db->query($sql);
					if($query AND $query->num_rows > 0)
					{
						while($result = $query->fetch_assoc())
						{
							$valueColumn = $resultSubcon['itemDescription'];
							
							$selected = ($itemDescription==$valueColumn) ? 'selected' : '';
							
							echo "<option value='".$valueColumn."' ".$selected.">".$valueColumn."</option>";
						}
					}
				}
			echo "</datalist>";
		echo "</div>";
	echo "</div>";
		
	
	echo "<div class='w3-padding-top'></div>";
	echo "<div class='row w3-padding'>";
		echo "<div class='col-md-12 w3-center'>";
			echo "<button class='w3-btn w3-round w3-small w3-indigo' form='formFilter'><i class='fa fa-search'></i>&emsp;<b>".strtoupper(displayText("B5"))."</b></button>"; // SEARCH
		echo "</div>";
	echo "</div>";
?>
<script>
	$(document).ready(function(){
        $("input.cparTypeClass").dblclick(function(){
            var thisCheck = $(this)
            if(thisCheck.is(':checked'))
            {			
                if(confirm("Check only this?"))
                {
                    $("input.cparTypeClass").prop('checked',false);
                    thisCheck.prop('checked',true);
                }
            }
            else
            {
                if(confirm("Check all status?"))
                {
                    $("input.cparTypeClass").prop('checked',true);
                }
            }
        });
	});
</script>
