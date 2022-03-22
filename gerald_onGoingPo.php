<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	$javascriptLib = "/".v."/Common Data/Libraries/Javascript/";
	$templates = "/".v."/Common Data/Templates/";
	set_include_path($path);	
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
	
	echo "
	<div id='tableDiv' style='width:100%;'>
		<table border='1' id='tableId'>
			<tr>
				<th></th>
				<th>".displayText('L224')."</th>
				<th>".displayText('L1049')."</th>
				<th>".displayText('L367')."</th>
				<th>".displayText('L172')."</th>
			</tr>
	";
	$sql = "SELECT poNumber, supplierId, supplierType, checkedBy, approvedBy FROM purchasing_podetailsnew WHERE poStatus = 0 AND supplierType != 0";
	$querySubPo = $db->query($sql);
	if($querySubPo->num_rows > 0)
	{
		while($resultSubPo = $querySubPo->fetch_array())
		{
			$poNumber = $resultSubPo['poNumber'];
			$supplierId = $resultSubPo['supplierId'];
			$supplierType = $resultSubPo['supplierType'];
			$checkedBy = $resultSubPo['checkedBy'];
			$approvedBy = $resultSubPo['approvedBy'];
			
			$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
			$queryPoContent = $db->query($sql);
			$poQty = $queryPoContent->num_rows;
			
			$supplierAlias = '';
			$sql = '';
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
					$resultSupplier = $querySupplier->fetch_array();
					$supplierAlias = $resultSupplier[0];
				}
			}
			
			$poStatus = '';
			if($checkedBy=='')			$poStatus = 'For Checking';
			else if($approvedBy=='')	$poStatus = 'For Approval';
			else						$poStatus = 'For Printing';
			
			echo "
				<tr>
					<td>".++$count."</td>
					<td><a href='gerald_purchaseOrderStatus.php?poNumber=".$poNumber."'>".$poNumber."</a></td>
					<td>".$poQty."</td>
					<td>".$supplierAlias."</td>
					<td>".$poStatus."</td>
				</tr>
			";
		}
	}
	echo "</div>";
?>
