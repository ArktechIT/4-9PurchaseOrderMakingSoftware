<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	require('Libraries/PHP/FPDF/fpdf.php');
	require('Libraries/PHP/FPDI/fpdi.php');
	ini_set('display_errors','on');
	
	// ------------------------------------------------ Customized Class ------------------------------------------------------------
	class PDF extends FPDI
	{
		// ----------------------------------------------- Page Group Function ---------------------------------------------------------
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
		
		function getLastPageNumber()
		{
			return $this->PageNo();
		}
		
		function setLastPageNumber($last)
		{
			$this->last = $last;
		}
		
		function getLast()
		{
			return $this->last();
		}
		
		// ------------------------------------ End of Page Group Function ----------------------------------------------
	}
	// --------------------------------------------------------- End of Class ----------------------------------------------------

	$pdf = new PDF('P','mm','A4');
	$pdf->SetLeftMargin(9);
	$pdf->AddFont('IDAutomationHC39M','');
	$pdf->AliasNbPages();
	$pdf->SetFont('Arial');
	$pdf->SetFontSize(10);

	$poNumber = $_GET['poNumber'];
	
	$attachPathFile = $_SERVER['DOCUMENT_ROOT']."/".v."/4-9 Purchase Order Making Software/AutoEmail/";
	
	$supplierType = '';
	$sql = "SELECT supplierType FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		$resultPodetailsNew = $queryPodetailsNew->fetch_assoc();
		$supplierType = $resultPodetailsNew['supplierType'];
	}
	
	if($supplierType==2)
	{
		$lotNumberArray = array();
		$sql = "SELECT DISTINCT lotNumber FROM purchasing_pocontents WHERE poNumber LIKE '".$poNumber."'";
		$queryPoContent = $db->query($sql);
		if($queryPoContent->num_rows > 0)
		{
			while($resultPoContent = $queryPoContent->fetch_array())
			{
				$lotNumberArray[] = "'".$resultPoContent['lotNumber']."'";
			}
		}
		$rose_partIdcounter=0;
		$partIdArray = array();			$partIdArray_02 = array();		$partIdArray_03 = array();		$partIdArray_04 = array();		$partIdArray_05 = array();
		$partIdArray_06 = array();		$partIdArray_07 = array();		$partIdArray_08 = array();		$partIdArray_09 = array();		$partIdArray_10 = array();
		$partIdArray_11 = array();		$partIdArray_12 = array();		$partIdArray_13 = array();		$partIdArray_14 = array();		$partIdArray_15 = array();
		$partIdArray_16 = array();		$partIdArray_17 = array();		$partIdArray_18 = array();		$partIdArray_19 = array();		$partIdArray_20 = array();
		$partIdArray_21 = array();		$partIdArray_22 = array();		$partIdArray_23 = array();		$partIdArray_24 = array();		$partIdArray_25 = array();
		$partIdArray_26 = array();		$partIdArray_27 = array();		$partIdArray_28 = array();		$partIdArray_29 = array();		$partIdArray_30 = array();
		$sql = "SELECT DISTINCT partId FROM ppic_lotlist WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND identifier = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_array())
			{
				if($rose_partIdcounter<20){$partIdArray[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<40){$partIdArray_02[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<60){$partIdArray_03[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<80){$partIdArray_04[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<100){$partIdArray_05[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<120){$partIdArray_06[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<140){$partIdArray_07[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<160){$partIdArray_08[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<180){$partIdArray_09[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<200){$partIdArray_10[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<220){$partIdArray_11[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<240){$partIdArray_12[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<260){$partIdArray_13[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<280){$partIdArray_14[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<300){$partIdArray_15[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<320){$partIdArray_16[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<340){$partIdArray_17[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<360){$partIdArray_18[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<380){$partIdArray_19[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<400){$partIdArray_20[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<420){$partIdArray_21[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<440){$partIdArray_22[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<460){$partIdArray_23[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<480){$partIdArray_24[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<500){$partIdArray_25[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<520){$partIdArray_26[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<540){$partIdArray_27[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<560){$partIdArray_28[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<580){$partIdArray_29[] = $resultLotList['partId'];}
				else if($rose_partIdcounter<600){$partIdArray_30[] = $resultLotList['partId'];}
              	else{}
				// $partIdArray[] = $resultLotList['partId'];
				$rose_partIdcounter++;
			}
		}
		$addZipString="";
		//for($zz=0;$zz<10;$zz++)
		for($zz=0;$zz<30;$zz++)
		{
			if($zz==0){$rose_partId=$partIdArray;}
			if($zz==1){$rose_partId=$partIdArray_02; $addZipString="_1";}
			if($zz==2){$rose_partId=$partIdArray_03; $addZipString="_2";}
			if($zz==3){$rose_partId=$partIdArray_04; $addZipString="_3";}
			if($zz==4){$rose_partId=$partIdArray_05; $addZipString="_4";}
			if($zz==5){$rose_partId=$partIdArray_06; $addZipString="_5";}
			if($zz==6){$rose_partId=$partIdArray_07; $addZipString="_6";}
			if($zz==7){$rose_partId=$partIdArray_08; $addZipString="_7";}
			if($zz==8){$rose_partId=$partIdArray_09; $addZipString="_8";}
			if($zz==9){$rose_partId=$partIdArray_10; $addZipString="_9";}
			
			if($zz==10){$rose_partId=$partIdArray_11; $addZipString="_10";}
			if($zz==11){$rose_partId=$partIdArray_12; $addZipString="_11";}
			if($zz==12){$rose_partId=$partIdArray_13; $addZipString="_12";}
			if($zz==13){$rose_partId=$partIdArray_14; $addZipString="_13";}
			if($zz==14){$rose_partId=$partIdArray_15; $addZipString="_14";}
			if($zz==15){$rose_partId=$partIdArray_16; $addZipString="_15";}
			if($zz==16){$rose_partId=$partIdArray_17; $addZipString="_16";}
			if($zz==17){$rose_partId=$partIdArray_18; $addZipString="_17";}
			if($zz==18){$rose_partId=$partIdArray_19; $addZipString="_18";}
			if($zz==19){$rose_partId=$partIdArray_20; $addZipString="_19";}
			if($zz==20){$rose_partId=$partIdArray_21; $addZipString="_20";}
			if($zz==21){$rose_partId=$partIdArray_22; $addZipString="_21";}
			if($zz==22){$rose_partId=$partIdArray_23; $addZipString="_22";}
			if($zz==23){$rose_partId=$partIdArray_24; $addZipString="_23";}
			if($zz==24){$rose_partId=$partIdArray_25; $addZipString="_24";}
			if($zz==25){$rose_partId=$partIdArray_26; $addZipString="_25";}
			if($zz==26){$rose_partId=$partIdArray_27; $addZipString="_26";}
			if($zz==27){$rose_partId=$partIdArray_28; $addZipString="_27";}
			if($zz==28){$rose_partId=$partIdArray_29; $addZipString="_28";}
			if($zz==29){$rose_partId=$partIdArray_30; $addZipString="_29";}
			
			//if($zz==30){$rose_partId=$partIdArray_31; $addZipString="_30";}
			if(count($rose_partId) > 0)
			{
				
				$zip = new ZipArchive();
				$zip_name = $attachPathFile.$poNumber.$addZipString.".zip"; // Zip name
				
				if(file_exists($zip_name)>0)	unlink($zip_name);
				
				$zip->open($zip_name,  ZipArchive::CREATE);
				
				foreach ($rose_partId as $partId)
				{                  
					$partNumber = $customerId = '';
					$sql = "SELECT partNumber, customerId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
					$queryPartNumber = $db->query($sql);
					if($queryPartNumber->num_rows > 0)
					{
						$resultPartNumber = $queryPartNumber->fetch_array();
						$partNumber = $resultPartNumber['partNumber'];
						$customerId = $resultPartNumber['customerId'];
					}
					
					if($customerId==28)
					{
						$path = $_SERVER['DOCUMENT_ROOT'].'/Document Management System/Arktech Folder/ARK_'.$partId.'.pdf';
					}
					else if($customerId==45)
					{
						$path = $_SERVER['DOCUMENT_ROOT'].'/Document Management System/Arktech Folder/MAIN_'.$partId.'.pdf';
					}
						
					if(file_exists($path))
					{
						$zip->addFromString(basename($path),  file_get_contents($path));  
						$zip->renameName(basename($path),$partNumber.'.pdf');
					}
					else
					{
						$path = $_SERVER['DOCUMENT_ROOT'].'/Document Management System/Arktech Folder/ARK_'.$partId.'.pdf';
						if(file_exists($path))
						{
							$zip->addFromString(basename($path),  file_get_contents($path));  
							$zip->renameName(basename($path),$partNumber.'.pdf');
						}
					}
				}
				
				if (!is_writable(__DIR__)) { die('directory not writable'); }	
				if($zip->close() === false)
				{
				   exit("Error creating ZIP file");
				};
			}
		}
	}
	
	$fileNameArray = array();
	foreach (glob("AutoEmail/".$poNumber."-*.pdf") as $filename)
	{
		$fileNameArray[] = str_replace("AutoEmail/","",$filename);
	}

	$pdf->StartPageGroup();
	
	foreach($fileNameArray as $fileName)
	{
		if(file_exists($attachPathFile.$fileName)>0)
		{
			$pdf->StartPageGroup();
			$lastPage=$pdf->getlastpageNumber();
			$pdf->setlastpageNumber(($lastPage-1));	
			$pageCount = $pdf->setSourceFile($attachPathFile.$fileName);
			for ($i = 1; $i <= $pageCount; $i++) {
				$tplIdx = $pdf->importPage($i);
				$pageLayout = $pdf->getTemplateSize($tplIdx);
				$pdf->addPage($pageLayout['h'] > $pageLayout['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplIdx);
			}
		}
	}

	$pathFileName = $attachPathFile.$poNumber.".pdf";
	$pdf->Output($pathFileName,'F');
	
	if(file_exists($pathFileName)>0)
	{
		foreach($fileNameArray as $fileName)
		{
			if(file_exists($attachPathFile.$fileName)>0)	unlink($attachPathFile.$fileName);
		}
	}
	
	header('location:alfa_purchaseOrderSendEmail.php?poNumber='.$poNumber);
	// echo "next-alfa_purchaseOrderSendEmail.php?poNumber=".$poNumber;
?>
