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
		$partIdArray = array();
		$partIdArray_02 = array();
		$partIdArray_03 = array();
		$partIdArray_04 = array();
		$partIdArray_05 = array();
		$partIdArray_06 = array();
		$partIdArray_07 = array();
		$partIdArray_08 = array();
		$partIdArray_09 = array();
		$partIdArray_10 = array();
		$sql = "SELECT DISTINCT partId FROM ppic_lotlist WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND identifier = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_array())
			{
				if($rose_partIdcounter<20)$partIdArray[] = $resultLotList['partId'];
				else if($rose_partIdcounter<40)$partIdArray_02[] = $resultLotList['partId'];
				else if($rose_partIdcounter<60)$partIdArray_03[] = $resultLotList['partId'];
				else if($rose_partIdcounter<80)$partIdArray_04[] = $resultLotList['partId'];
				else if($rose_partIdcounter<100)$partIdArray_05[] = $resultLotList['partId'];
				else if($rose_partIdcounter<120)$partIdArray_06[] = $resultLotList['partId'];
				else if($rose_partIdcounter<140)$partIdArray_07[] = $resultLotList['partId'];
				else if($rose_partIdcounter<160)$partIdArray_08[] = $resultLotList['partId'];
				else if($rose_partIdcounter<180)$partIdArray_09[] = $resultLotList['partId'];
				else if($rose_partIdcounter<200)$partIdArray_10[] = $resultLotList['partId'];
				// $partIdArray[] = $resultLotList['partId'];
				$rose_partIdcounter++;
			}
		}
		$addZipString="";
		for($zz=0;$zz<10;$zz++)
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
			echo "<br>".$zz."~~".$addZipString."~~".count($rose_partId);
			// echo "<br>".$zz;
			if(count($rose_partId) > 0)
			{				
				// $zip = new ZipArchive();
				// $zip_name = $attachPathFile.$poNumber.$addZipString.".zip"; // Zip name
				
				// if(file_exists($zip_name)>0)	unlink($zip_name);
				
				// $zip->open($zip_name,  ZipArchive::CREATE);
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
						// $zip->addFromString(basename($path),  file_get_contents($path));  
						// $zip->renameName(basename($path),$partNumber.'.pdf');
					}
					else
					{
						$path = $_SERVER['DOCUMENT_ROOT'].'/Document Management System/Arktech Folder/ARK_'.$partId.'.pdf';
						if(file_exists($path))
						{
							// $zip->addFromString(basename($path),  file_get_contents($path));  
							// $zip->renameName(basename($path),$partNumber.'.pdf');
						}
					}
				}
				
				// if (!is_writable(__DIR__)) { die('directory not writable'); }	
				// if($zip->close() === false)
				// {
				   // exit("Error creating ZIP file");
				// };
			}
		}
	}
	
	// $fileNameArray = array();
	// foreach (glob("AutoEmail/".$poNumber."-*.pdf") as $filename)
	// {
		// $fileNameArray[] = str_replace("AutoEmail/","",$filename);
	// }

	// $pdf->StartPageGroup();
	
	// foreach($fileNameArray as $fileName)
	// {
		// if(file_exists($attachPathFile.$fileName)>0)
		// {
			// $pdf->StartPageGroup();
			// $lastPage=$pdf->getlastpageNumber();
			// $pdf->setlastpageNumber(($lastPage-1));	
			// $pageCount = $pdf->setSourceFile($attachPathFile.$fileName);
			// for ($i = 1; $i <= $pageCount; $i++) {
				// $tplIdx = $pdf->importPage($i);
				// $pageLayout = $pdf->getTemplateSize($tplIdx);
				// $pdf->addPage($pageLayout['h'] > $pageLayout['w'] ? 'P' : 'L');
				// $pdf->useTemplate($tplIdx);
			// }
		// }
	// }

	$pathFileName = $attachPathFile.$poNumber.".pdf";
	// $pdf->Output($pathFileName,'F');
	
	if(file_exists($pathFileName)>0)
	{
		foreach($fileNameArray as $fileName)
		{
			if(file_exists($attachPathFile.$fileName)>0)	unlink($attachPathFile.$fileName);
		}
	}
	
	// header('location:alfa_purchaseOrderSendEmail.php?poNumber='.$poNumber);
	// echo "next-alfa_purchaseOrderSendEmail.php?poNumber=".$poNumber;
?>
