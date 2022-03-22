<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	require_once('Libraries/PHP/TCPDF-master/tcpdf.php');
	ini_set("display_errors", "on");
	
	if(isset($_GET['choicesFlag']))
	{
		$poNumber = $_GET['poNumber'];
		$poContentIdArray = array();
		$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND itemStatus != 2";
		$queryPoContents = $db->query($sql);
		if($queryPoContents AND $queryPoContents->num_rows > 0)
		{
			while($resultPoContents = $queryPoContents->fetch_assoc())
			{
				$poContentIdArray[] = $resultPoContents['poContentId'];
			}
		}
		
		echo "
			<form action='".$_SERVER['PHP_SELF']."?poNumber=".$poNumber."' method='post' id='itemTagFormId'></form>
			<table border='1'>
				<tr>
					<th><input type='checkbox' id='checkAll' checked></th>
					<th>".displayText("L45")."</th>
					<th>".displayText("L4175")."</th>
				</tr>
		";
		$sql = "SELECT lotNumber, workingQuantity FROM ppic_lotlist WHERE poId IN(".implode(",",$poContentIdArray).") AND workingQuantity > 0 AND identifier = 4";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$workingQuantity = $resultLotList['workingQuantity'];
				
				echo "
					<tr>
						<td><input type='checkbox' class='checkClass' name='lotNumberArray[]' value='".$lotNumber."' checked form='itemTagFormId'></td>
						<td>".$lotNumber."</td>
						<td>".$workingQuantity."</td>
					</tr>
				";
			}
		}
		else
		{
			/*
			$sql = "SELECT GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ',') as poContentIds FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND itemStatus != 2 GROUP BY lotNumber";
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows > 0)
			{
				while($resultPoContents = $queryPoContents->fetch_assoc())
				{
					$poContentIds = $resultPoContents['poContentIds'];
					
					echo "<br>".$sql = "SELECT lotNumber, workingQuantity FROM ppic_lotlist WHERE poContentId LIKE '".$poContentIds."' AND workingQuantity > 0 AND identifier = 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumber = $resultLotList['lotNumber'];
							$workingQuantity = $resultLotList['workingQuantity'];
							
							echo "
								<tr>
									<td><input type='checkbox' class='checkClass' name='lotNumberArray[]' value='".$lotNumber."' checked form='itemTagFormId'></td>
									<td>".$lotNumber."</td>
									<td>".$workingQuantity."</td>
								</tr>
							";
						}
					}
				}
			}*/
			
			$sql = "SELECT GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ',') as poContentIds, SUBSTRING_INDEX( lotNumber, '-', 3 ) as mainLot FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND itemStatus != 2 GROUP BY lotNumber";
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows > 0)
			{
				while($resultPoContents = $queryPoContents->fetch_assoc())
				{
					$poContentIds = $resultPoContents['poContentIds'];
					$mainLot = $resultPoContents['mainLot'];
					
					$sql = "SELECT lotNumber, workingQuantity FROM ppic_lotlist WHERE poContentId LIKE '%".$poContentIds."%' AND (lotNumber LIKE '".$mainLot."' OR lotNumber LIKE '".$mainLot."-%') AND workingQuantity > 0 AND identifier = 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumber = $resultLotList['lotNumber'];
							$workingQuantity = $resultLotList['workingQuantity'];
							
							echo "
								<tr>
									<td><input type='checkbox' class='checkClass' name='lotNumberArray[]' value='".$lotNumber."' checked form='itemTagFormId'></td>
									<td>".$lotNumber."</td>
									<td>".$workingQuantity."</td>
								</tr>
							";
						}
					}
				}
			}
		}		
		echo "
			<tr>
				<th colspan='3'><input type='submit' form='itemTagFormId' value='".displayText("B1","utf8",0,0,1)."'></th>
			</tr>
		";
		echo "</table>";
		?>
		<script src='/<?php echo v;?>/Common Data/Templates/jquery.js'></script>
		<script>
			$(function(){
				$("#checkAll").change(function(){
					if($(this).is(':checked'))
					{
						$(".checkClass").prop('checked', true);
					}
					else
					{
						$(".checkClass").prop('checked', false);
					}
				});
			});
		</script>
		<?php
		exit(0);
	}
	
	$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);//210, 297
	
	$paperWidth = 210;
	$paperLength = 297;
	$left = 5;
	$top = 5;
	$cols = 2;
	$rows = 3;
	
	$boxWidth = ($paperWidth / $cols);
	$boxLength = ($paperLength / $rows);
	
	$poNumber = $_GET['poNumber'];
	$lotNumberGet = (isset($_GET['lotNumber'])) ? $_GET['lotNumber'] : '';
	$saveFileFlag = (isset($_GET['saveFile']) AND $_GET['saveFile']==1) ? 1 : 0;

	$style = array('align' => 'C','stretch' => false,'text' => true,'font' => 'helvetica','fontsize' => 8,'stretchtext' => 0);
	
	$arkLogo = '/'.v.'/Common Data/Templates/images/arkLogo.jpg';

	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	
	$pdf->SetLineStyle(array('dash' => 0));
	$pdf->SetFont('Helvetica','',9);
	$pdf->SetAutoPageBreak(false, 0);
	
	$y = $top;
	$w = $boxWidth - ($top * 2);
	$h = 7;
	
	$sql = "SELECT supplierId, supplierType FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
		$supplierId = $resultPodetailsNew['supplierId'];
		$supplierType = $resultPodetailsNew['supplierType'];
	}	
	
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
	
	if(isset($_POST['lotNumberArray']))
	{
		$lotNumberArray = $_POST['lotNumberArray'];
	}
	else
	{
		$lotNumberArray = array();
		
		if($lotNumberGet!='')
		{
			$lotNumberArray[] = $lotNumberGet;
		}
		else
		{
			$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
			$queryPoContentsLot = $db->query($sql);
			if($queryPoContentsLot->num_rows > 0)
			{
				while($resultPoContentsLot = $queryPoContentsLot->fetch_array())
				{
					$lotNumberArray[] = $resultPoContentsLot['lotNumber'];
				}
			}	
		}
	}
	
	$index = 0;
	
	if(count($lotNumberArray) > 0)
	{
		foreach($lotNumberArray as $lotNumber)
		{
			$poContentIds = $filterPoContentId = $workingQuantity = '';
			$sql = "SELECT poId, identifier, poContentId, workingQuantity FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$poId = $resultLotList['poId'];
				$identifier = $resultLotList['identifier'];
				$poContentId = $resultLotList['poContentId'];
				$workingQuantity = $resultLotList['workingQuantity'];
				
				if($identifier==1)
				{
					$poContentIds = $poContentId;
				}
				else if($identifier==4)
				{
					$poContentIds = $poId;
				}
				$filterPoContentId = " AND poContentId IN(".$poContentIds.")";
			}			
			
			$partNumberArray = $treatmentProcessArray = array();
			$itemName = $itemDescription = '';
			$itemQuantity = 0;
			//~ $sql = "SELECT `poContentId`, `itemName`, `itemDescription`, `itemQuantity` FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND lotNumber LIKE '".$lotNumber."'";
			$sql = "SELECT `poContentId`, `itemName`, `itemDescription`, `itemQuantity` FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' ".$filterPoContentId;
			$queryPoContents = $db->query($sql);
			if($queryPoContents AND $queryPoContents->num_rows > 0)
			{
				while($resultPoContents = $queryPoContents->fetch_assoc())
				{
					$poContentId = $resultPoContents['poContentId'];
					$itemName = $resultPoContents['itemName'];
					$itemDescription = $resultPoContents['itemDescription'];
					$itemQuantity = $resultPoContents['itemQuantity'];
					
					if($supplierType==2)
					{
						$partNumberString = $itemDescription;
						$treatmentProcessArray[] = $itemName;
					}
				}
			}
			
			$itemQuantity = $workingQuantity;
			
			if(($index%6) == 0)
			{
				$pdf->AddPage();
				$pdf->SetLineStyle(array('dash' => '2'));
				// - - - - - - - - - - - - - - - - - - - - Creating Dash Lines - - - - - - - - - - - - - - - - - - - - //
				$counter = $boxWidth;
				while($counter < $paperWidth)
				{
					$pdf->Line($counter,0,$counter,$paperLength); //Horizontals
					$counter += $boxWidth;
				}
				
				$counter = $boxLength;
				while($counter < $paperLength)
				{
					$pdf->Line(0,$counter,$paperWidth,$counter); //Horizontals
					$counter += $boxLength;
				}
				// - - - - - - - - - - - - - - - - - - - End Creating Dash Lines - - - - - - - - - - - - - - - - - - - //
				$pdf->SetLineStyle(array('dash' => 0));
				$y = $top;
				$plusY = 0;
			}
			
			if(($index%2) == 0)
			{
				$x = $left;
				$y += $plusY;
				$w = $boxWidth - ($top * 2);
				
				$logoX = 65;
				$logoY = 16;
				
				$plusY = $boxLength;
			}
			else
			{
				$x = $left + $boxWidth;
			}
			
			// - - - - - - - - - - - - - - - - - ITEM TAG - - - - - - - - - - - - - - - //
			$pdf->Image($arkLogo,$x+$logoX,$y,($w)/3,'');//Logo
			
			$pdf->SetXY($x,$y);
			$pdf->SetFont('Helvetica','B',18);
			if($_GET['country']=='2')	$pdf->SetFont('Helvetica','B',12);
			$pdf->Cell(25,5,$poNumber,0,0,'C');
			
			$pdf->SetXY($x,$y);
			$pdf->SetFont('Helvetica','B',21);
			$pdf->Cell($w,8,'ITEM TAG  ',0,0,'C');$pdf->Ln(11);
			$pdf->write1DBarcode($lotNumber, 'C39', $x, '', $w, 10, 0.4, $style, 'T');
			
			$pdf->SetFont('Helvetica','',12);
			//~ $pdf->SetXY($x,$y+23);	$pdf->Cell(($w/4),$h,'Item Type','LTB',0,'L');		$pdf->Cell(($w/1.334),$h,$poType,'LTBR',0,'C'); 	$pdf->SetFont('Helvetica','',12); $pdf->ln();
			$pdf->SetXY($x,$y+23);
			
			if($_GET['country']=='2')	$pdf->SetFont('kozminproregular', '', 12);
			
			if($supplierType==1)
			{
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Supplier','LTB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$supplierAlias,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Item Name','LB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$itemName,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),($h*3),'Description','LB',0,'L');
				$pdf->MultiCell(($w/1.334),($h*3),$itemDescription,1,'C',false,0,'','',true,0,false,true,($h*3),'M',true);$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Lot No. ','LB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$lotNumber,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
			}
			else if($supplierType==2)
			{
				$treatmentProcessString = implode("\n",$treatmentProcessArray);
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Subcon','LTB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$supplierAlias,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Part No. ','LB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$partNumberString,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				
				# RG
				if($_SESSION['idNumber'] == true)
				{
					$partNote = "";
					$sql = "SELECT partNote FROM system_lotlist WHERE lotNumber = '".$lotNumber."'";
					$queryPartNote = $db->query($sql);
					if($queryPartNote AND $queryPartNote->num_rows > 0)
					{
						$resultPartNote = $queryPartNote->fetch_assoc();
						$partNote = $resultPartNote['partNote'];
					}

					$height = 3;
					if($partNote != "")
					{
						$pdf->SetX($x);
						$pdf->Cell(($w/4),$h,'Part Note ','LB',0,'L');
						$pdf->MultiCell(($w/1.334),$h,$partNote,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
						$height = 2.5;
						
					}
				}
				# RG END

				$pdf->SetX($x);
				$pdf->Cell(($w/4),($h*$height),'Treatment','LB',0,'L');
				$pdf->MultiCell(($w/1.334),($h*$height),$treatmentProcessString,1,'C',false,0,'','',true,0,false,true,($h*$height),'M',true);$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Lot No. ','LB',0,'L');
				$pdf->MultiCell(($w/1.334),$h,$lotNumber,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
			}
			
			if($poNumber=='0008201')
			{
				$itemQuantity = '50 rolls/box';
				//~ $unitName = '';
			}			
			
			$getY= $pdf->GetY();
			$pdf->SetXY($x,$getY+5);
			$pdf->SetFont('Helvetica','',10);
			$pdf->Cell(($w/3.3),$h,'PO Quantity',1,0,'C');
			$pdf->Cell(($w/3.3),$h,'Actual Quantity',1,0,'C');
			$pdf->ln();$pdf->SetX($x);
			$pdf->SetFont('Helvetica','',20);
			$pdf->Cell(($w/3.3),($h-1)*2,$itemQuantity,1,0,'C');
			$pdf->Cell(($w/3.3),($h-1)*2,'',1,0,'C');
			
			$pdf->SetXY($x+66,$getY+5);
			$pdf->SetFont('Helvetica','',10);
			$pdf->Cell(($w/3.3),$h,'Quality Stamp',1,0,'C');
			$pdf->ln();$pdf->SetX($x+66);
			$pdf->SetFont('Helvetica','',20);
			$pdf->Cell(($w/3.3),($h-1)*2,'',1,0,'C');
			// - - - - - - - - - - - - - - - - - END ITEM TAG - - - - - - - - - - - - - - - //
			
			$index++;
		}
	}
	
	if($saveFileFlag==1)
	{
		$attachmentFile = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/Email Attachment/".$poNumber."-2.pdf";
		$pdf->Output($attachmentFile, 'F');
		
		header('location:gerald_mergeEmailAttachment.php?poNumber='.$poNumber);
	}
	else
	{
		$pdf->Output();
	}
?>
