<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	require('Libraries/PHP/FPDF/fpdf.php');
	ini_set('display_errors','on');
	//require('PHP Modules/japanese.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/gerald_functions.php');
	
	class PDF extends FPDF
	{
		function waterMarkText($text)
		{
			$this->SetFont('Arial','B',50);
			$this->SetTextColor(128,128,128); //gray
			$this->SetAlpha(0.5);		
			$this->RotatedText(35,200,$text,46);
			$this->SetTextColor(0,0,0); //black
			$this->SetAlpha(1);			
		}		
		
		var $angle = 0;

		function Rotate($angle, $x = -1, $y = -1) {
			if ($x == -1)
				$x = $this->x;
			if ($y == -1)
				$y = $this->y;
			if ($this->angle != 0)
				$this->_out('Q');
			$this->angle = $angle;
			if ($angle != 0) {
				$angle*=M_PI / 180;
				$c = cos($angle);
				$s = sin($angle);
				$cx = $x * $this->k;
				$cy = ($this->h - $y) * $this->k;
				$this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
			}
		}

		function _endpage() {
			if ($this->angle != 0) {
				$this->angle = 0;
				$this->_out('Q');
			}
			parent::_endpage();
		}
		
		function RotatedText($x, $y, $txt, $angle) {
			//Text rotated around its origin
			$this->Rotate($angle, $x, $y);
			$this->Text($x, $y, $txt);
			$this->Rotate(0);
		}
		
		var $extgstates = array();

		// alpha: real value from 0 (transparent) to 1 (opaque)
		// bm:    blend mode, one of the following:
		//          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
		//          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
		function SetAlpha($alpha, $bm='Normal')
		{
			// set alpha for stroking (CA) and non-stroking (ca) operations
			$gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
			$this->SetExtGState($gs);
		}

		function AddExtGState($parms)
		{
			$n = count($this->extgstates)+1;
			$this->extgstates[$n]['parms'] = $parms;
			return $n;
		}

		function SetExtGState($gs)
		{
			$this->_out(sprintf('/GS%d gs', $gs));
		}

		function _enddoc()
		{
			if(!empty($this->extgstates) && $this->PDFVersion<'1.4')
				$this->PDFVersion='1.4';
			parent::_enddoc();
		}

		function _putextgstates()
		{
			for ($i = 1; $i <= count($this->extgstates); $i++)
			{
				$this->_newobj();
				$this->extgstates[$i]['n'] = $this->n;
				$this->_out('<</Type /ExtGState');
				$parms = $this->extgstates[$i]['parms'];
				$this->_out(sprintf('/ca %.3F', $parms['ca']));
				$this->_out(sprintf('/CA %.3F', $parms['CA']));
				$this->_out('/BM '.$parms['BM']);
				$this->_out('>>');
				$this->_out('endobj');
			}
		}

		function _putresourcedict()
		{
			parent::_putresourcedict();
			$this->_out('/ExtGState <<');
			foreach($this->extgstates as $k=>$extgstate)
				$this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
			$this->_out('>>');
		}

		function _putresources()
		{
			$this->_putextgstates();
			parent::_putresources();
		}		
		
		function Footer()
		{
			$pageNo = $this->PageNo();
			
			if(isset($_POST['poNumber']))	$poNumber = $_POST['poNumber'];
			else if(isset($_GET['poNumber']))	$poNumber = $_GET['poNumber'];
			
			if(strstr($poNumber,'-%')!==FALSE)
			{
				$poNumber = strstr($poNumber,'-%',true);
			}
			else if(strstr($poNumber,'-')!==FALSE)
			{
				//$this->waterMarkText('F O R E C A S T  P O');
			}
			
			if($pageNo > 1)
			{
				$this->SetY(-10);
				$this->SetFont('Arial','I',8);   
				$this->Cell('',5,'Page '.($pageNo-1).' of PO Number : '.$poNumber,0,0,'C');
			}
		}		
		
		function getMultiCellHeight($text,$width,$height)
		{
			$multiCellLineCount = 0;
			$stringArray = explode("\n",$text);
			if(count($stringArray) > 0)
			{
				foreach($stringArray as $string)
				{
					$strArray = explode("\n",$string);
					foreach($strArray as $str)
					{
						$strWidth = $this->GetStringWidth($str);
						if($strWidth >= $width)
						{
							$multiCellLineCount++;
						}
					}
					$multiCellLineCount++;
				}
			}
			
			return ($multiCellLineCount * $height);
		}
		
		function AutoFitCell($w='',$h='',$font='',$style='',$fontSize='',$string='',$border='',$ln='',$align='',$fill='',$link='') 
		{
			$decrement = 0.1;
			$limit = round($w)-(round($w)/3);
			
			$this->SetFont($font, $style, $fontSize);
			if(strlen($string)>$limit)
			{
				$string = substr($string,0,$limit);
				$string .= '...';
			}
			
			while($this->GetStringWidth($string) > $w)
			{
				$this->SetFontSize($fontSize -= $decrement);
			}
			
			return $this->Cell($w,$h,$string,$border,$ln,$align,$fill,$link);
		}
		
		///---JAPANESE
		function AddCIDFont($family, $style, $name, $cw, $CMap, $registry)
		{
			$fontkey=strtolower($family).strtoupper($style);
			if(isset($this->fonts[$fontkey]))
				$this->Error("CID font already added: $family $style");
			$i=count($this->fonts)+1;
			$this->fonts[$fontkey]=array('i'=>$i,'type'=>'Type0','name'=>$name,'up'=>-120,'ut'=>40,'cw'=>$cw,'CMap'=>$CMap,'registry'=>$registry);
		}

		function AddCIDFonts($family, $name, $cw, $CMap, $registry)
		{
			$this->AddCIDFont($family,'',$name,$cw,$CMap,$registry);
			$this->AddCIDFont($family,'B',$name.',Bold',$cw,$CMap,$registry);
			$this->AddCIDFont($family,'I',$name.',Italic',$cw,$CMap,$registry);
			$this->AddCIDFont($family,'BI',$name.',BoldItalic',$cw,$CMap,$registry);
		}

		function AddSJISFont($family='SJIS')
		{
			// Add SJIS font with proportional Latin
			$name='KozMinPro-Regular-Acro';
			$cw=$GLOBALS['SJIS_widths'];
			$CMap='90msp-RKSJ-H';
			$registry=array('ordering'=>'Japan1','supplement'=>2);
			$this->AddCIDFonts($family,$name,$cw,$CMap,$registry);
		}

		function AddSJIShwFont($family='SJIS-hw')
		{
			// Add SJIS font with half-width Latin
			$name='KozMinPro-Regular-Acro';
			for($i=32;$i<=126;$i++)
				$cw[chr($i)]=500;
			$CMap='90ms-RKSJ-H';
			$registry=array('ordering'=>'Japan1','supplement'=>2);
			$this->AddCIDFonts($family,$name,$cw,$CMap,$registry);
		}

		function GetStringWidth($s)
		{
			if($this->CurrentFont['type']=='Type0')
				return $this->GetSJISStringWidth($s);
			else
				return parent::GetStringWidth($s);
		}

		function GetSJISStringWidth($s)
		{
			// SJIS version of GetStringWidth()
			$l=0;
			$cw=&$this->CurrentFont['cw'];
			$nb=strlen($s);
			$i=0;
			while($i<$nb)
			{
				$o=ord($s[$i]);
				if($o<128)
				{
					// ASCII
					$l+=$cw[$s[$i]];
					$i++;
				}
				elseif($o>=161 && $o<=223)
				{
					// Half-width katakana
					$l+=500;
					$i++;
				}
				else
				{
					// Full-width character
					$l+=1000;
					$i+=2;
				}
			}
			return $l*$this->FontSize/1000;
		}

		function MultiCell($w, $h, $txt, $border=0, $align='L', $fill=false)
		{
			if($this->CurrentFont['type']=='Type0')
				$this->SJISMultiCell($w,$h,$txt,$border,$align,$fill);
			else
				parent::MultiCell($w,$h,$txt,$border,$align,$fill);
		}

		function SJISMultiCell($w, $h, $txt, $border=0, $align='L', $fill=false)
		{
			// Output text with automatic or explicit line breaks
			$cw=&$this->CurrentFont['cw'];
			if($w==0)
				$w=$this->w-$this->rMargin-$this->x;
			$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			$s=str_replace("\r",'',$txt);
			$nb=strlen($s);
			if($nb>0 && $s[$nb-1]=="\n")
				$nb--;
			$b=0;
			if($border)
			{
				if($border==1)
				{
					$border='LTRB';
					$b='LRT';
					$b2='LR';
				}
				else
				{
					$b2='';
					if(is_int(strpos($border,'L')))
						$b2.='L';
					if(is_int(strpos($border,'R')))
						$b2.='R';
					$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
				}
			}
			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$nl=1;
			while($i<$nb)
			{
				// Get next character
				$c=$s[$i];
				$o=ord($c);
				if($o==10)
				{
					// Explicit line break
					$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
					$i++;
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
					if($border && $nl==2)
						$b=$b2;
					continue;
				}
				if($o<128)
				{
					// ASCII
					$l+=$cw[$c];
					$n=1;
					if($o==32)
						$sep=$i;
				}
				elseif($o>=161 && $o<=223)
				{
					// Half-width katakana
					$l+=500;
					$n=1;
					$sep=$i;
				}
				else
				{
					// Full-width character
					$l+=1000;
					$n=2;
					$sep=$i;
				}
				if($l>$wmax)
				{
					// Automatic line break
					if($sep==-1 || $i==$j)
					{
						if($i==$j)
							$i+=$n;
						$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
					}
					else
					{
						$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
						$i=($s[$sep]==' ') ? $sep+1 : $sep;
					}
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
					if($border && $nl==2)
						$b=$b2;
				}
				else
				{
					$i+=$n;
					if($o>=128)
						$sep=$i;
				}
			}
			// Last chunk
			if($border && is_int(strpos($border,'B')))
				$b.='B';
			$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			$this->x=$this->lMargin;
		}

		function Write($h, $txt, $link='')
		{
			if($this->CurrentFont['type']=='Type0')
				$this->SJISWrite($h,$txt,$link);
			else
				parent::Write($h,$txt,$link);
		}

		function SJISWrite($h, $txt, $link)
		{
			// SJIS version of Write()
			$cw=&$this->CurrentFont['cw'];
			$w=$this->w-$this->rMargin-$this->x;
			$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			$s=str_replace("\r",'',$txt);
			$nb=strlen($s);
			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$nl=1;
			while($i<$nb)
			{
				// Get next character
				$c=$s[$i];
				$o=ord($c);
				if($o==10)
				{
					// Explicit line break
					$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
					$i++;
					$sep=-1;
					$j=$i;
					$l=0;
					if($nl==1)
					{
						// Go to left margin
						$this->x=$this->lMargin;
						$w=$this->w-$this->rMargin-$this->x;
						$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
					}
					$nl++;
					continue;
				}
				if($o<128)
				{
					// ASCII
					$l+=$cw[$c];
					$n=1;
					if($o==32)
						$sep=$i;
				}
				elseif($o>=161 && $o<=223)
				{
					// Half-width katakana
					$l+=500;
					$n=1;
					$sep=$i;
				}
				else
				{
					// Full-width character
					$l+=1000;
					$n=2;
					$sep=$i;
				}
				if($l>$wmax)
				{
					// Automatic line break
					if($sep==-1 || $i==$j)
					{
						if($this->x>$this->lMargin)
						{
							// Move to next line
							$this->x=$this->lMargin;
							$this->y+=$h;
							$w=$this->w-$this->rMargin-$this->x;
							$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
							$i+=$n;
							$nl++;
							continue;
						}
						if($i==$j)
							$i+=$n;
						$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
					}
					else
					{
						$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',0,$link);
						$i=($s[$sep]==' ') ? $sep+1 : $sep;
					}
					$sep=-1;
					$j=$i;
					$l=0;
					if($nl==1)
					{
						$this->x=$this->lMargin;
						$w=$this->w-$this->rMargin-$this->x;
						$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
					}
					$nl++;
				}
				else
				{
					$i+=$n;
					if($o>=128)
						$sep=$i;
				}
			}
			// Last chunk
			if($i!=$j)
				$this->Cell($l/1000*$this->FontSize,$h,substr($s,$j,$i-$j),0,0,'',0,$link);
		}

		function _putType0($font)
		{
			// Type0
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /Type0');
			$this->_out('/BaseFont /'.$font['name'].'-'.$font['CMap']);
			$this->_out('/Encoding /'.$font['CMap']);
			$this->_out('/DescendantFonts ['.($this->n+1).' 0 R]');
			$this->_out('>>');
			$this->_out('endobj');
			// CIDFont
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /CIDFontType0');
			$this->_out('/BaseFont /'.$font['name']);
			$this->_out('/CIDSystemInfo <</Registry (Adobe) /Ordering ('.$font['registry']['ordering'].') /Supplement '.$font['registry']['supplement'].'>>');
			$this->_out('/FontDescriptor '.($this->n+1).' 0 R');
			$W='/W [1 [';
			//~ foreach($font['cw'] as $w)
				//~ $W.=$w.' ';
			$this->_out($W.'] 231 325 500 631 [500] 326 389 500]');
			$this->_out('>>');
			$this->_out('endobj');
			// Font descriptor
			$this->_newobj();
			$this->_out('<</Type /FontDescriptor');
			$this->_out('/FontName /'.$font['name']);
			$this->_out('/Flags 6');
			$this->_out('/FontBBox [0 -200 1000 900]');
			$this->_out('/ItalicAngle 0');
			$this->_out('/Ascent 800');
			$this->_out('/Descent -200');
			$this->_out('/CapHeight 800');
			$this->_out('/StemV 60');
			$this->_out('>>');
			$this->_out('endobj');
		}
	}
	
	$seeAttachFlag = (isset($_GET['seeAttachFlag']) AND $_GET['seeAttachFlag']==1) ? 1 : 0;
	start:
	$previewFlag = (isset($_POST['preview']) AND $_POST['preview']==0) ? 0 : 1;
	$downloadFlag = (isset($_POST['download']) AND $_POST['download']==1) ? 1 : 0;
	$saveFileFlag = (isset($_GET['saveFile']) AND $_GET['saveFile']==1) ? 1 : 0;
	$tempPoNumber = $poNo = (isset($_GET['poNumber'])) ? $_GET['poNumber'] : '';
	$manualFlag = 0;
	
	$computePriceFlag = (isset($_GET['computePriceFlag']) AND $_GET['computePriceFlag']==1 AND $_SESSION['idNumber']=='0346') ? 1 : 0;
	
	if($_GET['country']==2)	$db->set_charset("sjis");
	
	if(isset($_GET['key']))
	{
		$key = $_GET['key'];
		$dataExplode=explode("`",$key);
		$dataExplode2=explode("-",$dataExplode[1]);
		
		$supplierId=$dataExplode[0];			
		$supplierType=$dataExplode2[0];
		$currency=$dataExplode2[1];
		$poCurrency=$dataExplode2[1];	
		$manualFlag=(isset($dataExplode2[2])) ? $dataExplode2[2] : 0;
		
		$poNo = '';
		$poNumber = ($tempPoNumber=='') ? 'PREVIEW' : $tempPoNumber;
		$supplierId = $dataExplode[0];
		$supplierType = $dataExplode2[0];
		$poCurrency = $dataExplode2[1];
		$delivery = '';//Temporary
		$shipmentType = '';
		$poRemarks = $_SESSION['poRemarks'];
		$poDiscount = 0;
		//~ $checkedBy = '';
		$checkedBy = $approvedBy = $_SESSION['idNumber'];
		$poInputDateTime = date('Y-m-d H:i:s');
		
		$poRemarks = ($supplierType == 2 AND $_GET['country']=='1') ? "NOTE: Please include inspection data, Certificate of conformance and Test Report upon delivery and via email." : $poRemarks;
		
		//~ $chargeDescriptionArray = (isset($_POST['chargeDescription'])) ? array_values(array_filter($_POST['chargeDescription'])) : array();
		//~ $chargeQuantityArray = (isset($_POST['chargeQuantity'])) ? array_values(array_filter($_POST['chargeQuantity'])) : array();
		//~ $chargeUnitArray = (isset($_POST['chargeUnit'])) ? array_values(array_filter($_POST['chargeUnit'])) : array();
		//~ $chargeUnitPriceArray = (isset($_POST['chargeUnitPrice'])) ? array_values(array_filter($_POST['chargeUnitPrice'])) : array();
		
		$prepared = $_SESSION['idNumber'];	
		
		$supplierAlias = $shipment = $terms = '';
		$sql = "SELECT supplierAlias, shipment, terms FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
		if($supplierType==2)	$sql = "SELECT subconAlias, shipment, terms FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
		$querySupplier = $db->query($sql);
		if($querySupplier AND $querySupplier->num_rows > 0)
		{
			$resultSupplier = $querySupplier->fetch_row();
			$supplierAlias = $resultSupplier[0];
			$shipment = $resultSupplier[1];
			$shipmentType = $resultSupplier[1];
			$terms = $resultSupplier[2];
		}
		
		$lotNumberArray = $deliveryArray = array();
		if($manualFlag==1)
		{
			$sql = "SELECT listId, dateNeeded FROM purchasing_forpurchaseorder WHERE lotNumber = '' AND processRemarks = '".$_SESSION['idNumber']."' AND supplierId = 0 AND supplierType = ".$supplierType." ";		
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$lotNumber = $resultWorkSchedule['listId'];
					$deliveryArray[] = $resultWorkSchedule['dateNeeded'];
					$lotNumberArray[] = $lotNumber;
				}
			}
		}
		else
		{
			//~ $sql = "SELECT lotNumber, dateNeeded FROM purchasing_forpurchaseorder WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency." GROUP BY lotNumber";
			$sql = "
				SELECT a.lotNumber, b.dateNeeded FROM ppic_workschedule as a
				INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
				WHERE a.processCode = 597 AND a.status = 1 AND b.supplierId = ".$supplierId." AND b.supplierType = ".$supplierType." AND b.poCurrency = ".$poCurrency." GROUP BY a.lotNumber
			";		
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$lotNumber = $resultWorkSchedule['lotNumber'];
					$deliveryArray[] = $resultWorkSchedule['dateNeeded'];
					$lotNumberArray[] = $lotNumber;
				}
			}
		}
		
		
		$poIssueDate = date('Y-m-d');
		
		$deliveryArray = array_values(array_unique(array_filter($deliveryArray)));
		if(count($deliveryArray) == 1)
		{
			$delivery = $deliveryArray[0];
		}
	}	
	else if(isset($_POST['poNumber']))
	{
		$poNumber = $_POST['poNumber'];
		$supplierId = $_POST['supplierId'];
		$supplierType = $_POST['supplierType'];
		$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
		$poCurrency = $_POST['poCurrency'];
		$delivery = $_POST['poTargetReceiveDate'];//Temporary
		$shipmentType = $_POST['shipmentType'];
		$poRemarks = $_POST['poRemarks'];
		$poDiscount = $_POST['poDiscount'];
		$checkedBy = '0541';
		$approvedBy = '0048';
		
		$chargeDescriptionArray = (isset($_POST['chargeDescription'])) ? array_values(array_filter($_POST['chargeDescription'])) : array();
		$chargeQuantityArray = (isset($_POST['chargeQuantity'])) ? array_values(array_filter($_POST['chargeQuantity'])) : array();
		$chargeUnitArray = (isset($_POST['chargeUnit'])) ? array_values(array_filter($_POST['chargeUnit'])) : array();
		$chargeUnitPriceArray = (isset($_POST['chargeUnitPrice'])) ? array_values(array_filter($_POST['chargeUnitPrice'])) : array();
		
		$prepared = $_SESSION['idNumber'];
		
		$sqlFilter = '';
		if($customerAlias!='')
		{
			if($customerAlias=='others')
			{
				$sqlFilter = " AND customerAlias NOT IN('B/E Phils.','JAMCO PHILS')";
			}
			else
			{
				$sqlFilter = " AND customerAlias LIKE '".$customerAlias."'";
			}
		}
		
		$lotNumberArray = array();
		$sql = "SELECT lotNumber, targetFinish, processRemarks FROM view_workschedule WHERE processCode = 461 AND processSection = 5 AND processRemarks != '' AND availability = 1 ".$sqlFilter." ORDER BY targetFinish, lotNumber DESC";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$targetFinish = $resultWorkSchedule['targetFinish'];
				$processRemarks = $resultWorkSchedule['processRemarks'];
				
				$productIds = $processRemarks;
				
				$productIdsCount = count(explode(",",$productIds));
				$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber LIKE '".$lotNumber."' AND productId IN(".$productIds.") AND itemStatus != 2";
				$queryPoContents = $db->query($sql);
				$poProductIdCount = ($queryPoContents AND $queryPoContents->num_rows) ? $queryPoContents->num_rows : 0;
				if($poProductIdCount >= $productIdsCount)
				{
					continue;
				}
				
				$partId = $workingQuantity = $identifier = $supplyType = '';
				$sql = "SELECT partId, workingQuantity, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					$resultLotList = $queryLotList->fetch_assoc();
					$partId = $resultLotList['partId'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$supplyType = $resultLotList['status'];
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
					
				$sql = "SELECT productId FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType." LIMIT 1";
				$querySupplierProducts = $db->query($sql);
				if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
				{
					//~ echo "<br>".$lotNumber;
					$lotNumberArray[] = $lotNumber;
				}
			}
		}
	}
	else if($poNo!='')
	{
		$poNumber = $poNo;
		$sql = "SELECT supplierId, supplierType, poTerms, poShipmentType, poIncharge, poIssueDate, poTargetReceiveDate, poRemarks, poCurrency, poDiscount, poInputDateTime, checkedBy, approvedBy FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
		$queryPodetailsNew = $db->query($sql);
		if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
		{
			$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
			$supplierId = $resultPodetailsNew['supplierId'];
			$supplierType = $resultPodetailsNew['supplierType'];
			$poTerms = $resultPodetailsNew['poTerms'];
			$shipmentType = $resultPodetailsNew['poShipmentType'];
			$prepared = $resultPodetailsNew['poIncharge'];
			$poIssueDate = $resultPodetailsNew['poIssueDate'];
			$delivery = $resultPodetailsNew['poTargetReceiveDate'];
			$poRemarks = $resultPodetailsNew['poRemarks'];
			$poCurrency = $resultPodetailsNew['poCurrency'];
			$poDiscount = $resultPodetailsNew['poDiscount'];
			$poInputDateTime = $resultPodetailsNew['poInputDateTime'];
			$checkedBy = $resultPodetailsNew['checkedBy'];
			$approvedBy = $resultPodetailsNew['approvedBy'];
		}
		
		$lotNumberArray = array();
		$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
		$queryPoContents = $db->query($sql);
		if($queryPoContents AND $queryPoContents->num_rows > 0)
		{
			while($resultPoContents = $queryPoContents->fetch_assoc())
			{
				$lotNumberArray[] = $resultPoContents['lotNumber'];
			}
		}
		
		$chargeDescriptionArray = $chargeQuantityArray = $chargeUnitArray = $chargeUnitPriceArray = array();
		$sql = "SELECT `chargeDescription`, `chargeQuantity`, `chargeUnit`, `chargeUnitPrice` FROM `purchasing_charges` WHERE poNumber LIKE '".$poNumber."'";
		$queryCharges = $db->query($sql);
		if($queryCharges->num_rows > 0)
		{
			while($resultCharges = $queryCharges->fetch_array())
			{
				$chargeDescriptionArray[] = $resultCharges['chargeDescription'];
				$chargeQuantityArray[] = $resultCharges['chargeQuantity'];
				$chargeUnitArray[] = $resultCharges['chargeUnit'];
				$chargeUnitPriceArray[] = $resultCharges['chargeUnitPrice'];
			}
		}
		if($computePriceFlag==1)	$poNo = '';
		
		if($poNumber=='0012322' AND $_SESSION['idNumber']=='0346')	$poNumber = '0011924';
		
		$mainPo = strstr($poNo,'-',true);
		if($mainPo!==FALSE)
		{
			$poNumber = $mainPo;
			
			//~ if($_SESSION['idNumber']=='0346')	$poNumber = '0016258-1';
			
			$poIssueDate = date('Y-m-d');
			
			$deliveryArray = array();
			$sql = "SELECT poTargetReceiveDate FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."'";
			$queryPodetailsNew = $db->query($sql);
			if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
			{
				$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
				$deliveryArray[] = $resultPodetailsNew['poTargetReceiveDate'];
			}			
			
			$delivery = '';
			$deliveryArray = array_values(array_unique(array_filter($deliveryArray)));
			if(count($deliveryArray) == 1)
			{
				$delivery = $deliveryArray[0];
			}
		}		
	}
	
	if($poNumber=='0010100')	$supplierId = 860;
	//~ if($poNumber=='0010469')	$supplierId = 638;//2018-04-05
	
	$sql = "SELECT firstName, surName FROM hr_employee WHERE idNumber LIKE '".$prepared."' LIMIT 1";
	$queryEmployee = $db->query($sql);
	if($queryEmployee->num_rows > 0)
	{
		$resultEmployee = $queryEmployee->fetch_array();
		$firstName = $resultEmployee['firstName'];
		$surName = $resultEmployee['surName'];
	}
	
	if($supplierType==1)
	{
		$sql = "SELECT supplierName, supplierAddress, supplierTel, supplierFax, contactPerson, terms, tinNumber FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	}
	else if($supplierType==2)
	{
		$sql = "SELECT subconName, subconAddress, subconPhone, subconFax, subconContactPerson, terms, tinNumber FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
	}
	if($sql!='')
	{
		$querySupplier = $db->query($sql);
		if($querySupplier AND $querySupplier->num_rows > 0)
		{
			$resultSupplier = $querySupplier->fetch_row();
			$supplierName = $resultSupplier[0];
			$supplierAddress = $resultSupplier[1];
			$supplierPhone = $resultSupplier[2];
			$supplierFax = $resultSupplier[3];
			$supplierContactPerson = $resultSupplier[4];
			$terms = $resultSupplier[5];
			$tinNumber = $resultSupplier[6];
		}
	}

	if($poTerms!='') $terms = $poTerms;

	$mergeSubconPriceFlag = (strtotime($poInputDateTime) >= strtotime('2022-03-25')) ? 1 : 0;
	
	$pdf=new PDF('P','mm','A4');
	$pdf->SetTitle('PO Number '.$poNumber);
	$pdf->AddSJISFont();
	$pdf->AddPage();
	$pdf->SetLeftMargin(13);
	$pdf->SetAutoPageBreak('on',1);
	$pdf->AliasNbPages();
	//~ if(in_array($poNumber,array('0014458','0014459')))	$previewFlag = 0;
	//if(in_array($poNumber,array('0010158')))	$previewFlag = 0; //lanie dec21,2017
	//~ if(in_array($poNumber,array('0014446','0014447','0014448')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0015029')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0012109','0012055','0012098','0011975','0012047','0012074','0011999')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0013074')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0012195','0012275','0012209','0012127','0012212','0012126','0012116','0012113','0012106','0012221')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0012064','0012056','0012065','0012105')))	$previewFlag = 0;
	//~ if(in_array($poNumber,array('0012131','0012129','0012188','0012121','0012196','0012199')))	$previewFlag = 0;
	
	if($previewFlag == 1)
	{
		$pdf->Image($path.'Templates/images/po2.jpg',0,0,210,297);
		$pdf->SetFont('Arial','B',19);
		$pdf->SetXY(150,35);
		$pdf->Cell(50,5,$poNumber,0,0,'C');
	}

	$issue = ($poIssueDate=='') ? date('Y-m-d') : $poIssueDate;
	$dateIssue = date('M d, Y', strtotime($issue));
	if($supplierType==1)
	{
		$deliveryDate = date('M d, Y', strtotime($delivery));
		if($delivery=='' OR $delivery=='0000-00-00')	$deliveryDate = 'To be advised';
		if($poNumber=='0010362')	$deliveryDate = 'To be discuss';
		if($poNumber=='0013837')	$deliveryDate = 'ASAP';
		# RG 2019-01-11
		if(in_array($poNumber, Array ('0013766','0013765','0013763','0013689','0012158','0012159','0012161', '0012162', '0013512')))	$deliveryDate = 'To be advised';
		# END RG
		//~ if($poNumber=='0011491')	$deliveryDate = 'To be discuss';
		if($poNumber=='0011046')	$deliveryDate = '';
		// if($poNumber=='0010463')	$deliveryDate = 'Mar 16, 2018';
		//~ $sending = ($sendingDate!='') ? date('M d, Y', strtotime($sendingDate)): '';
	}
	if($shipmentType==1)		$shipping='Land';
	else if($shipmentType==2)	$shipping='Air';
	else if($shipmentType==3)	$shipping='Sea';
	
	$yen = Iconv('UTF-8','ISO-8859-1//TRANSLIT','¥');
	
	if($poCurrency == 1)
	{
		$sign = '$';
		$currencyDetails = 'US $ (USD)';
	}
	else if($poCurrency == 2)
	{
		$sign = 'Php';
		$currencyDetails = 'Philippine Peso (Php)';
	}
	else if($poCurrency == 3)
	{
		$sign = Iconv('UTF-8','ISO-8859-1//TRANSLIT','¥');
		$currencyDetails = 'Japanese Yen ('.$yen.')';
	}
	
	//~ if($_SESSION['idNumber']=='0346')	$pdf->SetTextColor(255,255,255);
	
	$pdf->SetFont('Arial','',10);//Bold
	
	//~ $pdf->SetXY(158.00125,54.50125);
	$pdf->SetXY(158.00125,52.50125);
	$pdf->Cell(41,5,$dateIssue,0,0,'C');
	
	$pdf->SetXY(20.00125,58.00125);
	if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
	$pdf->MultiCell(96,4,$supplierName,0);
	$pdf->MultiCell(101,4,$supplierAddress,0);
	if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
	$pdf->Cell(101,4,"Tel #:".$supplierPhone,0,0,'L');$pdf->Ln();
	$pdf->Cell(101,4,"Fax #:".$supplierFax,0,0,'L');$pdf->Ln();
	$pdf->Cell(101,4,"Tin #:".$tinNumber,0,0,'L');
	
	$pdf->SetXY(125.00125,70.00125);
	$pdf->MultiCell(74,5,$supplierContactPerson,0);
	
	$pdf->SetXY(10.00125,93.00125);
	if($poNumber=='0013828')
	{
		$pdf->SetFont('Arial','',6);//Bold
		$pdf->MultiCell(28,3,$terms,0);
		$pdf->SetFont('Arial','',10);//Bold
	}
	else if($poNumber=='0013874')
	{
		$pdf->SetFont('Arial','',8.5);//Bold
		$pdf->MultiCell(28,3,$terms,0);
		$pdf->SetFont('Arial','',10);//Bold
	}
	else
	{
		$pdf->MultiCell(28,3,$terms,0);
	}
	$pdf->SetXY(46.00125,93.00125);
	
	$pdf->MultiCell(37,3,$deliveryDate,0);
	$pdf->SetXY(85.00125,93.00125);
	$pdf->Cell(54,5,$shipping,0,0,'C');
	$pdf->SetXY(141.00125,93.00125);
	$pdf->Cell(63,5,$firstName." ".$surName,0,0,'C');
	$pdf->SetXY(46.00125,107.00125);
	$pdf->Cell(158,5,'AMOUNT in '.$currencyDetails,0,0,'L');

	if($sendingDate!='' AND $sendingDate!='0000-00-00')
	{
		$pdf->SetXY(136.00125,102.00125);
		$pdf->Cell(40,5,'Sending Date to Subcon : ',0,0,'C');
		$pdf->Cell(23,5,$sending,0,0,'L');
	}
	
	$pdf->SetY(127.00125);
	
	$sql = "SELECT `item`, `quantity`, `unit`, `description`, `unitPrice`, `currency`, `amount`, `remarks` FROM purchasing_pospecialview WHERE poNumber LIKE '".$poNumber."'";
	$queryPoSpecialView = $db->query($sql);
	if($queryPoSpecialView AND $queryPoSpecialView->num_rows > 0)
	{
		while($resultPoSpecialView = $queryPoSpecialView->fetch_assoc())
		{
			$item = $resultPoSpecialView['item'];
			$quantity = $resultPoSpecialView['quantity'];
			$itemUnit = $resultPoSpecialView['unit'];
			$description = $resultPoSpecialView['description'];
			$itemPrice = $resultPoSpecialView['unitPrice'];
			$sign = $resultPoSpecialView['currency'];
			$amount = $resultPoSpecialView['amount'];
			$remarks = $resultPoSpecialView['remarks'];
			
			$quantity = wholeNumber($quantity);
			
			if($sign=='¥')
			{
				$sign = Iconv('UTF-8','ISO-8859-1//TRANSLIT','¥');
			}
			
			$unitName = '';
			$sql = "SELECT unitName FROM purchasing_units WHERE unitId = ".$itemUnit." LIMIT 1";
			$queryUnits = $db->query($sql);
			if($queryUnits AND $queryUnits->num_rows > 0)
			{
				$resultUnits = $queryUnits->fetch_assoc();
				$unitName = $resultUnits['unitName'];
			}
			
			$totalPrice = round($itemPrice,4) * $quantity;
			
			if($poNumber=='0011986')
			{
				$totalPrice = round($itemPrice,4);
			}
			
			$totalAmount += round($totalPrice,2);
			
			if($quantity == 0) $quantity = '';
			$item = ($item > 0) ? $item.'.' : '';
			
			$priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 4, '.', ',') : '';
			// $priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 2, '.', ',') : '';
			$totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
			
			if($poNumber=='0011986')	$totalPriceInFormat = '';
			
			
			$itemDescription = '';
			$pdf->SetFont('Arial','',10);//Bold
			$pdf->Cell(11,5,$item,0,0,'L');
			$pdf->Cell(3,5,"",0,0,'R');
			$pdf->Cell(4,5,$quantity,0,0,'R');
			if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
			$pdf->Cell(14,5,$unitName,0,0,'L');
			$pdf->Cell(96,5,$description,0,0,'L');
			if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
			$pdf->Cell(31,5,$priceInFormat,0,0,'R');
			$pdf->Cell(1,5,'',0);
			$pdf->Cell(29,5,$totalPriceInFormat,0,0,'R');
			$pdf->Cell(1,5,'',0);
			$pdf->Ln(4);
			$pdf->Cell(11,4,"",0,0,'L');
			$pdf->Cell(7,4,"",0,0,'R');
			$pdf->Cell(14,4,"",0,0,'L');
			if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
			$pdf->MultiCell(96,4,$itemDescription,0,'L');
			if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
		}
	}
	else
	{
		$contentDataArray = $chargesDataArray = array();
		
		$signatureFlag = 0;
		
		$totalAmount = 0;
		if(count($lotNumberArray) > 0)
		{
			if($seeAttachFlag == 0 AND $supplierType == 2)
			{
				$pdf->Cell(12,5,'',0,0,'L');
				$pdf->Cell(13,5,'',0,0,'R');
				$pdf->Cell(8,5,'',0,0,'L');
				$pdf->SetFont('Arial','U',10);;
				$pdf->Cell(47,5,'Part Number',0,0,'L');
				$pdf->Cell(20,5,'Lot Number',0,0,'L');
				$pdf->Cell(25,5,'Mat Type',0,0,'C');
				$pdf->SetFont('Arial','',10);
				$pdf->Cell(27,5,'',0,0,'R');
				$pdf->Cell(5,5,'',0);
				$pdf->Cell(25,5,'',0,0,'R');
				$pdf->Cell(5,5,'',0);
				$pdf->Ln();
			}
			
			foreach($lotNumberArray as $lotNumber)
			{
				//~ if($_SESSION['idNumber']=='0346' AND $lotNumber!='20-02-2211') continue;
				
				$sql = "SELECT poId, partId, workingQuantity, identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList->num_rows > 0 OR $manualFlag==1)
				{
					$resultLotList = $queryLotList->fetch_array();
					$poId = $resultLotList['poId'];
					$partId = $resultLotList['partId'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$supplyType = $resultLotList['status'];
					
					$workingQuantity = wholeNumber($workingQuantity);
					
					$dateNeeded = '';
					
					if($identifier==1 OR ($identifier==4 AND $supplyType==2))
					{
						if($identifier==1)
						{
							$partNumber = $revisionId = $materialSpecId = $customerId = $partNote = $subconSubpartFlag = '';
							$sql = "SELECT partNumber, revisionId, materialSpecId, customerId, partNote, subconSubpartFlag FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
							$queryParts = $db->query($sql);
							if($queryParts AND $queryParts->num_rows > 0)
							{
								$resultParts = $queryParts->fetch_assoc();
								$partNumber = $resultParts['partNumber'];
								$revisionId = "rev ".$resultParts['revisionId'];
								$materialSpecId = $resultParts['materialSpecId'];
								$customerId = $resultParts['customerId'];
								$partNote = $resultParts['partNote'];
								$subconSubpartFlag = $resultParts['subconSubpartFlag'];
							}
							
							if($customerId==28 AND $supplierId==11)//2018-06-23 by sir Nagano all BE items to KAPCO
							{
								$signatureFlag = 1;
							}
							
							$metalType = '';
							$sql = "SELECT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
							$queryMaterialSpecs = $db->query($sql);
							if($queryMaterialSpecs->num_rows > 0)
							{
								$resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
								$materialTypeId = $resultMaterialSpecs['materialTypeId'];
								
								$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
								$queryMat1 = $db->query($sql);
								if($queryMat1 AND $queryMat1->num_rows > 0)
								{
									$resultMat1 = $queryMat1->fetch_assoc();
									$metalType = $resultMat1['materialType'];
								}							
							}						
							
							//cadcam_materialspecs; 03-08-2017
							//~ $metalType = '';
							//~ $sql = "SELECT metalType FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
							//~ $queryMaterialSpecs = $db->query($sql);
							//~ if($queryMaterialSpecs->num_rows > 0)
							//~ {
								//~ $resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
								//~ $metalType = $resultMaterialSpecs['metalType'];
							//~ }
						}
						else if($identifier==4)
						{
							$partNumber = $revisionId = $partNote = '';
							
							$materialId = $cadamTreatmentId = '';
							$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
							$querySubconMaterial = $db->query($sql);
							if($querySubconMaterial->num_rows > 0)
							{
								$resultSubconMaterial = $querySubconMaterial->fetch_array();
								$materialId = $resultSubconMaterial['materialId'];
								$cadamTreatmentId = $resultSubconMaterial['treatmentId'];
							}
							
							$materialSpecId = $length = $width = '';
							$sql = "SELECT `materialSpecId`, `length`, `width` FROM `purchasing_material` WHERE `materialId` = ".$materialId." LIMIT 1";
							$queryMaterial = $db->query($sql);
							if($queryMaterial->num_rows > 0)
							{
								$resultMaterial = $queryMaterial->fetch_array();
								$materialSpecId = $resultMaterial['materialSpecId'];
								$length = $resultMaterial['length'];
								$width = $resultMaterial['width'];
							}
							
							$materialTypeId = $metalThickness = '';
							$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
							$queryMaterialSpecs = $db->query($sql);
							if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows)
							{
								$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
								$materialTypeId = $resultMaterialSpecs['materialTypeId'];
								$thickness = $resultMaterialSpecs['metalThickness'];
							}
							
							$materialType = '';
							$baseWeight = $coatingWeight = 0;
							$sql = "SELECT `materialType`, `baseWeight`, `coatingWeight` FROM `engineering_materialtype` WHERE `materialTypeId` = ".$materialTypeId." LIMIT 1";
							$queryMaterialType = $db->query($sql);
							if($queryMaterialType->num_rows > 0)
							{
								$resultMaterialType = $queryMaterialType->fetch_array();
								$materialType = $resultMaterialType['materialType'];
								$baseWeight = $resultMaterialType['baseWeight'];
								$coatingWeight = $resultMaterialType['coatingWeight'];
							}
							
							$metalType = $materialType;
							
							$partNumber = $materialType." t".$thickness."X".$length."X".$width;
							
							if($supplyType==1)
							{
								$materialComputationId = '';
								$sql = "SELECT materialComputationId FROM ppic_materialcomputationdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryMaterialComputationDetails = $db->query($sql);
								if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
								{
									$resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc();
									$materialComputationId = $resultMaterialComputationDetails['materialComputationId'];
								}
								
								$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
								$queryMaterialComputation = $db->query($sql);
								if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
								{
									$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
									$dateNeeded = $resultMaterialComputation['dateNeeded'];
								}
							}
						}
					}
					
					$pvc = '';
					if($identifier==4 AND $supplyType==1)
					{
						$sql = "SELECT pvc FROM system_confirmedmaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryConfirmedMaterialPo = $db->query($sql);
						if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
						{
							$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
							$pvc = ($resultConfirmedMaterialPo['pvc']==1) ? 'w/PVC' : '';
						}
					}
					
					$partialReceivingArray = $treatmentNameArray = $receivingDateArray = $sendingDateArray = $priceInFormatArray = $totalPriceInFormatArray = array();
					
					$subpartLotNumberArray = $subPartsArray = $unitPriceSubArray = array();
					//~ if($_SESSION['idNumber']=='0346' OR $_SESSION['idNumber']=='0276')
					//~ {
						$subpartLotNumber = $subParts = '';
						if($identifier==1 AND $subconSubpartFlag==1)
						{
							$sql = "SELECT lotNumber, partId FROM ppic_lotlist WHERE parentLot LIKE SUBSTRING_INDEX('".$lotNumber."','-',3) AND identifier = 1 AND lotNumber LIKE '%-%-%'";
							$querySubpartLot = $db->query($sql);
							if($querySubpartLot AND $querySubpartLot->num_rows > 0)
							{
								while($resultSubpartLot = $querySubpartLot->fetch_assoc())
								{
									$subpartLotNumberArray[] = $resultSubpartLot['lotNumber'];
									$subPartId = $resultSubpartLot['partId'];
									
									$sql = "SELECT CONCAT(partNumber,' rev ',revisionId) as subParts FROM cadcam_parts WHERE partId = ".$subPartId." LIMIT 1";
									$querySubParts = $db->query($sql);
									if($querySubParts AND $querySubParts->num_rows > 0)
									{
										$resultSubParts = $querySubParts->fetch_assoc();
										$treatmentNameArray[] = $subPartsArray[] = $resultSubParts['subParts'];
									}
									
									$subconListIdArray = array();
									$sql = "SELECT a FROM cadcam_subconlist WHERE partId = ".$subPartId."";
									$querySubconList = $db->query($sql);
									if($querySubconList AND $querySubconList->num_rows > 0)
									{
										while($resultSubconList = $querySubconList->fetch_assoc())
										{
											$subconListIdArray[] = $resultSubconList['a'];
										}
									}
									
									$productIdArray = array();
									$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId IN(".implode(",",$subconListIdArray).") AND supplyType = 5";
									$querySupplierProductLinking = $db->query($sql);
									if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
									{
										while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
										{
											$productIdArray[] = $resultSupplierProductLinking['productId'];
										}
									}
									
									$unitPriceSub = 0;
									$sql = "SELECT productId, productName, productDescription, productUnit FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".implode(",",$productIdArray).") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType."";
									$querySupplierProducts = $db->query($sql);
									if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
									{
										while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
										{
											$productId = $resultSupplierProducts['productId'];
											
											$itemQuantity1 = 1;
											
											$price = 0;
											$breakFlag = 0;
											$priceCount = 0;
											$sql = "SELECT priceLowerRange, priceUpperRange, price FROM purchasing_price WHERE productId = ".$productId." AND currency = ".$poCurrency."";
											$queryPrice = $db->query($sql);
											if($queryPrice AND $queryPrice->num_rows > 0)
											{
												while($resultPrice = $queryPrice->fetch_assoc())
												{
													$priceLowerRange = $resultPrice['priceLowerRange'];
													$priceUpperRange = $resultPrice['priceUpperRange'];
													$price = $resultPrice['price'];
													//~ if($poNumber=='0011723')	$price = ;
													
													$breakFlag = 0;
													
													if($priceLowerRange != 0 AND $priceUpperRange != 0)
													{
														if($priceLowerRange == $priceUpperRange)
														{
															if($workingQuantity >= $priceLowerRange)	$breakFlag = 1;
														}
														else
														{
															if($itemQuantity1 >= $priceLowerRange AND $itemQuantity1 <= $priceUpperRange)	$breakFlag = 1;
														}
													}
													else
													{
														$breakFlag = 1;
													}
													
													if(++$priceCount == $queryPrice->num_rows)	$breakFlag = 1;
													
													if($breakFlag==1)
													{
														$unitPriceSub += $price;
														break;
													}
												}
											}
										}
									}
									
									$unitPriceSubArray[$resultSubpartLot['lotNumber']] = $unitPriceSub;
								}
								$subpartLotNumberArray[] = $lotNumber;
								$treatmentNameArray[] = 'Assembly Lot Number';
								
								$subpartLotNumber = implode("\n",$subpartLotNumberArray);
								//~ $subParts = implode("\n",$subPartsArray)."\n";
							}
						}
					//~ }
					$totalUnitPrice = 0;
					if($poNo!='')
					{
						$sql = "SELECT `poContentId`, `productId`, `itemName`, `itemDescription`, `itemQuantity`, `itemUnit`, `itemContentQuantity`, `itemContentUnit`, `itemPrice`, `itemFlag` FROM purchasing_pocontents WHERE poNumber LIKE '".$poNo."' AND lotNumber LIKE '".$lotNumber."'";
						$queryPoContents = $db->query($sql);
						if($queryPoContents AND $queryPoContents->num_rows > 0)
						{
							while($resultPoContents = $queryPoContents->fetch_assoc())
							{
								$poContentId = $resultPoContents['poContentId'];
								$productId = $resultPoContents['productId'];
								$itemName = $resultPoContents['itemName'];
								$itemDescription = $resultPoContents['itemDescription'];
								$itemQuantity = $resultPoContents['itemQuantity'];
								$itemUnit = $resultPoContents['itemUnit'];
								$itemPrice = $resultPoContents['itemPrice'];
								
								$totalUnitPrice += $itemPrice;
								$totalPrice = round($itemPrice,4) * $itemQuantity;
								if($mergeSubconPriceFlag==0) $totalAmount += round($totalPrice,2);
								
								$priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 4, '.', ',') : '';
								// $priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 2, '.', ',') : '';
								$totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
								
								if($supplierType==1)
								{
									$unitName = '';
									$sql = "SELECT unitName FROM purchasing_units WHERE unitId = ".$itemUnit." LIMIT 1";
									$queryUnits = $db->query($sql);
									if($queryUnits AND $queryUnits->num_rows > 0)
									{
										$resultUnits = $queryUnits->fetch_assoc();
										$unitName = $resultUnits['unitName'];
									}								
									
									$sql = "SELECT quantity, receivingDate FROM purchasing_pocontentdetails WHERE poContentId = ".$poContentId."";
									$queryPoContentDetails = $db->query($sql);
									if($queryPoContentDetails AND $queryPoContentDetails->num_rows > 0)
									{
										while($resultPoContentDetails = $queryPoContentDetails->fetch_assoc())
										{
											//~ $partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['receivingDate']." = Quantity : ".$resultPoContentDetails['quantity'];
											if($resultPoContentDetails['quantity']==$itemQuantity)
											{
												$partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['receivingDate'];
											}
											else
											{
												$partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['quantity']." ".$unitName." ".$resultPoContentDetails['receivingDate'];
											}
										}
									}
									else
									{
										$partialReceivingArray[] = "Receiving: to be advised";
									}
								}
								else if($supplierType==2)
								{
									if(count($subpartLotNumberArray) > 0)
									{
										$subPartCount = count($subpartLotNumberArray) - 1;
										
										//~ $priceInFormat1 = ($itemPrice > 0) ? $sign." ".number_format(($itemPrice/$subPartCount), 4, '.', ',') : '';
										//~ $totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
										
										foreach($subpartLotNumberArray as $subpartLot)
										{
											$unitPriceSub = $unitPriceSubArray[$subpartLot];
											
											$priceInFormat1 = ($unitPriceSub > 0) ? $sign." ".number_format(($unitPriceSub), 4, '.', ',') : '';
											// $priceInFormat1 = ($unitPriceSub > 0) ? $sign." ".number_format(($unitPriceSub), 2, '.', ',') : '';
											
											$priceInFormatArray[] = ($subpartLot!=$lotNumber) ? $priceInFormat1 : '';
											$totalPriceInFormatArray[] = '';
											$sendingDateArray[] = "";
											$receivingDateArray[] = '';
										}
									}
									
									$treatmentProcess = $itemName;
									if(trim($treatmentProcess)=='JPS-3405 Type II Class 1 (More than 10 Micron)') $treatmentProcess = 'JPS-3405Q Type II Class 1';
									$treatmentProcessCut = (strlen($treatmentProcess) > 15) ? substr($treatmentProcess,0,23)."..." : $treatmentProcess ;
									$treatmentNameArray[] = $treatmentProcessCut;
									$priceInFormatArray[] = $priceInFormat;
									$totalPriceInFormatArray[] = $totalPriceInFormat;
									
									$sql = "SELECT sendingDate, receivingDate FROM purchasing_pocontentdetails WHERE poContentId = ".$poContentId." LIMIT 1";
									$queryPoContentDetails = $db->query($sql);
									if($queryPoContentDetails AND $queryPoContentDetails->num_rows > 0)
									{
										$resultPoContentDetails = $queryPoContentDetails->fetch_assoc();
										$receivingDate = "Receiving: ".$resultPoContentDetails['receivingDate'];
										$sendingDate = "Sending: ".$resultPoContentDetails['sendingDate'];
										
										if(strtotime($issue) >= strtotime(date('Y-m-d')))
										{
											$receivingDateArray[] = (in_array($receivingDate,$receivingDateArray)) ? "" : $receivingDate;
											$sendingDateArray[] = (in_array($sendingDate,$sendingDateArray)) ? "" : $sendingDate;
										}
										else
										{
											$receivingDateArray[] = $receivingDate;
											$sendingDateArray[] = $sendingDate;
										}
									}
								}
							}
						}
					}
					else if($manualFlag==1)
					{
						$listId = $lotNumber;
						$sql = "SELECT itemName, itemDescription, itemQuantity, itemUnit, itemPrice FROM purchasing_forpurchaseorder WHERE listId = ".$listId." LIMIT 1";
						$queryForPurchase = $db->query($sql);
						if($queryForPurchase AND $queryForPurchase->num_rows > 0)
						{
							$resultForPurchase = $queryForPurchase->fetch_assoc();
							$itemName = $resultForPurchase['itemName'];
							$itemDescription = $resultForPurchase['itemDescription'];
							$itemQuantity = $resultForPurchase['itemQuantity'];
							$itemUnit = $resultForPurchase['itemUnit'];
							$itemPrice = $resultForPurchase['itemPrice'];
							
							$totalUnitPrice += $itemPrice;
							$totalPrice = round($itemPrice,4) * $itemQuantity;
							if($mergeSubconPriceFlag==0) $totalAmount += round($totalPrice,2);
							
							$priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 4, '.', ',') : '';
							// $priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 2, '.', ',') : '';
							$totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
						}
					}
					else
					{
						$itemQuantity = $workingQuantity;
						
						if(count($subpartLotNumberArray) > 0)
						{
							$subPartCount = count($subpartLotNumberArray) - 1;
							
							//~ $priceInFormat1 = ($itemPrice > 0) ? $sign." ".number_format(($itemPrice/$subPartCount), 4, '.', ',') : '';
							//~ $totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
							
							foreach($subpartLotNumberArray as $subpartLot)
							{
								$unitPriceSub = $unitPriceSubArray[$subpartLot];
								
								$priceInFormat1 = ($unitPriceSub > 0) ? $sign." ".number_format(($unitPriceSub), 4, '.', ',') : '';
								// $priceInFormat1 = ($unitPriceSub > 0) ? $sign." ".number_format(($unitPriceSub), 2, '.', ',') : '';
								
								$priceInFormatArray[] = ($subpartLot!=$lotNumber) ? $priceInFormat1 : '';
								$totalPriceInFormatArray[] = '';
								$sendingDateArray[] = "";
								$receivingDateArray[] = '';
							}
						}
						
						$productIds = '';
						
						//~ $sql = "
							//~ SELECT a.processRemarks FROM view_workschedule as a
							//~ INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
							//~ WHERE a.processCode = 597 AND a.status = 0 AND a.lotNumber LIKE '".$lotNumber."'
						//~ ";
						//~ if($computePriceFlag==1) $sql = "SELECT processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461 AND processSection = 5 AND processRemarks != ''";
						$sql = "SELECT GROUP_CONCAT(productId) as processRemarks FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."' GROUP BY lotNumber";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
							$productIds = $resultWorkSchedule['processRemarks'];
						}
						
						$packingCostFlag = 0;
						$unitPrice = 0;
						$productUnit = '';
						$sql = "SELECT productId, productName, productDescription, productUnit FROM purchasing_supplierproducts WHERE productId IN(SELECT productId FROM purchasing_price WHERE productId IN(".$productIds.") AND currency = ".$poCurrency.") AND supplierId = ".$supplierId." AND supplierType = ".$supplierType."";
						$querySupplierProducts = $db->query($sql);
						if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
						{
							while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
							{
								$productId = $resultSupplierProducts['productId'];
								$itemName = $resultSupplierProducts['productName'];
								$itemDescription = $resultSupplierProducts['productDescription'];
								$itemUnit = $resultSupplierProducts['productUnit'];
								
								$surfaceArea = $packingCost = 0;
								if($supplierType==1)
								{
									$unitName = '';
									$sql = "SELECT unitName FROM purchasing_units WHERE unitId = ".$itemUnit." LIMIT 1";
									$queryUnits = $db->query($sql);
									if($queryUnits AND $queryUnits->num_rows > 0)
									{
										$resultUnits = $queryUnits->fetch_assoc();
										$unitName = $resultUnits['unitName'];
									}
									
									$sql = "SELECT quantity, receivingDate FROM purchasing_pocontentdetails WHERE lotNumber LIKE '".$lotNumber."' AND poContentId = ''";
									$queryPoContentDetails = $db->query($sql);
									if($queryPoContentDetails AND $queryPoContentDetails->num_rows > 0)
									{
										while($resultPoContentDetails = $queryPoContentDetails->fetch_assoc())
										{
											//~ $partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['receivingDate']." = Quantity : ".$resultPoContentDetails['quantity'];
											if($resultPoContentDetails['quantity']==$itemQuantity)
											{
												$partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['receivingDate'];
											}
											else
											{										
												$partialReceivingArray[] = "Receiving : ".$resultPoContentDetails['quantity']." ".$unitName." ".$resultPoContentDetails['receivingDate'];
											}
										}
									}
									else
									{
										$sql = "SELECT dateNeeded FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
										$queryForPurchase = $db->query($sql);
										if($queryForPurchase AND $queryForPurchase->num_rows > 0)
										{
											$resultForPurchase = $queryForPurchase->fetch_assoc();
											$partialReceivingArray[] = "Receiving: ".$resultForPurchase['dateNeeded'];
										}
										else
										{
											$partialReceivingArray[] = "Receiving: to be advised";
										}
									}
								}
								else if($supplierType==2)
								{
									$treatmentProcess = $itemName;
									
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
											$subconOrder = 1;
											$sql = '';
											if($supType==2)
											{
												$sql = "SELECT processCode, surfaceArea, subconOrder FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$supplyId." LIMIT 1";
											}
											else if($supType==5)
											{
												$treatmentProcess = $itemDescription;
												if(trim($treatmentProcess)=='JPS-3405 Type II Class 1 (More than 10 Micron)') $treatmentProcess = 'JPS-3405Q Type II Class 1';
												//~ $treatmentProcessCut = (strlen($treatmentProcess) > 15) ? substr($treatmentProcess,0,23)."..." : $treatmentProcess ;
												$treatmentProcess = (strlen($treatmentProcess) > 15) ? substr($treatmentProcess,0,23)."..." : $treatmentProcess ;
												$sql = "SELECT processCode, surfaceArea, subconOrder FROM cadcam_subconlist WHERE a = ".$supplyId." LIMIT 1";
											}
											if($sql!='')
											{
												$querySubconList = $db->query($sql);
												if($querySubconList AND $querySubconList->num_rows > 0)
												{
													$resultSubconList = $querySubconList->fetch_assoc();
													$treatmentId = $resultSubconList['processCode'];
													$surfaceArea = $resultSubconList['surfaceArea'];
													$subconOrder = $resultSubconList['subconOrder'];
												}
											}
											
											if($supType==5)
											{
												$surfaceArea = 0;
											}										
											
											if($subconOrder==2)
											{
												$deliveryToSubconProcess = 172;
												$receivingWarehouseProcess = 138;
											}
											else if($subconOrder==3)
											{
												$deliveryToSubconProcess = 228;
												$receivingWarehouseProcess = 229;
											}
											else
											{
												$deliveryToSubconProcess = 145;
												$receivingWarehouseProcess = 137;
											}
											
											//~ $packingCost = (in_array($supplyId,array(270,272))) ? ($totalSurfaceClear * 2) * 0.0031 : 0 ;
											$packingCost = ($supplyId==270) ? ($surfaceArea * 0.0031) : 0 ;
										}
										else if($identifier==4)
										{
											$deliveryToSubconProcess = 145;
											$receivingWarehouseProcess = 137;
											
											$surfaceArea = (($thickness*$length*2)+($thickness*$width*2))/10000;
											$surfaceArea = ($length*$width*2/10000)+$surfaceArea;
											
											$sidesNo = 2;
											$sql = "SELECT sidesNumber FROM engineering_subcontreatment WHERE treatmentId = ".$cadamTreatmentId." AND processCode = ".$supplyId." LIMIT 1";
											$querySubconTreatment = $db->query($sql);
											if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
											{
												$resultSubconTreatment = $querySubconTreatment->fetch_assoc();
												$sidesNo = $resultSubconTreatment['sidesNumber'];
												if($supplyId==272)
												{
													$sidesNumber = " ".$sidesNo." Side(s)";
												}
											}
											
											if($supplyId == 273)
											{
												$surfaceArea = ($length*$width*2/10000);
											}
											
											if($sidesNo==1)	$surfaceArea = $surfaceArea/2;
											
											$packingCost = 0.61;
											if($packingCostFlag==1)	$packingCost = 0;
										}
										
										$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$receivingWarehouseProcess." LIMIT 1";
										$queryWorkSchedule = $db->query($sql);
										if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
										{
											$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
											$targetFinish = $resultWorkSchedule['targetFinish'];
											
											if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(5);
											
											//~ $receivingDateArray[] = "Receiving: ".$targetFinish;
											$receivingDate = "Receiving: ".$targetFinish;
										}
										
										$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$deliveryToSubconProcess." LIMIT 1";
										$queryWorkSchedule = $db->query($sql);
										if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
										{
											$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
											$targetFinish = $resultWorkSchedule['targetFinish'];
											
											if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(2);
											//~ $sendingDateArray[] = "Sending: ".$targetFinish;
											$sendingDate = "Sending: ".$targetFinish;
										}
										
										//~ if(strtotime($issue) >= strtotime(date('Y-m-d')))
										//~ {
											//~ $receivingDateArray[] = (in_array($receivingDate,$receivingDateArray)) ? "" : $receivingDate;
											//~ $sendingDateArray[] = (in_array($sendingDate,$sendingDateArray)) ? "" : $sendingDate;
										//~ }
										//~ else
										//~ {
											$receivingDateArray[] = $receivingDate;
											$sendingDateArray[] = $sendingDate;
										//~ }
									}
									
									$specRev = '';
									$sql = "SELECT specificationRevision FROM engineering_specifications WHERE specificationNumber LIKE '".$treatmentProcess."' AND status = 0 ORDER BY specificationDate DESC";
									$querySpecificationRevision = $db->query($sql);
									if($querySpecificationRevision->num_rows > 0)
									{
										$resultSpecificationRevision = $querySpecificationRevision->fetch_array();
										$specRev = "rev.".$resultSpecificationRevision['specificationRevision'];
									}
									
									if($treatmentProcess=='PS1101' AND date('Y-m-d')=='2020-10-22')	$specRev = 'rev.H';//2020-10-22
									
									$treatmentNameArray[] = $treatmentProcess." ".$specRev.$sidesNumber;							
								}
								
								if($packingCost > 0 AND $packingCostFlag==0) $packingCostFlag = 1;
								
								$price = 0;
								$breakFlag = 0;
								$priceCount = 0;
								$sql = "SELECT priceLowerRange, priceUpperRange, price FROM purchasing_price WHERE productId = ".$productId." AND currency = ".$poCurrency."";
								$queryPrice = $db->query($sql);
								if($queryPrice AND $queryPrice->num_rows > 0)
								{
									while($resultPrice = $queryPrice->fetch_assoc())
									{
										$priceLowerRange = $resultPrice['priceLowerRange'];
										$priceUpperRange = $resultPrice['priceUpperRange'];
										$price = $resultPrice['price'];
										//~ if($poNumber=='0011723')	$price = ;
										
										$breakFlag = 0;
										
										if($priceLowerRange != 0 AND $priceUpperRange != 0)
										{
											if($priceLowerRange == $priceUpperRange)
											{
												if($workingQuantity >= $priceLowerRange)	$breakFlag = 1;
											}
											else
											{											
												if($itemQuantity >= $priceLowerRange AND $itemQuantity <= $priceUpperRange)	$breakFlag = 1;
											}
										}
										else
										{
											$breakFlag = 1;
										}
										
										if(++$priceCount == $queryPrice->num_rows)	$breakFlag = 1;
										
										if($breakFlag==1)
										{
											$itemPrice = $price;
											if($supplierType==2 OR $supplyType==2)
											{
												if($surfaceArea > 0)
												{
													$itemPrice = ($price * $surfaceArea)+$packingCost;
												}
											}
											
											if($supplyType==1)
											{
												if($itemUnit==2 AND $productId!=4526 AND $supplierId!=937)
												{
													$materialId = $cadamTreatmentId = '';
													$sql = "SELECT materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
													$querySubconMaterial = $db->query($sql);
													if($querySubconMaterial->num_rows > 0)
													{
														$resultSubconMaterial = $querySubconMaterial->fetch_array();
														$materialId = $resultSubconMaterial['materialId'];
														$cadamTreatmentId = $resultSubconMaterial['treatmentId'];
													}
													
													$materialSpecId = $length = $width = '';
													$sql = "SELECT `materialSpecId`, `length`, `width` FROM `purchasing_material` WHERE `materialId` = ".$materialId." LIMIT 1";
													$queryMaterial = $db->query($sql);
													if($queryMaterial->num_rows > 0)
													{
														$resultMaterial = $queryMaterial->fetch_array();
														$materialSpecId = $resultMaterial['materialSpecId'];
														$length = $resultMaterial['length'];
														$width = $resultMaterial['width'];
													}
													
													$materialTypeId = $metalThickness = '';
													$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
													$queryMaterialSpecs = $db->query($sql);
													if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows)
													{
														$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
														$materialTypeId = $resultMaterialSpecs['materialTypeId'];
														$thickness = $resultMaterialSpecs['metalThickness'];
													}
													
													$baseWeight = $coatingWeight = 0;
													$sql = "SELECT `baseWeight`, `coatingWeight` FROM `engineering_materialtype` WHERE `materialTypeId` = ".$materialTypeId." LIMIT 1";
													$queryMaterialType = $db->query($sql);
													if($queryMaterialType->num_rows > 0)
													{
														$resultMaterialType = $queryMaterialType->fetch_array();
														$baseWeight = $resultMaterialType['baseWeight'];
														$coatingWeight = $resultMaterialType['coatingWeight'];
													}
													
													$itemDescription = $thickness." x ".$length." x ".$width;
													
													if($pvc=='w/PVC')
													{
														$itemPrice += ($supplierId==682) ? 0.10 : 0.15 ; //682 supplierId of Toyota Tsusho
													}
													
													$var1 = $var2 = $var3 = 1;
													//~ if($baseWeight!=0 AND $coatingWeight!=0)
													if($baseWeight!=0)
													{
														$var1 = (($baseWeight*$thickness)+$coatingWeight);
														$var2 = ($length/1000);
														$var3 = ($width/1000);
													}
													
													if($supplierId==3)//Mm Steel
													{
														$var1 = round($var1,4);
														$var2 = round(($length * $width) / 1000000,4);
														$ans1 = ($var1*$var2);
														
														$ans1 = (string)$ans1;
														$decimalPlaces = 0;
														$i = 0;
														$first3Significant = '';
														$finalAns = '';
														while(strlen($first3Significant) < 4)
														{
															if(strstr($finalAns,'.')) $decimalPlaces++;
															if($ans1[$i] == '0' AND $i == 0)
															{
																$finalAns .= $ans1[$i];
															}
															else
															{
																if($ans1[$i]!='.')
																{
																	$first3Significant .= $ans1[$i];
																}
																$finalAns .= $ans1[$i];
															}
															$i++;
															
															if($i > strlen($ans1))	break;
														}
														$ans1 = round($finalAns,($decimalPlaces - 1));
													}
													else
													{
														if($length > 0 AND $width > 0)
														{
															$ans1 = ($var1*$var2*$var3);
														}
														else
														{
															$ans1 = 1;
														}
													}
													
													$itemPrice = ($ans1*$itemPrice);
												}
												else
												{
													if($pvc=='w/PVC')
													{
														//$itemPrice += ($supplierId==682) ? 0.10 : 0.15 ; //682 supplierId of Toyota Tsusho
													}
												}
											}
											
											//~ $customerId = '';
											//~ $sql = "SELECT customerId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
											//~ $queryParts = $db->query($sql);
											//~ if($queryParts AND $queryParts->num_rows > 0)
											//~ {
												//~ $resultParts = $queryParts->fetch_assoc();
												//~ $customerId = $resultParts['customerId'];
											//~ }
											
											//~ if($customerId==45 AND $supplierId==11)
											//~ {
												//~ $itemPrice = $itemPrice - ($itemPrice*0.10);
											//~ }
											$totalUnitPrice += $itemPrice;
											$totalPrice = round($itemPrice,4) * $itemQuantity;
											if($mergeSubconPriceFlag==0) $totalAmount += round($totalPrice,2);
											
											$priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 4, '.', ',') : '';
											// $priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 2, '.', ',') : '';
											$totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
											
											if($supplierType==2)
											{
												$priceInFormatArray[] = $priceInFormat;
												$totalPriceInFormatArray[] = $totalPriceInFormat;
											}
											
											break;
										}
									}
								}
							}
						}
						
						// ---------- START Change Pattern of Item Description of material from thickness x length x width to thickness x width x length (2018-07-06 11:17:10 by Sir Nagano/Sir Ariel/Sir Ace/Ma'am Rose/Ma'am Isabel/Ma'am Geanna/GERALD) ---------- //
						if(strtotime($poInputDateTime) >= strtotime('2018-07-06 11:17:10'))
						{
							if($supplyType==1)
							{
								//~ if($_SESSION['idNumber']=='0346')
								//~ {
									//~ $itemDescription = '';
									$itemDescriptionPart = explode(" x ",$itemDescription);
									if(count($itemDescriptionPart) == 3)
									{
										$itemDescription = $itemDescriptionPart[0]." x ".$itemDescriptionPart[2]." x ".$itemDescriptionPart[1];
									}
								//~ }
							}
						}
						// ---------- END Change Pattern of Item Description of material from thickness x length x width to thickness x width x length (2018-07-06 11:17:10 by Sir Nagano/Sir Ariel/Sir Ace/Ma'am Rose/Ma'am Isabel/Ma'am Geanna/GERALD) ---------- //
						
						if($identifier==1 OR ($identifier==4 AND $supplyType==2) AND $itemDescription=='')	$itemDescription = $partNumber." ".$revisionId;
						if($identifier==4 AND $supplyType==1 AND $pvc!='') $itemDescription .= " ".$pvc;
					}

					if($totalUnitPrice > 0 AND $mergeSubconPriceFlag==1)
					{
						$receivingDateArray = array_unique($receivingDateArray);
						$sendingDateArray = array_unique($sendingDateArray);

						$itemPrice = $totalUnitPrice;

						$totalPrice = round($itemPrice,4) * $itemQuantity;
						$totalAmount += round($totalPrice,2);
						
						$priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 4, '.', ',') : '';
						// $priceInFormat = ($itemPrice > 0) ? $sign." ".number_format($itemPrice, 2, '.', ',') : '';
						$totalPriceInFormat = ($totalPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';							
						foreach($priceInFormatArray as $key => $value)
						{
							if($key==0)
							{
								$priceInFormatArray[$key] = $priceInFormat;
								$totalPriceInFormatArray[$key] = $totalPriceInFormat;
							}
							else
							{
								$priceInFormatArray[$key] = ' ';
								$totalPriceInFormatArray[$key] = ' ';	
							}
						}
					}					
					
					$unitName = '';
					$sql = "SELECT unitName FROM purchasing_units WHERE unitId = ".$itemUnit." LIMIT 1";
					$queryUnits = $db->query($sql);
					if($queryUnits AND $queryUnits->num_rows > 0)
					{
						$resultUnits = $queryUnits->fetch_assoc();
						$unitName = $resultUnits['unitName'];
					}
					//~ echo "<hr>".$supplyType;
					//~ echo "<br>".$itemUnit;
					if($supplierId!=937)
					{
						if($supplyType==1 AND $itemUnit==2 AND !in_array($poNumber,array('0009687','0009781','0009971','0010121')))
						{
							//~ if($productId!=4526)//2018-06-19 remove by geanna
							//~ {
								$unitName = 'sheets';
							//~ }
						}
					}
					
					if($poNumber=='0011926')	$unitName = 'kg';
					
					$itemQuantity = wholeNumber($itemQuantity);
					//~ echo "<br>if($identifier==4 AND $supplyType==1 AND strtotime($poInputDateTime) >= strtotime('2019-12-17'))";
					if($identifier==4 AND $supplyType==1 AND (strtotime($poInputDateTime) >= strtotime('2019-12-17') OR $poInputDateTime==''))
					{
						if(in_array($supplierId,array(765,898,80,937)))
						{
							$aeroMaterial = '';
							$sql = "
								SELECT aeroMaterialCode, aeroMaterialSpecs, aeroMaterialRemarks FROM purchasing_material as a
								INNER JOIN cadcam_materialspecs as b ON b.materialSpecId = a.materialSpecId
								INNER JOIN engineering_materialtype as c ON c.materialTypeId = b.materialTypeId
								INNER JOIN purchasing_materialtreatment as d ON d.materialId = a.materialId
								WHERE d.materialTreatmentId = ".$partId." LIMIT 1
							";
							$queryAeroMaterial = $db->query($sql);
							if($queryAeroMaterial AND $queryAeroMaterial->num_rows > 0)
							{
								$resultAeroMaterial = $queryAeroMaterial->fetch_assoc();
								$aeroMaterialCode = $resultAeroMaterial['aeroMaterialCode'];
								$aeroMaterialSpecs = $resultAeroMaterial['aeroMaterialSpecs'];
								$aeroMaterialRemarks = $resultAeroMaterial['aeroMaterialRemarks'];
								
								//~ $aeroMaterial = $aeroMaterialCode." ".$aeroMaterialSpecs;
							}
							
							//~ $itemName = $aeroMaterialRemarks." ".$itemName." ".$aeroMaterial;
							$itemName = $aeroMaterialRemarks." ".$aeroMaterialCode." ".$itemName." ".$aeroMaterialSpecs;//Change order format by Kim 2021-03-09
						}
					}
					
					if($seeAttachFlag == 0)
					{
						if($poStatus != 2 AND $poContentStatus == 2)
						{
							$pdf->SetLineWidth(0.5);
							$pdf->SetDrawColor(255,0,0);
							$currentY = $pdf->GetY();
							$pdf->Line(10.00125,($currentY+2.5),200.00125,($currentY+2.5));
							$totalAmount -= $totalPrice;
							$pdf->SetLineWidth(0.2);
							$pdf->SetDrawColor(0,0,0);
						}
						
						//~ if($_SESSION['idNumber']=='0346')
						//~ {
							//~ if($count >= 4)
							//~ {
								//~ $pdf->SetTextColor(0,0,0);
							//~ }
						//~ }
						
						if($supplierType==2)
						{
							$treatmentNameString = implode("\n",$treatmentNameArray);
							$priceInFormat = implode("\n",$priceInFormatArray);
							$totalPriceInFormat = implode("\n",$totalPriceInFormatArray);
							$sendingDateString = implode("\n",$sendingDateArray);
							$receivingDateString = implode("\n",$receivingDateArray);
							
							$pdf->SetFont('Arial','',10);//B
							$pdf->Cell(12,5,++$count.".",0,0,'L');
							$pdf->Cell(13,5,$itemQuantity,0,0,'R');
							$pdf->SetFont('Arial','',9);
							$pdf->Cell(8,5,"pcs",0,0,'L');
							$pdf->SetFont('Arial','',8);//B
							if($subpartLotNumber!='')
							{
								$pdf->Cell(47,5,'',0,0,'L');
								$pdf->Cell(20,5,'',0,0,'L');
							}
							else
							{
								if($_GET['country']==2)	$pdf->SetFont('SJIS','',8);
								$pdf->Cell(47,5,$itemDescription,0,0,'L');
								if($_GET['country']==2)	$pdf->SetFont('Arial','',8);
								$pdf->Cell(20,5,$lotNumber,0,0,'L');
							}
							$pdf->Cell(25,5,$metalType,0,0,'C');
							$pdf->Cell(28,5,'',0,0,'R');
							$pdf->Cell(1,5,'',0);
							$pdf->Cell(29,5,'',0,0,'R');
							$pdf->Cell(1,5,'',0);
							$pdf->Ln(4);
							$currentY = $pdf->GetY();
							$pdf->Cell(32,5,$partNote,0,0,'R');
							$pdf->SetFont('Arial','',8);
							if($_GET['country']==2)	$pdf->SetFont('SJIS','',8);
							$pdf->MultiCell(37,3,$treatmentNameString,0,'L');
							if($_GET['country']==2)	$pdf->SetFont('Arial','',8);
							if(strlen($sendingDateString) > 0) $pdf->SetFont('Arial','UI',8);
							$pdf->SetXY(79,$currentY);
							$pdf->MultiCell(29,3,$sendingDateString,0,'L');
							if($subpartLotNumber!='')
							{
								$pdf->SetFont('Arial','',8);//B
								$pdf->SetXY(92,$currentY);
								$pdf->MultiCell(22,3,$subpartLotNumber,0,'L');
								$pdf->SetFont('Arial','UI',8);
							}
							if(strlen($receivingDateString) > 0) $pdf->SetFont('Arial','UI',8);
							$pdf->SetXY(108,$currentY);
							$pdf->MultiCell(33,3,$receivingDateString,0,'L');
							$pdf->SetXY(141,$currentY);
							$pdf->SetFont('Arial','',8);
							$pdf->MultiCell(25,3,$priceInFormat,0,'R');
							$pdf->SetXY(167,$currentY);
							//~ $pdf->Cell(1,5,'',0);
							$pdf->MultiCell(29,3,$totalPriceInFormat,0,'R');
							$pdf->Cell(2,5,'',0);						
							$pdf->Ln(0.1);
						}
						else
						{
							/* Temporary
							if(count($partialReceivingArray) > 0)	$itemDescription .= "\n".implode("\n",$partialReceivingArray);
							*/
							
							if(($delivery=='' OR $delivery=='0000-00-00') AND $identifier==4 AND $supplyType==1)
							{
								if(count($partialReceivingArray) > 0)	$itemDescription .= "\n".implode("\n",$partialReceivingArray);
							}
							
							//~ $itemDescription = Iconv('UTF-8','ISO-8859-1//TRANSLIT',$itemDescription);
							//~ $itemDescription = iconv('UTF-8', 'windows-1252', $itemDescription);
							
							$pdf->SetFont('Arial','',10);//Bold
							$pdf->Cell(12,5,++$count.".",0,0,'L');
							$pdf->Cell(7,5,$itemQuantity,0,0,'R');
							if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
							$pdf->Cell(14,5,$unitName,0,0,'L');
							$pdf->Cell(92,5,$itemName,0,0,'L');
							if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
							$pdf->Cell(28,5,$priceInFormat,0,0,'R');
							$pdf->Cell(1,5,'',0);
							$pdf->Cell(29,5,$totalPriceInFormat,0,0,'R');
							$pdf->Cell(1,5,'',0);
							$pdf->Ln(4);
							$pdf->Cell(12,4,"",0,0,'L');
							$pdf->Cell(7,4,"",0,0,'R');
							$pdf->Cell(14,4,"",0,0,'L');
							if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
							$pdf->MultiCell(92,4,$itemDescription,0,'L');
							if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
						}
						$currentY = $pdf->GetY();
						//~ echo "<br>".$currentY;
						if($seeAttachFlag == 0 AND ($currentY) >= 237)
						{
							$seeAttachFlag = 1;
							goto start;
						}
					}
					else
					{
						$contentData = array();
						if($supplierType==2)
						{					
							$treatmentNameString = implode("\n",$treatmentNameArray);
							$priceInFormat = implode("\n",$priceInFormatArray);
							$totalPriceInFormat = implode("\n",$totalPriceInFormatArray);
							$sendingDateString = implode("\n",$sendingDateArray);
							$receivingDateString = implode("\n",$receivingDateArray);
							
							$contentData['itemQuantity'] = $itemQuantity;
							$contentData['itemDescription'] = $itemDescription;
							$contentData['lotNumber'] = $lotNumber;
							$contentData['metalType'] = $metalType;
							$contentData['partNote'] = $partNote;
							$contentData['treatmentNameString'] = $treatmentNameString;
							$contentData['subpartLotNumber'] = $subpartLotNumber;
							$contentData['sendingDateString'] = $sendingDateString;
							$contentData['receivingDateString'] = $receivingDateString;
							$contentData['priceInFormat'] = $priceInFormat;
							$contentData['totalPriceInFormat'] = $totalPriceInFormat;
						}
						else
						{
							/* Temporary
							if(count($partialReceivingArray) > 0)	$itemDescription .= "\n".implode("\n",$partialReceivingArray);				
							*/
							
							if($delivery=='' AND $identifier==4 AND $supplyType==1)
							{
								if(count($partialReceivingArray) > 0)	$itemDescription .= "\n".implode("\n",$partialReceivingArray);
							}						
							
							$contentData['itemQuantity'] = $itemQuantity;
							$contentData['unitName'] = $unitName;
							$contentData['itemName'] = $itemName;
							$contentData['priceInFormat'] = $priceInFormat;
							$contentData['totalPriceInFormat'] = $totalPriceInFormat;
							$contentData['itemDescription'] = $itemDescription;
						}
						$contentDataArray[] = $contentData;
					}
				}
			}
			
			if(count($chargeDescriptionArray) > 0)
			{
				foreach($chargeDescriptionArray as $key => $val)
				{
					$description = explode("\n",$val);
					
					$chargeQuantity = $chargeQuantityArray[$key];
					$chargeUnit = $chargeUnitArray[$key];
					$unitPrice = $chargeUnitPriceArray[$key];
					
					$unitName = '';
					$sql = "SELECT unitName FROM purchasing_units WHERE unitId = ".$chargeUnit." LIMIT 1";
					$queryUnits = $db->query($sql);
					if($queryUnits->num_rows > 0)
					{
						$resultUnits = $queryUnits->fetch_array();
						$unitName = $resultUnits['unitName'];
					}
					
					$totalPrice = $unitPrice * $chargeQuantity;
					
					$priceInFormat = ($unitPrice > 0) ? $sign." ".number_format($unitPrice, 4, '.', ',') : '';
					// $priceInFormat = ($unitPrice > 0) ? $sign." ".number_format($unitPrice, 2, '.', ',') : '';
					$totalPriceInFormat = ($unitPrice > 0) ? $sign." ".number_format(($totalPrice), 2, '.', ',') : ' ';
					
					$totalAmount += $unitPrice;
					
					if($seeAttachFlag == 0)
					{				
						$pdf->SetFont('Arial','',10);//Bold
						$pdf->Cell(12,5,++$count.".",0,0,'L');
						$pdf->Cell(7,5,$chargeQuantity,0,0,'R');
						if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
						$pdf->Cell(14,5,$unitName,0,0,'L');
						$pdf->Cell(92,5,$description[0],0,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
						$pdf->Cell(28,5,$priceInFormat,0,0,'R');
						$pdf->Cell(1,5,'',0);
						$pdf->Cell(29,5,$totalPriceInFormat,0,0,'R');
						$pdf->Cell(1,5,'',0);
						$pdf->Ln(4);
						$pdf->Cell(12,4,"",0,0,'L');
						$pdf->Cell(7,4,"",0,0,'R');
						$pdf->Cell(14,4,"",0,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
						$pdf->MultiCell(92,4,$description[1],0,'L');
						if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
					}
					else
					{
						$chargesData = array();
						$chargesData['chargeQuantity'] = $chargeQuantity;
						$chargesData['unitName'] = $unitName;
						$chargesData['description1'] = $description[0];
						$chargesData['priceInFormat'] = $priceInFormat;
						$chargesData['totalPriceInFormat'] = $totalPriceInFormat;
						$chargesData['description2'] = $description[1];
						$chargesDataArray[] = $chargesData;
					}
				}
			}
		}
	}
		
	if($seeAttachFlag == 1)
	{
		//~ $pdf->Cell(190,5,"SEE ATTACHED",0,0,'C',false,'http://192.168.254.163/4-9 Purchase Order Making Software/gerald_purchaseOrderAttachment.php?poNumber='.$poNumber);
		$pdf->Cell(184,5,"SEE ATTACHED",0,0,'C');
	}
	else
	{
		$pdf->SetFont('Arial','',10);//Bold
		$pdf->Cell(160,5,"********NOTHING FOLLOWS********",0,0,'C');//190
		$pdf->Ln();
		$pdf->SetTextColor(0,0,0);
		//~ if($poNumber!='0009781' OR $poNumber!='0011926')
		if(!in_array($poNumber,array('0009781','0011926')))
		{
			$multiCellHeight = $pdf->getMultiCellHeight($poRemarks,160,5);
		}
		
		$currentY = $pdf->GetY();
		if(in_array($poNumber,array('0009781','0011926')))
		{
			$pdf->SetFont('Arial','',9);//Bold
			$pdf->Cell(35,5,"",0,0,'C');//190
			$pdf->MultiCell(125,4,$poRemarks,0,'L');
		}
		else
		{
			$pdf->MultiCell(154,5,$poRemarks,0,'C');
		}
	}
	
	if($poNumber=='0011950')	$totalAmount = 1955200;//2018-12-10
	
	$totalAmountInFormat = ($totalAmount > 0) ? $sign." ".number_format($totalAmount, 2, '.', ',') : '';
	$subTotalInFormat = $totalAmountInFormat;
	
	if($totalAmount == 0 AND $poDiscount < 0) $totalAmountInFormat = $sign." ".number_format(abs($poDiscount), 2, '.', ',');
	
	if($seeAttachFlag == 0 AND ($currentY + $multiCellHeight) >= 237)
	{
		$seeAttachFlag = 1;
		goto start;
	}
	
	$pdf->SetXY(167.00125,231.00125);
	$pdf->Cell(29,5,$totalAmountInFormat,0,0,'R');
	
	$poDiscountInFormat = '';
	if($poDiscount > 0)
	{
		$pdf->SetY(247.00125);
		$pdf->Cell(154,5,'(Discount)',0,0,'R');
		$poDiscountInFormat = $sign." ".number_format($poDiscount, 2, '.', ',');
	}
	else if($poDiscount < 0)
	{
		$poDiscountInFormat = '';
		if($totalAmount != 0)
		{
			$pdf->SetY(247.00125);
			$pdf->Cell(154,5,'(Charges)',0,0,'R');
			$poDiscountInFormat = $sign." ".number_format(abs($poDiscount), 2, '.', ',');
		}
	}
	
	$totalAmount -= $poDiscount;
	
	$pdf->SetXY(167.00125,247.00125);
	$pdf->Cell(29,5,$poDiscountInFormat,0,0,'R');
	
	$totalAmountInFormat = ($totalAmount > 0) ? $sign." ".number_format($totalAmount, 2, '.', ',') : '';
	
	$pdf->SetXY(167.00125,252.00125);
	$pdf->Cell(29,7.5,$totalAmountInFormat,0,0,'R');
	
	//~ //nagano
	//~ $pdf->Image($path.'Templates/images/nagano.jpg',104,263,28,10);
	//~ //nagano
	
	$checked = '';
	if($checkedBy != '')
	{
		/*
		if($checkedBy=='0399')	$checkedBy = '0048';
		if($checkedBy=='0557')	$checkedBy = '0048';
		if($checkedBy=='0565')	$checkedBy = '0048';
		if($checkedBy=='0449')	$checkedBy = '0048';
		if($checkedBy=='0470')	$checkedBy = '0048';
		if($checkedBy=='0466')	$checkedBy = '0048';
		if($checkedBy=='0574')	$checkedBy = '0048';
		if($checkedBy=='0588')	$checkedBy = '0048';
		if($checkedBy=='0600')	$checkedBy = '0048';
		if($checkedBy=='0601')	$checkedBy = '0048';
		if($checkedBy=='0541')	$checkedBy = '0048';
		if($checkedBy=='0775')	$checkedBy = '0048';
		if($checkedBy=='0772')	$checkedBy = '0048';
		if($checkedBy=='0346')	$checkedBy = '0346';
		*/
		
		$checkedBy = '0048';
		
		$sql = "SELECT CONCAT(firstName,' ',surName) as employeeName FROM hr_employee WHERE idNumber LIKE '".$checkedBy."' LIMIT 1";
		$queryEmployee = $db->query($sql);
		if($queryEmployee AND $queryEmployee->num_rows > 0)
		{
			$resultEmployee = $queryEmployee->fetch_assoc();
			$checked = $resultEmployee['employeeName'];
		}
		
		$checkedBySignatureFlag = 0;
		//~ if($_SESSION['idNumber']=='0346')
		//~ {
			if(strtotime($poInputDateTime) >= strtotime('2020-10-01'))
			{
				$checkedBySignatureFlag = 1;
				$sql = "SELECT prId FROM purchasing_prcontent WHERE lotNumber IN('".implode("','",$lotNumberArray)."') LIMIT 1";
				$queryPrContent = $db->query($sql);
				if($queryPrContent AND $queryPrContent->num_rows > 0)
				{
					$checkedBySignatureFlag = 0;
				}
				if(in_array($_SESSION['idNumber'],['0346','0280']))
				{
					$checkedBySignatureFlag = 1;
				}
				if($checkedBySignatureFlag==1 AND stristr($poNumber,'-')===false)
				{
					$pdf->Image($path.'Templates/images/sirAriel.jpg',32,263,28,10);
				}
				
			}
		//~ }
	}
	
	if($approvedBy != '')
	{
		if($approvedBy=='0331')	$approvedBy = '0227';
		if($checkedBy=='0449')	$checkedBy = '0048';
		//~ if($checkedBy=='0470')	$checkedBy = '0048';
		//~ if($checkedBy=='0466')	$checkedBy = '0048';
		
		$productIdArray = array();
		$sql = "SELECT DISTINCT productId FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
		$queryPoContents = $db->query($sql);
		if($queryPoContents AND $queryPoContents->num_rows > 0)
		{
			while($resultPoContents = $queryPoContents->fetch_assoc())
			{
				$productIdArray[] = $resultPoContents['productId'];
			}
			$sql = "SELECT poNumber FROM purchasing_pocontents WHERE productId IN(".implode(",",$productIdArray).") LIMIT 1";
			$queryExistingItem = $db->query($sql);
			if($queryExistingItem AND $queryExistingItem->num_rows > 0)
			{
				/*
				if($approvedBy = '0227')
				{
					$pdf->Image($path.'Templates/images/nagano.jpg',104,263,28,10);
					//~ $pdf->Image($path.'Templates/images/0275.jpg',104,258,41,22);
				}
				*/
			}
		}
		else
		{
			$productIdArray = array();
			$sql = "SELECT DISTINCT productId FROM purchasing_forpurchaseorder WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency." GROUP BY lotNumber";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$productIdArray[] = $resultWorkSchedule['productId'];
				}
			}			
		}
		
		if(isset($poInputDateTime))//2018-06-23 by sir Nagano all BE items to KAPCO
		{
			if($signatureFlag==1)
			{
				if(strtotime($poInputDateTime) >= strtotime('2021-03-23'))
				{
					//$pdf->Image($path.'Templates/images/shachou.jpg',104,263,28,10);//commented 2021-08-03 rose
				}
				else if(strtotime($poInputDateTime) >= strtotime('2018-06-23'))
				{
					$pdf->Image($path.'Templates/images/nagano.jpg',104,263,28,10);
				}
			}
		}
		
		if(strtotime($poInputDateTime) >= strtotime('2019-09-12 11:04:00'))
		{
			$vp = 'KAZUMASA NAGANO';
		}
		else
		{
			$vp = 'NOBUYOSHI SUZUKI';
		}
		$president = 'HIDEHIKO ARAKAWA';
	}
	
	$pdf->SetXY(13.00125,270.00125);
	$pdf->Cell(51,5,strtoupper($checked),0,0,'C');		
	
	$pdf->SetXY(102.00125,270.00125);
	$pdf->Cell(43,5,$president,0,0,'C');
	
	// ************************************************************ ATTACHMENT ************************************************************ //
	if($seeAttachFlag==1 AND $previewFlag==1)
	{
		//~ include('gerald_purchaseOrderAttachment.php');
		$pdf->SetLeftMargin(10);
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',12);
		$pdf->Cell(193,10,"PO ATTACHMENT",1,0,'C');$pdf->Ln();
		$pdf->Cell(193,8,"PO NUMBER : ".$poNumber,1,0,'L');$pdf->Ln();
		$pdf->Cell(193,8,"TO : ".$supplierName,1,0,'L');$pdf->Ln();
		
		$pdf->SetFont('Arial','I',9);
		$pdf->Cell(12,7.5,'ITEM',1,0,'C');
		$pdf->Cell(20,7.5,'QUANTITY',1,0,'C');
		$pdf->Cell(99,7.5,'DESCRIPTION',1,0,'C');
		$pdf->Cell(32,7.5,'UNIT PRICE',1,0,'C');
		$pdf->Cell(30,7.5,'AMOUNT',1,0,'C');
		$pdf->Ln();		
		$memoCurrentY = $pdf->GetY();
		
		if($supplierType == 2)
		{
			$pdf->Cell(12,5,'',0,0,'L');
			$pdf->Cell(14,5,'',0,0,'R');
			$pdf->Cell(6,5,'',0,0,'L');
			$pdf->SetFont('Arial','U',10);;
			$pdf->Cell(50,5,'Part Number',0,0,'L');
			$pdf->Cell(22,5,'Lot Number',0,0,'L');
			$pdf->Cell(27,5,'Mat Type',0,0,'C');
			$pdf->SetFont('Arial','',10);
			$pdf->Cell(27,5,'',0,0,'R');
			$pdf->Cell(5,5,'',0);
			$pdf->Cell(25,5,'',0,0,'R');
			$pdf->Cell(5,5,'',0);
			$pdf->Ln();
		}
		
		if(count($contentDataArray) > 0)
		{
			$count = 0;
			//~ $loopCounter = 0;
			//~ while($loopCounter < 3)
			//~ {
				//~ $loopCounter++;
				foreach($contentDataArray as $contentData)
				{
					//~ if($poStatus != 2 AND $poContentStatus == 2)
					//~ {
						//~ $pdf->SetLineWidth(0.5);
						//~ $pdf->SetDrawColor(255,0,0);
						//~ $currentY = $pdf->GetY();
						//~ $pdf->Line(10.00125,($currentY+2.5),200.00125,($currentY+2.5));
						//~ $totalAmount -= $totalPrice;
						//~ $pdf->SetLineWidth(0.2);
						//~ $pdf->SetDrawColor(0,0,0);
					//~ }
					
					if($supplierType==2)
					{
						$itemQuantity = $contentData['itemQuantity'];
						$itemDescription = $contentData['itemDescription'];
						$lotNumber = $contentData['lotNumber'];
						$metalType = $contentData['metalType'];
						$partNote = $contentData['partNote'];
						$treatmentNameString = $contentData['treatmentNameString'];
						$subpartLotNumber = $contentData['subpartLotNumber'];
						$sendingDateString = $contentData['sendingDateString'];
						$receivingDateString = $contentData['receivingDateString'];
						$priceInFormat = $contentData['priceInFormat'];
						$totalPriceInFormat = $contentData['totalPriceInFormat'];
						/*
						$multiCellHeight = $pdf->getMultiCellHeight($itemDescription,96,4);
						
						if(($currentY + $multiCellHeight) >= 280)
						{
							$boxHeight = $currentY - $memoCurrentY;
							
							$pdf->SetY($memoCurrentY);
							$pdf->Cell(12,($boxHeight),'',1,0,'C');
							$pdf->Cell(20,($boxHeight),'',1,0,'C');
							$pdf->Cell(99,($boxHeight),'',1,0,'C');
							$pdf->Cell(32,($boxHeight),'',1,0,'C');
							$pdf->Cell(30,($boxHeight),'',1,0,'C');
							$pdf->AddPage();
							$pdf->SetFont('Arial','B',12);
							$pdf->Cell(193,10,"PO ATTACHMENT",1,0,'C');$pdf->Ln();
							$pdf->Cell(193,8,"PO NUMBER : ".$poNumber,1,0,'L');$pdf->Ln();
							$pdf->Cell(193,8,"TO : ".$supplierName,1,0,'L');$pdf->Ln();
							
							$pdf->SetFont('Arial','I',9);
							$pdf->Cell(12,7.5,'ITEM',1,0,'C');
							$pdf->Cell(20,7.5,'QUANTITY',1,0,'C');
							$pdf->Cell(99,7.5,'DESCRIPTION',1,0,'C');
							$pdf->Cell(32,7.5,'UNIT PRICE',1,0,'C');
							$pdf->Cell(30,7.5,'AMOUNT',1,0,'C');
							$pdf->Ln();		
							$memoCurrentY = $pdf->GetY();
							
							if($supplierType == 2)
							{
								$pdf->Cell(12,5,'',0,0,'L');
								$pdf->Cell(14,5,'',0,0,'R');
								$pdf->Cell(6,5,'',0,0,'L');
								$pdf->SetFont('Arial','U',10);;
								$pdf->Cell(50,5,'Part Number',0,0,'L');
								$pdf->Cell(22,5,'Lot Number',0,0,'L');
								$pdf->Cell(27,5,'Mat Type',0,0,'C');
								$pdf->SetFont('Arial','',10);
								$pdf->Cell(27,5,'',0,0,'R');
								$pdf->Cell(5,5,'',0);
								$pdf->Cell(25,5,'',0,0,'R');
								$pdf->Cell(5,5,'',0);
								$pdf->Ln();
							}
						}*/
						
						$pdf->SetFont('Arial','',10);//B
						$pdf->Cell(12,5,++$count.".",0,0,'L');
						$pdf->Cell(12,5,$itemQuantity,0,0,'R');
						$pdf->SetFont('Arial','',9);
						$pdf->Cell(8,5,"pcs",0,0,'L');
						$pdf->SetFont('Arial','',8);//B
						if($subpartLotNumber!='')
						{
							$pdf->Cell(50,5,'',0,0,'L');
							$pdf->Cell(22,5,'',0,0,'L');
						}
						else
						{
							$pdf->Cell(50,5,$itemDescription,0,0,'L');
							$pdf->Cell(22,5,$lotNumber,0,0,'L');
						}
						$pdf->Cell(27,5,$metalType,0,0,'C');
						$pdf->Cell(27,5,'',0,0,'R');
						$pdf->Cell(5,5,'',0);
						$pdf->Cell(25,5,'',0,0,'R');
						$pdf->Cell(5,5,'',0);
						$pdf->Ln(4);
						$currentY = $pdf->GetY();
						$pdf->Cell(32,5,$partNote,0,0,'R');
						$pdf->SetFont('Arial','',8);
						$pdf->MultiCell(37,3,$treatmentNameString,0,'L');
						$pdf->SetFont('Arial','UI',8);
						$pdf->SetXY(79,$currentY);
						$pdf->MultiCell(29,3,$sendingDateString,0,'L');
						if($subpartLotNumber!='')
						{
							$pdf->SetFont('Arial','',8);//B
							$pdf->SetXY(92,$currentY);
							$pdf->MultiCell(22,3,$subpartLotNumber,0,'L');
							$pdf->SetFont('Arial','UI',8);
						}
						$pdf->SetXY(108,$currentY);
						$pdf->MultiCell(33,3,$receivingDateString,0,'L');
						$pdf->SetXY(141,$currentY);
						$pdf->SetFont('Arial','',8);
						$pdf->MultiCell(31,3,$priceInFormat,0,'C');
						$pdf->SetXY(171,$currentY);
						//~ $pdf->Cell(1,5,'',0);
						$pdf->MultiCell(29,3,$totalPriceInFormat,0,'R');
						$pdf->Cell(2,5,'',0);						
						$pdf->Ln(0.1);
						//~ if($lotNumber=='17-07-1590-1')	echo "asd";
					}
					else
					{
						$itemQuantity = $contentData['itemQuantity'];
						$unitName = $contentData['unitName'];
						$itemName = $contentData['itemName'];
						$priceInFormat = $contentData['priceInFormat'];
						$totalPriceInFormat = $contentData['totalPriceInFormat'];
						$itemDescription = $contentData['itemDescription'];
						/*
						$multiCellHeight = $pdf->getMultiCellHeight($itemDescription,96,4);
						
						if(($currentY + $multiCellHeight) >= 280)
						{
							$boxHeight = $currentY - $memoCurrentY;
							
							$pdf->SetY($memoCurrentY);
							$pdf->Cell(12,($boxHeight),'',1,0,'C');
							$pdf->Cell(20,($boxHeight),'',1,0,'C');
							$pdf->Cell(99,($boxHeight),'',1,0,'C');
							$pdf->Cell(32,($boxHeight),'',1,0,'C');
							$pdf->Cell(30,($boxHeight),'',1,0,'C');
							$pdf->AddPage();
							$pdf->SetFont('Arial','B',12);
							$pdf->Cell(193,10,"PO ATTACHMENT",1,0,'C');$pdf->Ln();
							$pdf->Cell(193,8,"PO NUMBER : ".$poNumber,1,0,'L');$pdf->Ln();
							$pdf->Cell(193,8,"TO : ".$supplierName,1,0,'L');$pdf->Ln();
							
							$pdf->SetFont('Arial','I',9);
							$pdf->Cell(12,7.5,'ITEM',1,0,'C');
							$pdf->Cell(20,7.5,'QUANTITY',1,0,'C');
							$pdf->Cell(99,7.5,'DESCRIPTION',1,0,'C');
							$pdf->Cell(32,7.5,'UNIT PRICE',1,0,'C');
							$pdf->Cell(30,7.5,'AMOUNT',1,0,'C');
							$pdf->Ln();		
							$memoCurrentY = $pdf->GetY();
							
							if($supplierType == 2)
							{
								$pdf->Cell(12,5,'',0,0,'L');
								$pdf->Cell(14,5,'',0,0,'R');
								$pdf->Cell(6,5,'',0,0,'L');
								$pdf->SetFont('Arial','U',10);;
								$pdf->Cell(50,5,'Part Number',0,0,'L');
								$pdf->Cell(22,5,'Lot Number',0,0,'L');
								$pdf->Cell(27,5,'Mat Type',0,0,'C');
								$pdf->SetFont('Arial','',10);
								$pdf->Cell(27,5,'',0,0,'R');
								$pdf->Cell(5,5,'',0);
								$pdf->Cell(25,5,'',0,0,'R');
								$pdf->Cell(5,5,'',0);
								$pdf->Ln();
							}
						}*/
						
						$pdf->SetFont('Arial','',10);//Bold
						$pdf->Cell(11,5,++$count.".",0,0,'L');
						//~ $pdf->Cell(7,5,$itemQuantity,0,0,'R');
						//~ $pdf->Cell(14,5,$unitName,0,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
						$pdf->Cell(21,5,$itemQuantity." ".$unitName,0,0,'R');
						$pdf->Cell(96,5,$itemName,0,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
						$pdf->Cell(31,5,$priceInFormat,0,0,'R');
						$pdf->Cell(1,5,'',0);
						$pdf->Cell(29,5,$totalPriceInFormat,0,0,'R');
						$pdf->Cell(1,5,'',0);
						$pdf->Ln(4);
						$pdf->Cell(11,4,"",0,0,'L');
						$pdf->Cell(7,4,"",0,0,'R');
						$pdf->Cell(14,4,"",0,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
						$pdf->MultiCell(96,4,$itemDescription,0,'L');
						if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
					}
					
					$currentY = $pdf->GetY();
					if($currentY >= 280)
					{
						$boxHeight = $currentY - $memoCurrentY;
						
						$pdf->SetY($memoCurrentY);
						$pdf->Cell(12,($boxHeight),'',1,0,'C');
						$pdf->Cell(20,($boxHeight),'',1,0,'C');
						$pdf->Cell(99,($boxHeight),'',1,0,'C');
						$pdf->Cell(32,($boxHeight),'',1,0,'C');
						$pdf->Cell(30,($boxHeight),'',1,0,'C');
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',12);
						$pdf->Cell(193,10,"PO ATTACHMENT",1,0,'C');$pdf->Ln();
						$pdf->Cell(193,8,"PO NUMBER : ".$poNumber,1,0,'L');$pdf->Ln();
						$pdf->Cell(193,8,"TO : ".$supplierName,1,0,'L');$pdf->Ln();
						
						$pdf->SetFont('Arial','I',9);
						$pdf->Cell(12,7.5,'ITEM',1,0,'C');
						$pdf->Cell(20,7.5,'QUANTITY',1,0,'C');
						$pdf->Cell(99,7.5,'DESCRIPTION',1,0,'C');
						$pdf->Cell(32,7.5,'UNIT PRICE',1,0,'C');
						$pdf->Cell(30,7.5,'AMOUNT',1,0,'C');
						$pdf->Ln();		
						$memoCurrentY = $pdf->GetY();
						
						if($supplierType == 2)
						{
							$pdf->Cell(12,5,'',0,0,'L');
							$pdf->Cell(14,5,'',0,0,'R');
							$pdf->Cell(6,5,'',0,0,'L');
							$pdf->SetFont('Arial','U',10);;
							$pdf->Cell(50,5,'Part Number',0,0,'L');
							$pdf->Cell(22,5,'Lot Number',0,0,'L');
							$pdf->Cell(27,5,'Mat Type',0,0,'C');
							$pdf->SetFont('Arial','',10);
							$pdf->Cell(27,5,'',0,0,'R');
							$pdf->Cell(5,5,'',0);
							$pdf->Cell(25,5,'',0,0,'R');
							$pdf->Cell(5,5,'',0);
							$pdf->Ln();
						}
					}
				}
			//~ }
			
			if(count($chargesDataArray) > 0)
			{
				foreach($chargesDataArray as $chargesData)
				{
					$chargeQuantity = $chargesData['chargeQuantity'];
					$unitName = $chargesData['unitName'];
					$description1 = $chargesData['description1'];
					$priceInFormat = $chargesData['priceInFormat'];
					$totalPriceInFormat = $chargesData['totalPriceInFormat'];
					$description2 = $chargesData['description2'];
					
					$pdf->SetFont('Arial','',10);//Bold
					$pdf->Cell(11,5,++$count.".",0,0,'L');
					$pdf->Cell(7,5,$chargeQuantity,0,0,'R');
					if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
					$pdf->Cell(14,5,$unitName,0,0,'L');
					$pdf->Cell(96,5,$description1,0,0,'L');
					if($_GET['country']==2)	$pdf->SetFont('Arial','',10);
					$pdf->Cell(31,5,$priceInFormat,0,0,'R');
					$pdf->Cell(1,5,'',0);
					$pdf->Cell(29,5,$totalPriceInFormat,0,0,'R');
					$pdf->Cell(1,5,'',0);
					$pdf->Ln(4);
					$pdf->Cell(11,4,"",0,0,'L');
					$pdf->Cell(7,4,"",0,0,'R');
					$pdf->Cell(14,4,"",0,0,'L');
					if($_GET['country']==2)	$pdf->SetFont('SJIS','',10);
					$pdf->MultiCell(96,4,$description2,0,'L');			
					if($_GET['country']==2)	$pdf->SetFont('Arial','',10);		
				}
			}
			
			$pdf->SetFont('Arial','',10);//Bold
			$pdf->Cell(160,5,"********NOTHING FOLLOWS********",0,0,'C');//190
			$pdf->Ln();
			$pdf->SetTextColor(0,0,0);
			$pdf->MultiCell(160,5,$poRemarks,0,'C');			
			$currentY = $pdf->GetY();
			
			$boxHeight = $currentY - $memoCurrentY;
			
			$pdf->SetY($memoCurrentY);
			$pdf->Cell(12,($boxHeight),'',1,0,'C');
			$pdf->Cell(20,($boxHeight),'',1,0,'C');
			$pdf->Cell(99,($boxHeight),'',1,0,'C');
			$pdf->Cell(32,($boxHeight),'',1,0,'C');
			$pdf->Cell(30,($boxHeight),'',1,0,'C');	
			
			$pdf->SetFont('Arial','IB',7);
			$pdf->Ln();
			$currentY = $pdf->GetY();
			//~ if($poNumber=='0010471' OR $poNumber=='0010507')
			//~ {
				//~ $pdf->addPage();
				//~ $currentY = $pdf->GetY();
			//~ }
			
			if($currentY+(6.5+6+5+5) >= 287)
			{
				$pdf->addPage();
				$currentY = $pdf->GetY();
				
			}
			
			$pdf->SetX(141.00125);
			$pdf->Cell(32,6.5,'Subtotal','LTR',0,'L');
			$pdf->Cell(30,6.5,'',1,0,'C');
			$pdf->Ln();
			$pdf->SetX(141.00125);
			$pdf->Cell(32,6,'Shipping','LR',0,'L');
			$pdf->Cell(30,6,'',1,0,'C');
			$pdf->Ln();
			$pdf->SetX(141.00125);
			$pdf->Cell(32,5,'Tax','LR',0,'L');
			$pdf->Cell(30,5,'',1,0,'C');
			$pdf->Ln();
			$pdf->SetX(141.00125);
			$pdf->Cell(32,5,'Others','LBR',0,'L');
			$pdf->Cell(30,5,'',1,0,'C');
			
			$pdf->SetFont('Arial','IB',10);
			$pdf->Ln();
			$pdf->SetX(141.00125);
			$pdf->Cell(32,7.5,'TOTAL',1,0,'C');
			$pdf->Cell(30,7.5,'',1,0,'C');
			
			$pdf->SetFont('Arial','',10);
			$pdf->SetXY(171.00125,$currentY+1);
			$pdf->Cell(29,5,$subTotalInFormat,0,0,'R');
			if($poDiscount > 0)
			{
				$pdf->SetXY(151.00125,($currentY+18));
				$pdf->Cell(20,5,'(Discount)',0,0,'R');
			}
			else if($poDiscount < 0)
			{
				if($totalAmount != 0)
				{
					$pdf->SetXY(151.00125,($currentY+18));
					$pdf->Cell(20,5,'(Charges)',0,0,'R');
				}
			}
			$pdf->SetXY(171.00125,($currentY+18));
			$pdf->Cell(29,5,$poDiscountInFormat,0,0,'R');
			$pdf->SetXY(171.00125,($currentY+23));
			$pdf->Cell(29,7.5,$totalAmountInFormat,0,0,'R');
		}
	}
	// ********************************************************** END ATTACHMENT ********************************************************** //
	
	if($saveFileFlag==1 AND $previewFlag==1)
	{
		$attachmentFile = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/Email Attachment/".$poNumber."-1.pdf";
		$pdf->Output($attachmentFile, 'F');
		
		header('location:gerald_invoiceDetailsConverter.php?saveFile=1&poNumber='.$poNumber);
	}
	else
	{
		if($downloadFlag == 1)
		{
			$attachmentFile = $poNumber."(For Printing).pdf";
			$pdf->Output($attachmentFile, 'D');
		}
		else	$pdf->Output();
	}
?>
