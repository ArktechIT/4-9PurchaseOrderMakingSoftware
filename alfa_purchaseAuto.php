<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	
	if(!isset($_SESSION))
	{
		include('Templates/mysqliConnection.php');
		$_SESSION = array();
		$sql = "SELECT * FROM hr_employee WHERE idNumber = '0276' ";
		$queryEmployee = $db->query($sql);
		if($queryEmployee AND $queryEmployee->num_rows > 0)
		{
			$resultEmployee = $queryEmployee->fetch_assoc();
			$idNumber = $resultEmployee['idNumber'];
			$employeeId = $resultEmployee['employeeId'];
			$sectionId = $resultEmployee['sectionId'];
			$departmentId = $resultEmployee['departmentId'];

			$sql = "SELECT * FROM hr_employee where employeeId = '".$employeeId."'";
			$queryAccounts = $db->query($sql);
			if($queryAccounts AND $queryAccounts->num_rows > 0)
			{
				$resultAccounts = $queryAccounts->fetch_assoc();
				$userName = $resultAccounts['userName'];
				$password = $resultAccounts['userPassword'];
				$userType = $resultAccounts['userType'];

				$_SESSION['userID'] = $userName;
				$_SESSION['password'] = $password;
				$_SESSION['userPassword'] = $password;
				$_SESSION['sectionId'] = $sectionId;
				$_SESSION['userType'] = trim($userType);
				$_SESSION['userName'] = $userName;
				$_SESSION['employeeId'] = $employeeId;
				$_SESSION['idNumber'] = $idNumber;
				$_SESSION['departmentId'] = $departmentId;
			}
		}
	}
	
	
	include('PHP Modules/mysqliConnection.php');
	
	ini_set('display_errors','on');
	
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/gerald_functions.php');
	
	$sql = "SELECT poNumber FROM purchasing_podetailsnew WHERE emailDate like '0000-00-00 00:00:00' LIMIT 1";
	$queryPodetailsNew = $db->query($sql);
	if($queryPodetailsNew AND $queryPodetailsNew->num_rows > 0)
	{
		while($resultPodetailsNew = $queryPodetailsNew->fetch_assoc())
		{
		$poNumber = $resultPodetailsNew['poNumber'];
		echo "<br>";
		echo $poNumber;
		//header('location:alfa_purchaseOrderConverter.php?saveFile=1&poNumber='.$poNumber);
		}
	}
	
?>
