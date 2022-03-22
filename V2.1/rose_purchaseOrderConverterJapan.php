<?php
	include("../../Common Data/PHP Modules/mysqliConnection.php");
	include("../../Common Data/PHP Modules/anthony_wholeNumber.php");
	include("../../Common Data/PHP Modules/gerald_functions.php");
	require('../../Common Data/classes/fpdf.php');
	require_once('../../Common Data/fpdi/fpdi.php');
	
	$SJIS_widths = array(' '=>278,'!'=>299,'"'=>353,'#'=>614,'$'=>614,'%'=>721,'&'=>735,'\''=>216,
		'('=>323,')'=>323,'*'=>449,'+'=>529,','=>219,'-'=>306,'.'=>219,'/'=>453,'0'=>614,'1'=>614,
		'2'=>614,'3'=>614,'4'=>614,'5'=>614,'6'=>614,'7'=>614,'8'=>614,'9'=>614,':'=>219,';'=>219,
		'<'=>529,'='=>529,'>'=>529,'?'=>486,'@'=>744,'A'=>646,'B'=>604,'C'=>617,'D'=>681,'E'=>567,
		'F'=>537,'G'=>647,'H'=>738,'I'=>320,'J'=>433,'K'=>637,'L'=>566,'M'=>904,'N'=>710,'O'=>716,
		'P'=>605,'Q'=>716,'R'=>623,'S'=>517,'T'=>601,'U'=>690,'V'=>668,'W'=>990,'X'=>681,'Y'=>634,
		'Z'=>578,'['=>316,'\\'=>614,']'=>316,'^'=>529,'_'=>500,'`'=>387,'a'=>509,'b'=>566,'c'=>478,
		'd'=>565,'e'=>503,'f'=>337,'g'=>549,'h'=>580,'i'=>275,'j'=>266,'k'=>544,'l'=>276,'m'=>854,
		'n'=>579,'o'=>550,'p'=>578,'q'=>566,'r'=>410,'s'=>444,'t'=>340,'u'=>575,'v'=>512,'w'=>760,
		'x'=>503,'y'=>529,'z'=>453,'{'=>326,'|'=>380,'}'=>326,'~'=>387);

	// ------------------------------------------- Some Boring Stuff --------------------------------------------------------------------------------------
	class MYPDF extends FPDI
	{
		function AutoFitCell($w='',$h='',$font='',$style='',$fontSize='',$string='',$border='',$ln='',$align='',$fill='',$link='') 
		{
			$decrement = 0.1;
			$limit = round($w)-(round($w)/3);
			
			$string = preg_replace('/\s+/', ' ', $string);
			
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
		
		function SetDash($black=null, $white=null)
		{
			if($black!==null)
				$s=sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
			else
				$s='[] 0 d';
			$this->_out($s);
		}		
		
		function getlastpageNumber()
		{
			return $this->PageNo();
		}

		var $last; 
		function setlastpageNumber($last) 
		{
			$this->last = $last;
		}		 
		
		function getlast()	
		{
			return $this->last();
		}
		
	// --------------------------------------------------------------------------------

		var $NewPageGroup;   // variable indicating whether a new group was requested
		var $PageGroups;     // variable containing the number of pages of the groups
		var $CurrPageGroup;  // variable containing the alias of the current page group

		// create a new page group; call this before calling AddPage()
		function StartPageGroup()
		{
				$this->NewPageGroup = true;
		}

		// current page in the group
		function GroupPageNo()
		{
				return $this->PageGroups[$this->CurrPageGroup];
		}

		// alias of the current page group -- will be replaced by the total number of pages in this group
		function PageGroupAlias()
		{
				return $this->CurrPageGroup;
		}

		function _beginpage($orientation, $format)
		{
				parent::_beginpage($orientation, $format);
				if($this->NewPageGroup)
				{
						// start a new group
						$n = sizeof($this->PageGroups)+1;
						$alias = "{nb$n}";
						$this->PageGroups[$alias] = 1;
						$this->CurrPageGroup = $alias;
						$this->NewPageGroup = false;
				}
				elseif($this->CurrPageGroup)
						$this->PageGroups[$this->CurrPageGroup]++;
		}

		function _putpages()
		{
				$nb = $this->page;
				if (!empty($this->PageGroups))
				{
						// do page number replacement
						foreach ($this->PageGroups as $k => $v)
						{
								for ($n = 1; $n <= $nb; $n++)
								{
										$this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
								}
						}
				}
				parent::_putpages();
		}

		// Page footer
		//var $valz=0;
		function Footer()
		{
			if($this->footer!=1)
			{
				$this->SetY(-7.5);
				$this->SetFont('SJIS','',9);
				//~ $this->Cell(0,5,'	備考 (仮)..仮単価 / (望)..希望展開 / (決)..決定単価 ','T',0,'L');
				$this->Cell(0,5,'','T',0,'L');
			}
		}
		function Header(){
			//297,210
			include("../../Common Data/PHP Modules/mysqliConnection.php");
			
			if($this->header!=1)
			{
				$db->set_charset("sjis");
				
				$poNo = $this->poNo;
				$poNumber = $this->poNumber;
				$poCurrency = $this->poCurrency;
				$supplierId = $this->supplierId;
				$supplierType = $this->supplierType;
				$poIssueDate = $this->poIssueDate;
				$supplierName = $this->supplierName;
				$lotNumberArray = $this->lotNumberArray;
				
				if($poCurrency == 1)		$sign = '$';
				else if($poCurrency == 2)	$sign = 'Php';
				else if($poCurrency == 3)	$sign = '￥';
				
				$totalAmount = 0;
				if($poNo!='')
				{
					$sql = "SELECT DISTINCT lotNumber, itemQuantity, itemUnit FROM purchasing_pocontents WHERE poNumber LIKE '".$poNo."'";
					$queryPoContentLot = $db->query($sql);
					if($queryPoContentLot AND $queryPoContentLot->num_rows > 0)
					{
						while($resultPoContentLot = $queryPoContentLot->fetch_assoc())
						{
							$lotNumber = $resultPoContentLot['lotNumber'];
							$poContentQuantity = $resultPoContentLot['itemQuantity'];
							$itemUnit = $resultPoContentLot['itemUnit'];
							
							$poContentPrice = 0;
							$sql = "SELECT productId, itemPrice FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND lotNumber LIKE '".$lotNumber."'";
							$queryPoContent = $db->query($sql);
							if($queryPoContent->num_rows > 0)
							{
								while($resultPoContent = $queryPoContent->fetch_array())
								{
									$poContentPrice = $resultPoContent['itemPrice'] * $poContentQuantity;
									$totalAmount += $poContentPrice;
								}
							}
						}
					}
				}
				else
				{
					if(count($lotNumberArray) > 0)
					{
						$sql = "SELECT DISTINCT lotNumber, itemQuantity, itemUnit FROM purchasing_forpurchaseorder WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency."";
						$queryPoContentLot = $db->query($sql);
						if($queryPoContentLot AND $queryPoContentLot->num_rows > 0)
						{
							while($resultPoContentLot = $queryPoContentLot->fetch_assoc())
							{
								$lotNumber = $resultPoContentLot['lotNumber'];
								$poContentQuantity = $resultPoContentLot['itemQuantity'];
								$itemUnit = $resultPoContentLot['itemUnit'];
								
								$poContentPrice = 0;
								$sql = "SELECT itemPrice FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."' AND supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency."";
								$queryPoContent = $db->query($sql);
								if($queryPoContent->num_rows > 0)
								{
									while($resultPoContent = $queryPoContent->fetch_array())
									{
										$poContentPrice = $resultPoContent['itemPrice'] * $poContentQuantity;
										$totalAmount += $poContentPrice;
									}
								}
							}
						}					
					}
				}				
				
				//143.5
				
				// ---------------------------------------- HEADER LEFT ---------------------------------------- // 
				$this->SetFont('SJIS','',9);
				$this->Cell(28.335,5,'伝票コード ',0);
				$this->SetFont('SJIS','',13);
				$this->Cell(38.335,6,$poNumber,0);
				$this->SetFont('SJIS','',19);
				$this->Cell(7,10,'',0,0,'C');
				$this->Cell(17.5557,10,'注','LTB',0,'C');
				$this->Cell(17.5557,10,'文','TB',0,'C');
				$this->Cell(17.5557,10,'書','TBR',0,'C');
				$this->Cell(7,10,'',0,0,'C');
				$this->SetFont('SJIS','',9);
				$this->Cell(28.335,5,'注文日',0,0,'R');
				$this->SetFont('SJIS','',13);
				$this->Cell(38.335,6,date('y/m/d',strtotime($poIssueDate)),0,0,'R');
				
				$this->Ln();
				$this->SetFont('SJIS','',9);
				//~ $this->Cell(0,5,$this->PageNo().'/{nb} ページ',0,0,'R');
				$this->Cell(0,5,$this->GroupPageNo().'/'.$this->PageGroupAlias().' ページ',0,0,'R');
				
				if($this->PageNo() == 1)
				{
					$this->Ln();
					$this->SetFont('SJIS','',13);
					$this->Cell(0,8,$supplierName,0,0,'L');
					
					$this->Ln();
					$this->SetFont('SJIS','',12);
					$this->Cell(0,5,'御中',0,0,'C');
					
					$this->SetY($this->GetY());
					$this->SetFont('SJIS','',10);
					$this->Cell(106.86,5,'',0,0,'R');
					$this->Cell(93.14,5,'ARK	アークテック株式会社',0,0,'C');	$this->Ln();
					$this->Cell(106.86,5,'',0,0,'R');$this->SetFont('SJIS','',5.5);
					$this->Cell(93.14,5,'〒321-0202　下都賀郡壬生町おもちゃのまち4-11-16',0,0,'C');	$this->Ln();
					$this->Cell(106.86,5,'',0,0,'R');$this->SetFont('SJIS','',8);
					$this->Cell(93.14,5,'TEL:0282-86-0276',0,0,'C');	$this->Ln();
					$this->Cell(106.86,5,'',0,0,'R');
					$this->Cell(93.14,5,'FAX:0282-86-0365',0,0,'C');	$this->Ln();
					
					$note = "下記の通り注文いたします。\n単価未定は速やかに見積書を提出願います\n納入場所は原則として当社本社とする\n支払方法等は現行「支払方法等について」にやります。\n本注文書の単価は税抜き価格です。";
					
					$this->SetFont('SJIS','',8);
					$this->SetXY(5,($this->GetY()-15));
					$this->MultiCell(74.166666667,4.5,$note,0,'L');
					
					$this->Ln();
					$this->SetFont('SJIS','',11);
					$this->Cell(2.5,5,'',0,0,'L');
					$this->Cell(25,5,'注文合計金額','B',0,'L');
					$this->Cell(50,5,$sign." ".number_format($totalAmount, 0, '.', ','),'B',0,'C');
					$this->Cell(10,5,'(税抜)',0,0,'C');
					$this->Ln(10);
					// -------------------------------------- END HEADER LEFT -------------------------------------- // 
					
					$this->SetY(63.5);
				}
				else
				{
					$this->SetY(23.5);
				}
				
				//~ $this->SetFont('SJIS','',9);
				//~ $this->Cell(10,5,'NO','B',0,'L');
				//~ $this->Cell(69.375,5,'図番','B',0,'L');
				//~ $this->Cell(69.375,5,'品名','B',0,'L');
				//~ $this->Cell(46.25,5,'','B',0,'R');
				//~ $this->Cell(5,5,'','B',0,'R');
				//~ $this->Ln();		
				
				$this->SetFont('SJIS','',9);
				$this->Cell(10,5,'NO','B',0,'L');
				//~ $this->Cell(23.75,5,'ロット番号','B',0,'L');//Lot Number
				$this->Cell(23.75,5,'Lot No.','B',0,'L');//Lot Number
				$this->Cell(33.75,5,'図番','B',0,'L');//Name
				$this->Cell(43.75,5,'品名','B',0,'L');//Description
				$this->Cell(11.75,5,'注文数','B',0,'R');//Quantity
				$this->Cell(2,5,'','B',0,'R');
				$this->Cell(16.75,5,'支給予定日','B',0,'C');
				$this->Cell(16.75,5,'納期','B',0,'C');
				$this->Cell(20.75,5,'単価','B',0,'R');
				$this->Cell(20.75,5,'','B',0,'R');
				$this->Ln();		
			}
			
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
			foreach($font['cw'] as $w)
				$W.=$w.' ';
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
	// ------------------------------------------- End of Some Boring Stuff --------------------------------------------------------------------------------------

	$previewFlag = (isset($_POST['preview']) AND $_POST['preview']==0) ? 0 : 1;
	$downloadFlag = (isset($_POST['download']) AND $_POST['download']==1) ? 1 : 0;
	$saveFileFlag = (isset($_GET['saveFile']) AND $_GET['saveFile']==1) ? 1 : 0;
	$tempPoNumber = $poNo = (isset($_GET['poNumber'])) ? $_GET['poNumber'] : '';
	
	$db->set_charset("sjis");
	
	if(isset($_GET['key']))
	{
		$key = $_GET['key'];
		$dataExplode=explode("`",$key);
		$dataExplode2=explode("-",$dataExplode[1]);
		
		$supplierId=$dataExplode[0];			
		$supplierType=$dataExplode2[0];
		$currency=$dataExplode2[1];
		$poCurrency=$dataExplode2[1];		
		
		$poNo = '';
		$poNumber = ($tempPoNumber=='') ? 'PREVIEW' : $tempPoNumber;
		$supplierId = $dataExplode[0];
		$supplierType = $dataExplode2[0];
		$poCurrency = $dataExplode2[1];
		$delivery = '';//Temporary
		$shipmentType = '';
		$poRemarks = '';
		$poDiscount = 0;
		$checkedBy = '';
		$approvedBy = '';
		
		//~ $chargeDescriptionArray = (isset($_POST['chargeDescription'])) ? array_values(array_filter($_POST['chargeDescription'])) : array();
		//~ $chargeQuantityArray = (isset($_POST['chargeQuantity'])) ? array_values(array_filter($_POST['chargeQuantity'])) : array();
		//~ $chargeUnitArray = (isset($_POST['chargeUnit'])) ? array_values(array_filter($_POST['chargeUnit'])) : array();
		//~ $chargeUnitPriceArray = (isset($_POST['chargeUnitPrice'])) ? array_values(array_filter($_POST['chargeUnitPrice'])) : array();
		
		$prepared = $_SESSION['idNumber'];	
		
		$lotNumberArray = array();
		$sql = "SELECT lotNumber, dateNeeded FROM purchasing_forpurchaseorder WHERE supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency." GROUP BY lotNumber";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
			{
				$lotNumber = $resultWorkSchedule['lotNumber'];
				$delivery = $resultWorkSchedule['dateNeeded'];
				$lotNumberArray[] = $lotNumber;
			}
		}
		$poIssueDate = date('Y-m-d');
	}
	else if($poNo!='')
	{
		$poNumber = $poNo;
		$sql = "SELECT supplierId, supplierType, poTerms, poShipmentType, poIncharge, poIssueDate, poTargetReceiveDate, poRemarks, poCurrency, poDiscount, checkedBy, approvedBy FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
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
		
	}

	if($supplierType==1)
	{
		$sql = "SELECT supplierName, supplierAddress, supplierTel, supplierFax, contactPerson, terms, tinNumber, shipment FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
	}
	else if($supplierType==2)
	{
		$sql = "SELECT subconName, subconAddress, subconPhone, subconFax, subconContactPerson, terms, tinNumber, shipment FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
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
			$shipment = $resultSupplier[7];
			if($shipment==0)	$shipment = 1;
		}
	}
	
	if($shipmentType=='')	$shipmentType = $shipment;
	
	//~ $deliveryDate = date('M d, Y', strtotime($delivery));
	$deliveryDate = date('y/m/d',strtotime($delivery));
	
	// -------------------------------------------------------------------- Generate PDF ----------------------------------------------------------------------------
	$pdf = new MYPDF('P','mm','A4');//297,210
	$pdf->AddSJISFont();
	$pdf->AliasNbPages();
	$pdf->SetMargins(5,5,5);
	$pdf->SetAutoPageBreak(true,5);
	$pdf->poNo = $poNo;
	$pdf->poNumber = $poNumber;
	$pdf->poCurrency = $poCurrency;
	$pdf->supplierId = $supplierId;
	$pdf->supplierType = $supplierType;
	$pdf->poIssueDate = $poIssueDate;
	$pdf->supplierName = $supplierName;
	$pdf->lotNumberArray = $lotNumberArray;
	
	$pdf->StartPageGroup();
	$pdf->AddPage();
	
	$count = 0;
	if(count($lotNumberArray) > 0)
	{
		foreach($lotNumberArray as $lotNumber)
		{
			$sql = "SELECT poId, partId, workingQuantity, identifier, status, patternId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_array();
				$poId = $resultLotList['poId'];
				$partId = $resultLotList['partId'];
				$poContentQuantity = $resultLotList['workingQuantity'];
				$identifier = $resultLotList['identifier'];
				$supplyType = $resultLotList['status'];
				$patternId = $resultLotList['patternId'];
				
				$poContentQuantity = wholeNumber($poContentQuantity);
				
				if($poCurrency == 1)		$sign = '$';
				else if($poCurrency == 2)	$sign = 'Php';
				else if($poCurrency == 3)	$sign = '￥';
				
				$totalSurfaceClear = 0;
				
				$itemName = $itemDescription = '';
				if($identifier==1)
				{
					$partNumber = $partName = '';
					$sql = "SELECT partNumber, partName FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
					$queryParts = $db->query($sql);
					if($queryParts->num_rows > 0)
					{
						$resultParts = $queryParts->fetch_array();
						$partNumber = $resultParts['partNumber'];
						$partName = $resultParts['partName'];
					}
					
					$totalSurfaceClear = 0;
					$sql = "SELECT surfaceArea FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = 270 LIMIT 1";//Anodize
					$querySubconList = $db->query($sql);
					if($querySubconList->num_rows > 0)
					{
						$resultSubconList = $querySubconList->fetch_array();
						$totalSurfaceClear = $resultSubconList['surfaceArea'];
					}
					
					$itemName = $partNumber;
					$itemDescription = $partName;
				}
				else
				{
					if($supplyType==1)
					{
						$materialId = '';
						$sql = "SELECT materialId FROM purchasing_materialtreatment WHERE materialTreatmentId = ".$partId." LIMIT 1";
						$queryMaterialTreatment = $db->query($sql);
						if($queryMaterialTreatment->num_rows > 0)
						{
							$resultMaterialTreatment = $queryMaterialTreatment->fetch_array();
							$materialId = $resultMaterialTreatment['materialId'];
						}
						
						$materialSpecId = $length = $width = '';
						$sql = "SELECT materialSpecId, length, width FROM purchasing_material WHERE materialId = ".$materialId." LIMIT 1";
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
						if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
						{
							$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
							$materialTypeId = $resultMaterialSpecs['materialTypeId'];
							$metalThickness = $resultMaterialSpecs['metalThickness'];
						}
						
						$materialType = '';
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType AND $queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_assoc();
							$materialType = $resultMaterialType['materialType'];
						}
						
						$itemName = $materialType;
						$itemDescription = $metalThickness." X ".$length." X ".$width;
					}
					else if($supplyType==3)
					{
						$itemName = $itemDescription = '';
						$sql = "SELECT itemName, itemDescription FROM purchasing_items WHERE itemId = ".$partId." LIMIT 1";
						$queryItems = $db->query($sql);
						if($queryItems->num_rows > 0)
						{
							$resultItems = $queryItems->fetch_array();
							$itemName = $resultItems['itemName'];
							$itemDescription = $resultItems['itemDescription'];
						}
					}
					else if($supplyType==4)
					{
						$accessoryNumber = $accessoryName = '';
						$sql = "SELECT accessoryNumber, accessoryName FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
						$queryAccessories = $db->query($sql);
						if($queryAccessories->num_rows > 0)
						{
							$resultAccessories = $queryAccessories->fetch_array();
							$accessoryNumber = $resultAccessories['accessoryNumber'];
							$accessoryName = $resultAccessories['accessoryName'];
						}
						
						$itemName = $accessoryNumber;
						$itemDescription = $accessoryName;
					}
				}
				
				$itemUnit = '';
				$poContentPrice = 0;
				$subconListIdArray = array();
				$itemRemarks = $sendingDate = $receivingDate = '';
				if($poNo!='')
				{
					$sql = "SELECT productId, itemPrice, itemUnit, itemQuantity, itemRemarks, sendingDate, receivingDate FROM purchasing_pocontents WHERE poNumber LIKE '".$poNo."' AND lotNumber LIKE '".$lotNumber."'";
					$queryPoContent = $db->query($sql);
					if($queryPoContent->num_rows > 0)
					{
						while($resultPoContent = $queryPoContent->fetch_array())
						{
							$listId = $resultPoContent['productId'];
							$poContentPrice += $resultPoContent['itemPrice'];
							$poContentQuantity = $resultPoContent['itemQuantity'];
							$itemRemarks = $resultPoContent['itemRemarks'];
							$sendingDate = $resultPoContent['sendingDate'];
							$receivingDate = $resultPoContent['receivingDate'];
							$deliveryDate = $resultPoContent['receivingDate'];
							
							$poContentQuantity = wholeNumber($poContentQuantity);
							
							$treatmentId = '';
							$sql = "SELECT supplyId, supplyType FROM purchasing_supplies WHERE listId = ".$listId." LIMIT 1";
							$queryTreatmentId = $db->query($sql);
							if($queryTreatmentId->num_rows > 0)
							{
								$resultTreatmentId = $queryTreatmentId->fetch_array();
								$treatmentId = $resultTreatmentId['supplyId'];
								$supplyType = $resultTreatmentId['supplyType'];
								
								if($supplyType==2 OR $supplyType==5)
								{
									$subconListId = $treatmentId;
									if($supplyType==2)
									{
										$sql = "SELECT a FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$treatmentId." LIMIT 1";
										$queryProcessCode = $db->query($sql);
										if($queryProcessCode->num_rows > 0)
										{
											$resultProcessCode = $queryProcessCode->fetch_array();
											$subconListId = $resultProcessCode['a'];
										}
									}
									$subconListIdArray[] = $subconListId;
								}
							}
						}
					}
					
					//~ $delivery = $sendingDate = '';
					//~ $sql = "SELECT sendingDate, receivingDate FROM purchasing_pocontentdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					//~ $queryPoContentDetails = $db->query($sql);
					//~ if($queryPoContentDetails AND $queryPoContentDetails->num_rows > 0)
					//~ {
						//~ $resultPoContentDetails = $queryPoContentDetails->fetch_assoc();
						//~ $delivery = $resultPoContentDetails['receivingDate'];
						//~ $sendingDate = $resultPoContentDetails['sendingDate'];
					//~ }					
				}
				else
				{
					$sql = "SELECT productId, itemName, itemDescription, itemUnit, itemPrice, itemRemarks, dateNeeded FROM purchasing_forpurchaseorder WHERE lotNumber LIKE '".$lotNumber."' AND supplierId = ".$supplierId." AND supplierType = ".$supplierType." AND poCurrency = ".$poCurrency."";
					$querySupplierProducts = $db->query($sql);
					if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
					{
						while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
						{
							$productId = $resultSupplierProducts['productId'];
							$itemName = $resultSupplierProducts['itemName'];
							//~ $itemDescription = $resultSupplierProducts['itemDescription'];
							$itemUnit = $resultSupplierProducts['itemUnit'];
							$poContentPrice += $resultSupplierProducts['itemPrice'];
							$itemRemarks = $resultSupplierProducts['itemRemarks'];
							$deliveryDate = $resultSupplierProducts['dateNeeded'];
							
							$treatmentId = '';
							$sql = "SELECT supplyId, supplyType FROM purchasing_supplies WHERE listId = ".$productId." LIMIT 1";
							$queryTreatmentId = $db->query($sql);
							if($queryTreatmentId->num_rows > 0)	
							{
								$resultTreatmentId = $queryTreatmentId->fetch_array();
								$treatmentId = $resultTreatmentId['supplyId'];
								$supplyType = $resultTreatmentId['supplyType'];
								
								if($supplyType==2 OR $supplyType==5)
								{
									$subconListId = $treatmentId;
									if($supplyType==2)
									{
										$sql = "SELECT a FROM cadcam_subconlist WHERE partId = ".$partId." AND processCode = ".$treatmentId." LIMIT 1";
										$queryProcessCode = $db->query($sql);
										if($queryProcessCode->num_rows > 0)
										{
											$resultProcessCode = $queryProcessCode->fetch_array();
											$subconListId = $resultProcessCode['a'];
										}
									}
									$subconListIdArray[] = $subconListId;
								}
							}							
						}
					}
					
					if($identifier==1)
					{
						$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(91,92) ORDER BY processOrder LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
							$targetFinish = $resultWorkSchedule['targetFinish'];
							
							$targetFinish = addDays(1,$targetFinish);
							//~ if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(5);
							
							$sendingDate = $targetFinish;
						}
						
						if($deliveryDate=='0000-00-00')
						{
							$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processSection = 10 ORDER BY processOrder LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
								$targetFinish = $resultWorkSchedule['targetFinish'];
								
								//~ if(strtotime($targetFinish) < strtotime(date('Y-m-d')))	$targetFinish = addDays(2);
								
								$deliveryDate = $targetFinish;
							}
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
				
				if($identifier==1)
				{
					$unitName = "個";
					$deliveryDate = ($deliveryDate!='0000-00-00') ? date('y/m/d',strtotime($deliveryDate)) : '';
					$sendingDate = ($sendingDate!='0000-00-00') ? date('y/m/d',strtotime($sendingDate)) : '';
				}
				
				$amount = $poContentPrice * $poContentQuantity;
				
				$totalAmount += $amount;
				
				$count++;
				
				/*
				$pdf->SetFont('SJIS','',9);
				$pdf->Cell(10,5,$count,'T',0,'L');
				$pdf->Cell(69.375,5,$itemName,'T',0,'L');
				//~ $pdf->Cell(83,5,$itemDescription,'T',0,'L');
				$pdf->AutoFitCell(115.625,5,'SJIS','',9,$itemDescription,'T',0,'L');$pdf->SetFont('SJIS','',9);
				$pdf->Cell(5,5,'','T',0,'R');
				$pdf->Ln();
				
				$pdf->Cell(10,5,'',0,0,'L');
				$pdf->Cell(18.5,5,'注文数',0,0,'R');
				$pdf->Cell(18.5,5,$poContentQuantity.$unitName,0,0,'R');
				$pdf->Cell(18.5,5,'納期',0,0,'R');
				$pdf->Cell(18.5,5,$deliveryDate,0,0,'L');
				if($identifier==1)
				{				
					$pdf->Cell(18.5,5,'支給予定日',0,0,'R');
					$pdf->Cell(18.5,5,$sendingDate,0,0,'L');
				}
				else
				{
					$pdf->Cell(18.5,5,'',0,0,'R');
					$pdf->Cell(18.5,5,'',0,0,'L');
				}
				$pdf->Cell(18.5,5,'単価',0,0,'C');
				$pdf->Cell(18.5,5,$sign." ".number_format($poContentPrice, 0, '.', ','),0,0,'R');
				//~ $pdf->Cell(18.5,5,'(決)',0,0,'L');
				$pdf->Cell(18.5,5,'',0,0,'L');
				$pdf->Cell(18.5,5,$sign." ".number_format(($poContentPrice * $poContentQuantity), 0, '.', ','),0,0,'R');
				$pdf->Cell(5,5,'',0,0,'R');
				$pdf->Ln();
				
				$pdf->Cell(10,5,'備考',0,0,'L');
				$pdf->Cell(69.375,5,$lotNumber,0,0,'L');
				$pdf->Ln();*/
				
				$pdf->SetFont('SJIS','',9);
				$pdf->Cell(10,5,$count,'T',0,'L');
				$pdf->Cell(23.75,5,$lotNumber,'T',0,'L');//Lot Number
				$pdf->Cell(33.75,5,$itemName,'T',0,'L');//Name
				$pdf->AutoFitCell(43.75,5,'SJIS','',9,$itemDescription,'T',0,'L');$pdf->SetFont('SJIS','',9);
				$pdf->Cell(11.75,5,$poContentQuantity.$unitName,'T',0,'R');//Quantity
				$pdf->Cell(2,5,'','T',0,'R');
				if($identifier!=1)
				{
					if($sendingDate!="0000-00-00")
					{
						$pdf->Cell(16.75,5,$sendingDate,'T',0,'C');
					}
					else
					{
						$pdf->Cell(16.75,5,"",'T',0,'C');
					}
					$pdf->Cell(16.75,5,$deliveryDate,'T',0,'C');
				}
				else
				{
					$pdf->Cell(16.75,5,"",'T',0,'C');
					$pdf->Cell(16.75,5,"",'T',0,'C');
				}
				$pdf->Cell(20.75,5,$sign." ".number_format($poContentPrice, 0, '.', ','),'T',0,'R');
				$pdf->Cell(20.75,5,$sign." ".number_format(($poContentPrice * $poContentQuantity), 0, '.', ','),'T',0,'R');		
				$pdf->Ln();		
				
				if(count($subconListIdArray) > 0)
				{
					$sql = "SELECT a, processCode FROM `cadcam_subconlist` WHERE a IN(".implode(",",$subconListIdArray).") ORDER BY subconOrder";
					$querySubconList = $db->query($sql);
					if($querySubconList AND $querySubconList->num_rows > 0)
					{
						while($resultSubconList = $querySubconList->fetch_assoc())
						{
							$subconListId = $resultSubconList['a'];
							$processCode = $resultSubconList['processCode'];
							
							$firstProcessCode = '';
							$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." ORDER BY processOrder LIMIT 1";
							$queryFirstProcess = $db->query($sql);
							if($queryFirstProcess AND $queryFirstProcess->num_rows > 0)
							{
								$resultFirstProcess = $queryFirstProcess->fetch_assoc();
								$firstProcessCode = $resultFirstProcess['processCode'];
							}
							
							if($firstProcessCode==$processCode)	$sendingDate = 'N/A';
							
							$treatmentName = '';
							$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId = ".$processCode." LIMIT 1";
							$queryTreatment = $db->query($sql);
							if($queryTreatment AND $queryTreatment->num_rows > 0)
							{
								$resultTreatment = $queryTreatment->fetch_assoc();
								$treatmentName = $resultTreatment['treatmentName'];
							}
							
							$dataOne = $dataTwo = $dataThree = '';
							$sql = "SELECT dataOne, dataTwo, dataThree FROM engineering_subconremarks WHERE subconListId = ".$subconListId;
							$querySubconRemarks = $db->query($sql);
							if($querySubconRemarks AND $querySubconRemarks->num_rows > 0)
							{
								$counter = 0;
								while($resultSubconRemarks = $querySubconRemarks->fetch_assoc())
								{
									if($counter > 0)
									{
										$treatmentName = "";
									}
									$dataOne = $resultSubconRemarks['dataOne'];
									$dataTwo = $resultSubconRemarks['dataTwo'];
									$dataThree = $resultSubconRemarks['dataThree'];								
									
									if($dataOne=="別途指示")
									{
										$sql = "SELECT remarks FROM sales_polist WHERE poId = ".$poId;
										$poListQuery = $db->query($sql);
										$poListQueryResult = $poListQuery->fetch_assoc();
										$remarks = explode("`",$poListQueryResult['remarks']);
										
										$dataOne = $remarks[1];
									}
									
									$pdf->Cell(10,5,'',0,0,'L');
									$pdf->Cell(37.5,5,$treatmentName,0,0,'L');
									$pdf->Cell(35.833,5,$dataOne,0,0,'L');
									$pdf->Cell(20.833,5,$dataTwo,0,0,'R');
									$pdf->Cell(20.833,5,$dataThree,0,0,'L');
									$pdf->Cell(16.75,5,$sendingDate,0,0,'C');
									$pdf->Cell(16.75,5,$deliveryDate,0,0,'C');
									$pdf->Cell(5,5,'',0,0,'R');
									$pdf->Ln();
                                	$pdf->Cell(10,5,'備考',0,0,'L');
                                	$pdf->Cell(0,5,$itemRemarks,0,0,'L');
                                	$pdf->Ln();                                
									$counter++;						
								}
							}
							else
							{
								$pdf->Cell(10,5,'',0,0,'L');
								$pdf->Cell(37.5,5,$treatmentName,0,0,'L');
								$pdf->Cell(35.833,5,"",0,0,'L');
								$pdf->Cell(20.833,5,"",0,0,'R');
								$pdf->Cell(20.833,5,"",0,0,'L');
								$pdf->Cell(16.75,5,$sendingDate,0,0,'C');
								$pdf->Cell(16.75,5,$deliveryDate,0,0,'C');
                            	$pdf->Ln();
								$pdf->Cell(10,5,'備考',0,0,'L');
								$pdf->Cell(0,5,$itemRemarks,0,0,'L');
								$pdf->Ln();	                            
							}                        
						}
					}
				}	
				else
                {
					$pdf->Cell(10,5,'備考',0,0,'L');
					$pdf->Cell(0,5,$itemRemarks,0,0,'L');
					$pdf->Ln();
                }
			}
		}
		$pdf->SetFont('SJIS','',10);
		$pdf->Ln();
		$pdf->Cell(0,5,'=============================================================',0,0,'C');
		$pdf->Ln();
		$pdf->MultiCell(0,5,$poRemarks,0,'C');
		
	}
	$pdf->PageNo();
	// ************************************************** ITEM TAG ************************************************** //
	$pdf->AddFont('IDAutomationHC39M','');
	
	$paperWidth = 210;
	$paperLength = 297;
	$left = 5;
	$top = 5;
	$cols = 2;
	$rows = 3;
	
	$boxWidth = ($paperWidth / $cols);
	$boxLength = ($paperLength / $rows);	
	
	$arkLogo = $_SERVER['DOCUMENT_ROOT'].'/Include Files/images/arkJapanLogo.png';
	
	$y = $top;
	$w = $boxWidth - ($top * 2);
	$h = 7;		

	//~ $supplierAlias = $supplierName;
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
	
	$index = 0;
	$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
	$queryPoContentsLot = $db->query($sql);
	if($queryPoContentsLot->num_rows > 0)
	{
		while($resultPoContentsLot = $queryPoContentsLot->fetch_array())
		{
			$lotNumber = $resultPoContentsLot['lotNumber'];
			
			$partNumberArray = $treatmentProcessArray = array();
			$itemName = $itemDescription = '';
			$itemQuantity = 0;
			$sql = "SELECT `poContentId`, `itemName`, `itemDescription`, `itemQuantity` FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."' AND lotNumber LIKE '".$lotNumber."'";
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
			
			if(($index%6) == 0)
			{
				$pdf->header = 1;
				$pdf->StartPageGroup();
				$pdf->AddPage();
				$pdf->footer = 1;
				//~ $pdf->SetLineStyle(array('dash' => '2'));
				// - - - - - - - - - - - - - - - - - - - - Creating Dash Lines - - - - - - - - - - - - - - - - - - - - //
				//$pdf->SetDash(0.5,1); //5mm on, 5mm off
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
				//$pdf->SetDash(0,0); //5mm on, 5mm off
				// - - - - - - - - - - - - - - - - - - - End Creating Dash Lines - - - - - - - - - - - - - - - - - - - //
				//~ $pdf->SetLineStyle(array('dash' => 0));
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
			$pdf->SetFont('SJIS','B',18);
			//~ $pdf->AutoFitCell(25,5,'SJIS','B',18,$poNumber."-".($index+1),0,0,'C');
			$pdf->AutoFitCell(25,5,'SJIS','B',18,$tempPoNumber."-".($index+1),0,0,'C');
			
			$pdf->SetXY($x,$y);
			$pdf->SetFont('SJIS','B',21);
			$pdf->Cell($w,8,'ITEM TAG  ',0,0,'C');$pdf->Ln(11);
			$pdf->SetX($x);
			$pdf->SetFont('IDAutomationHC39M','',8);
			$pdf->Cell($w,10,"*".$lotNumber."*",0,0,'C');
			
			$pdf->SetFont('SJIS','',12);
			$pdf->SetXY($x,$y+23);
			
			if($supplierType==1)
			{
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Supplier','LTB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$supplierAlias,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$supplierAlias,1,0,'C');$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Item Name','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$itemName,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$itemName,1,0,'C');$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),($h*3),'Description','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),($h*3),$itemDescription,1,'C',false,0,'','',true,0,false,true,($h*3),'M',true);$pdf->ln();
				$pdf->AutoFitCell(($w/1.334),($h*3),'SJIS','',12,$itemDescription,1,0,'C');$pdf->ln();
				//~ $pdf->MultiCell(($w/1.334),$h,$itemDescription,1,'C');//$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Lot No. ','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$lotNumber,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$lotNumber,1,0,'C');$pdf->ln();
			}
			else if($supplierType==2)
			{
				$asd = 0;
				while($asd < 3)
				{
					if(!isset($treatmentProcessArray[$asd]))	$treatmentProcessArray[$asd] = " ";
					$asd++;
				}
				$treatmentProcessString = implode("\n",$treatmentProcessArray);
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Subcon','LTB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$supplierAlias,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$supplierAlias,1,0,'C');$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Part No. ','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$partNumberString,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$partNumberString,1,0,'C');$pdf->ln();
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),($h*3),'Treatment','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),($h*3),$treatmentProcessString,1,'C',false,0,'','',true,0,false,true,($h*3),'M',true);$pdf->ln();
				//~ $pdf->Cell(($w/1.334),($h*3),$treatmentProcessString,1,0,'C');$pdf->ln();
				//~ $pdf->MultiCell(($w/1.334),$h,$treatmentProcessString,1,'C');//$pdf->ln();
				
				$pdf->AutoFitCell(($w/1.334),$h,'SJIS','',12,$treatmentProcessArray[0],1,0,'C');$pdf->ln();$pdf->SetX($x+($w/4));
				$pdf->AutoFitCell(($w/1.334),$h,'SJIS','',12,$treatmentProcessArray[1],1,0,'C');$pdf->ln();$pdf->SetX($x+($w/4));
				$pdf->AutoFitCell(($w/1.334),$h,'SJIS','',12,$treatmentProcessArray[2],1,0,'C');$pdf->ln();$pdf->SetX($x+($w/4));
				
				$pdf->SetX($x);
				$pdf->Cell(($w/4),$h,'Lot No. ','LB',0,'L');
				//~ $pdf->MultiCell(($w/1.334),$h,$lotNumber,1,'C',false,0,'','',true,0,false,true,$h,'M',true);$pdf->ln();
				$pdf->Cell(($w/1.334),$h,$lotNumber,1,0,'C');$pdf->ln();
			}
			
			if($poNumber=='0008201')
			{
				$itemQuantity = '50 rolls/box';
				//~ $unitName = '';
			}			
			
			$getY= $pdf->GetY();
			$pdf->SetXY($x,$getY+5);
			$pdf->SetFont('SJIS','',10);
			$pdf->Cell(($w/3.3),$h,'PO Quantity',1,0,'C');
			$pdf->Cell(($w/3.3),$h,'Actual Quantity',1,0,'C');
			$pdf->ln();$pdf->SetX($x);
			$pdf->SetFont('SJIS','',20);
			$pdf->Cell(($w/3.3),($h-1)*2,$itemQuantity,1,0,'C');
			$pdf->Cell(($w/3.3),($h-1)*2,'',1,0,'C');
			
			$pdf->SetXY($x+66,$getY+5);
			$pdf->SetFont('SJIS','',10);
			$pdf->Cell(($w/3.3),$h,'Quality Stamp',1,0,'C');
			$pdf->ln();$pdf->SetX($x+66);
			$pdf->SetFont('SJIS','',20);
			$pdf->Cell(($w/3.3),($h-1)*2,'',1,0,'C');
			// - - - - - - - - - - - - - - - - - END ITEM TAG - - - - - - - - - - - - - - - //
			
			$index++;
		}
	}
	// ************************************************** ITEM TAG ************************************************** //	
	
	//~ if($saveFileFlag==1 AND $previewFlag==1)
	if($saveFileFlag==1 AND $tempPoNumber==1)
	{
		$attachmentFile = $_SERVER['DOCUMENT_ROOT']."/V3/4-9 Purchase Order Making Software/Email Attachment/".$tempPoNumber."-1.pdf";
		$pdf->Output($attachmentFile, 'F');
		
		header('location:rose_purchaseOrderConverterJapan.php?key='.$key.'&poNumber='.$tempPoNumber.'&saveFile=0');
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

