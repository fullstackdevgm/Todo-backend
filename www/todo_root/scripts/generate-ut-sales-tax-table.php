#!/usr/bin/php -q
<?php
/*
 
 The purpose of this script is to populate/update the tdo_sales_tax table.
 
 Required Input Files:
	1. Utah Sales & Use Tax Rates
	2. Telecom Tax Rate Boundaries File
 
 Full steps:
 
 1. Download the latest "Simple" Utah Sales & Use Tax Rates file in "xls"
	format available here: http://tax.utah.gov/sales/rates
 
 2. Open the file in Excel and copy the two columns that have the county/city
	code and the combined sales and use tax. Paste this information into a new
	document and convert the second column (the sales tax) into number format
	with 4 decimal places (so that we don't have to parse out the '%' char).
	Export to CSV with File > Save as. The first line of the file should be data
	and NOT a column header. Save the file as "ut-sales-tax.csv". This file
	should be placed in the same directory as this script.
 
 3. Download the newest Telecom Tax Reate Boundaries file (ZIP file) available
	here: http://tax.utah.gov/utah-taxes/telecom-download
 
 4. Unzip the telecom file and get the utscmtsa20121001_all.txt file inside of
	it. It will need to be placed into the same directory as this script and
	its name should be specified as a PHP define.
 
 5. Upload this script and the supporting files to one of the production
	servers.
 
 6. You will probably want to take the service offline in order to make the
	change using the 'todoproctl.sh' script.
 
 7. cd into the directory on the production server that you uploaded the files
	in step 5.
 
 8. Run the script. Errors will be output to the screen (if any) or a success
	message will be shown.
*/
	
	include_once('TodoOnline/base_sdk.php');
	
	define('TAX_RATES_FILENAME', 'ut-sales-tax.csv');
	define('TELECOM_DATA_FILENAME', 'ustcmtsa20121001_all.txt');
	
	function createTempTable($link)
	{
		if (empty($link))
		{
			echo "Could not create the temp table because there's no link to the DB\n";
			return false;
		}
		
		$sql = "CREATE TABLE citycoderatelookup(citycode VARCHAR(5), taxrate DECIMAL(5,4) DEFAULT 0, INDEX citycoderatelookup_pk(citycode)) ENGINE=MEMORY";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			echo "Error creating the temp table: " . mysql_error() . "\n";
			return false;
		}
		
		return true;
	}
	
	function dropTempTable($link)
	{
		if (empty($link))
		{
			echo "Could not drop the temp table because there's no link to the DB\n";
			return false;
		}
		
		$sql = "DROP TABLE citycoderatelookup";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			echo "Error dropping the temp table: " . mysql_error() . "\n";
			return false;
		}
		
		return true;
	}
	
	function getRateForCityCode($cityCode, $link)
	{
		if ($link == NULL)
		{
			echo "ERROR: Called getRateForCityCode with null DB link\n";
			return false;
		}
		
		$cityCode = mysql_real_escape_string($cityCode, $link);
		$sql = "SELECT taxrate FROM citycoderatelookup WHERE citycode='$cityCode'";
		if ($result = mysql_query($sql, $link))
		{
			if ($row = mysql_fetch_array($result))
			{
				return $row['taxrate'];
			}
		}
		
		return false;
	}
	
	function addCityTaxRate($cityCode, $taxRate, $link)
	{
		if ($link == NULL)
		{
			echo "ERROR: No connection to the database\n";
			return false;
		}
		
		if (getRateForCityCode($cityCode, $link))
		{
			// Already exists, ignore
			return true;
		}
		
		$cityCode = mysql_real_escape_string($cityCode, $link);
		$sql = "INSERT INTO citycoderatelookup (citycode, taxrate) VALUES ('$cityCode', $taxRate)";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			echo "ERROR inserting into the databsae: " . mysql_error() . "\n";
			return false;
		}
		
		return true;
	}
	
	function addZipCodeTaxRate($zipCode, $taxRate, $cityName, $link)
	{
		if ($link == NULL)
		{
			echo "ERROR: No connection to the database\n";
			return false;
		}
		
		$zipCode = mysql_real_escape_string($zipCode, $link);
		$cityName = mysql_real_escape_string($cityName, $link);
		$sql = "INSERT INTO tdo_sales_tax (zipcode, taxrate, cityname) VALUES ('$zipCode', $taxRate, '$cityName')";
		$response = mysql_query($sql, $link);
		if (!$response)
		{
			echo "ERROR inserting into the tdo_sales_tax table: " . mysql_error() . "\n";
			return false;
		}
		
		return true;
	}
	
	$link = TDOUtil::getDBLink();
	if (!$link)
	{
		echo "ERROR getting a link to the database\n";
		exit(1);
	}
	
	
	//
	// Go through the telecom data file and build a map of city codes to zip
	// codes.
	//
	if (!createTempTable($link))
	{
		echo "Error creating the temp table, exiting.\n";
		exit(1);
	}
	
	//
	// Parse the sales tax rates file
	//
	// This is a windows CR file, so set this:
	ini_set("auto_detect_line_endings", true);
	
	$f = fopen(TAX_RATES_FILENAME, 'r');
	while ($line = fgets($f))
	{
		$parts = explode(",", $line);
		if (count($parts) != 2)
		{
			echo "Encountered a line that does not have two parts of data: $line\n";
			continue;
		}
		
		$unparsedCityCode = $parts[0];
		if (empty($unparsedCityCode))
			continue; // section header
		
		$taxRate = $parts[1];
		$cityCode = substr($unparsedCityCode, 0, 2) . substr($unparsedCityCode, 3, 3);
		
		if (!addCityTaxRate($cityCode, $taxRate, $link))
		{
			echo "Error adding tax rate ($taxRate) for city code ($cityCode).\n";
			dropTempTable($link);
			TDOUtil::closeDBLink($link);
			exit(1);
		}
		//echo "Unparsed city code: $unparsedCityCode, City Code: $cityCode, Tax Rate: $taxRate\n";
	}
	fclose($f);
	
	
	// Do this inside of a DB transaction in case anything goes wrong
	
	if(!mysql_query("START TRANSACTION", $link))
	{
		echo "ERROR: Could not start a DB transaction to safely update the tdo_sales_tax table.\n";
		dropTempTable($link);
		TDOUtil::closeDBLink($link);
		exit(1);
	}
	
	if (!mysql_query("TRUNCATE TABLE tdo_sales_tax", $link))
	{
		echo "ERROR: Could not truncate the tdo_sales_tax table before populating with new data\n";
		mysql_query("ROLLBACK");
		dropTempTable($link);
		TDOUtil::closeDBLink($link);
		exit(1);
	}

	$f = fopen(TELECOM_DATA_FILENAME, 'r');
	while ($line = fgets($f))
	{
		// This file is arranged with fixed-width fields
		$cityName = trim(substr($line, 86, 28));
		$zipCode = substr($line, 114, 5);
		$countyCode = substr($line, 151, 2);
		$cityCode = substr($line, 153, 3);
		$cityCountyCode = $countyCode . $cityCode;
		//echo "County Code: $countyCode, City Code: $cityCode, ZIP Code: $zipCode, City Name: $cityName\n";
		
		if ($cityCode == "000")
			continue; // Skip over the county addresses because they have multiple zip codes associated
		
		$zipInfo = TDOTeamAccount::getTaxRateInfoForZipCode($zipCode, $link);
		if ($zipInfo)
		{
			// We already know about this zip code, so we can skip it
			continue;
		}
		
		$taxRate = getRateForCityCode($cityCountyCode, $link);
		if (!$taxRate)
		{
			echo "Could not find tax rate information for city code: $cityCountyCode\n";
			mysql_query("ROLLBACK", $link);
			dropTempTable($link);
			TDOUtil::closeDBLink($link);
			exit(1);
		}
		
		
		
		
		if (!addZipCodeTaxRate($zipCode, $taxRate, $cityName, $link))
		{
			echo "Error adding zip code tax rate info ($zipCode, $taxRate, $cityName) into tdo_sales_tax table.\n";
			mysql_query("ROLLBACK", $link);
			dropTempTable($link);
			TDOUtil::closeDBLink($link);
			exit(1);
		}
	}
	fclose($f);
	
	
	if (!mysql_query("COMMIT", $link))
	{
		echo "ERROR committing changes to tdo_sales_tax table: " . mysql_error() . "\n";
	}
	else
	{
		echo "SUCCESSFULLY updated the tdo_tax_table!\n";
	}
	
	dropTempTable($link);
	TDOUtil::closeDBLink($link);
?>
