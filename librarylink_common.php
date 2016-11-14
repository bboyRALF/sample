<?php
/*
Taopix Library Link Asset Management Service Integration Example
Version 5.0 - Tuesday, 14th June 2016
Compatible with Taopix v2016r2
Copyright 2011 - 2016 Taopix Limited

• Overview
This is an example of how an integration to an asset management system could be performed.
The integration involves creating a script that acts as a broker between Taopix and the asset management system.
The script is responsible for:
1. Receiving and processing API commands from Taopix.
2. Converting them into requests to the asset management system.
3. Transmitting meta-data back to Taopix.
4. Transmitting asset binary data back to Taopix.

Asset binary data can be one of the following:
1. Thumbnail - An optional low-res version of the asset that can be used to display a preview to the user quickly while other data downloads.
2. FPO - A 'For Placement Only' proportionally correct low-res version of the asset that is used within Taopix designer until the hi-res version becomes available.
3. Original Hi-Res

This example code shows the following key concepts:
1. How to receive and decrypt Library Link API commands from Taopix Designer / Taopix Production.
2. How to process the API commands.
3. How a simple cache could be created to store the files that may be required by the integration (thumbnail / FPO etc...)
4. How to reply to the Taopix Desktop Designer, Online Designer, Online Image Server and Taopix Production.



• Installation Instructions
1. Create an asset service using Taopix Creator so that it accesses the librarylink_sample.php file and contains a unique API secret value.
2. Copy the files to a location that is available to a web server.
3. Modify the paths kAssetRootFilePath, kAssetFPOCacheRootFilePath, kAssetHiResCacheRootFilePath to point to the asset root path, local fpo cache folder, local hi-res cache folder.
4. Modify kAPISecret with a value that is also defined in the asset service.
5. Modify kEncryptHiResTransmission, kEncryptHiResWhenCataloging, kEncryptHiResMode to the desired settings.
6. Check the file / folder permissions to make sure that they can be accessed by the web server (the cache folders need read/write/modify/delete permissions).
7. Copy pictures (or folders of pictures) into the asset root path.
8. Execute the asset_management_catalog.php script to catalog the assets before attempting to view them within Taopix or after the files within the asset root path or sub-folders have changed.



• Cataloging Process
For demonstration purposes only, this example also implements the most basic of asset management system functions which is to catalog assets in a file structure.
The cataloging process scans the asset root path for pictures that have not already been cataloged or have changed. For each picture it performs the following action:
a) Generates a unique ID for each file and folder scanned and stores the information within the master dictionary file referenced by the constant kIDDictionaryFileName.
b) Creates any thumbnail, fpo and hi-res cache files that are required
c) Creates a meta-data file in the fpo cache folder structure for each file

A real asset management system would catalog the assets as part of it's core functionality. Depending on the asset management system used it might be possible to invoke
a custom script at the point of cataloging that creates any cache files required by the integration. 
If this is not possible then this should be done as a separate background task on the server.



• Meta-Data
It is possible to supply meta-data back to Taopix via a text file in the same folder as the hi-res asset.
The file must have the exact same filename with the prefix .inf (eg: a picture 10.jpg would have a meta-data file with the filename 10.jpg.inf).
The meta-data file consists of a set of key names and values separated by an equals sign (eg: pricetype=0)
The supported meta-data is as follows:
	name				-	The name of the asset that will be displayed to the user in Taopix multi-lingual string format (version 1 API names must be the file / folder name).
	downloadhires		- 	The Asset Trust level:
							Desktop Designer
								0 = Fully Trusted. Hi-res file can only be downloaded by Taopix Production.
								1 = Untrusted. Hi-res file can be downloaded by Taopix Designer (default).
							Taopix Online
								0 = Fully Trusted. Hi-res file not stored within Taopix Online and can only be downloaded by Taopix Production.
								1 = Untrusted - Browser Must Perform Transfer. Hi-res file is downloaded by Online Designer and then re-uploaded to Taopix Online (default).
								2 = Untrusted - Browser Must Not Perform Transfer. Hi-res file is queued for download by Taopix Online.
								3 = Trust Content - Browser Must Not Perform Transfer. Hi-res file is queued for download by Taopix Online but flight-check does not wait.
	encrypthires		- 	0 = Hi-res files downloaded to Taopix Designer are not encrypted (default). 1 = Hi-res files downloaded to Taopix Designer are encrypted.
	pricetype			- 	0 = Free Of Charge asset (default). 1 = Charged once per project. 2 = Charged every time used in project.
	unitcost			- 	The unit cost of the asset (default 0.00).
	unitsell			- 	The unit sell price of the asset (default 0.00).
	reportasset			- 	0 = The asset is not reported to the shopping cart (default). 1 = The asset is reported to the shopping cart (assets with a price are always reported).
	expirationdate		- 	The UTC expiration date of the asset in MySQL DateTime format.
Other meta-data that the system requires to operate (but is not stored in a customisable file) is:
	id						-	The unique ID for each folder and asset within the system.
	filename				-	The file / folder name.
	format					-	The high resolution asset file format - JPEG, TIFF or PNG.
	width					-	The high resolution asset width in pixels.
	height					-	The high resolution asset height in pixels.
	filesize				-	The high resolution asset file size.
	thumbwidth				-	The thumbnail width in pixels (or 0 if not required).
	thumbheight				-	The thumbnail height in pixels (or 0 if not required).
	fpowidth				-	The FPO width in pixels.
	fpoheight				-	The FPO height in pixels.
	fpohasalphachannel		-	-1 = The alpha channel status is not known. 0 = FPO / Hi-res does not contain an alpha channel. 1 = FPO / Hi-res does contain an alpha channel.
	fpofilesize				-	The FPO file size.
	assetlastmodified		-	The high resolution asset last modified date/time in MySQL DateTime format (Unix Timestamp in version 1 of the API).
	metadatalastmodified	-	The meta-data last modified date/time in MySQL DateTime format (Unix Timestamp in version 1 of the API).

A real asset management system would store this information within it's database and not in the file system.

NOTES
1. Once an asset has been made available it's dimensions or visual appearance should not be modified otherwise the end result cannot be guaranteed.

2. Taopix Designer may request updates to the following meta-data via the getinfo API call: 
		name, encrypthires, downloadhires, reportasset, pricetype, unitcost, unitsell, format, fpohasalphachannel
For Taopix Designer to detect a change in the meta-data the metadatalastmodified date must also have been updated. This property is automatically set to the meta-data file's modified time in the example
but would normally be stored in the asset management database. If this information is not available then the current date/time may be used.
		
3. If dealing with PNG and TIFF files it is critical to know if the picture contains an alpha channel.
If the high resolution file does contain an alpha channel the FPO must also contain an alpha channel otherwise the user will see major differences between on-screen and their printed product.
If the status of the alpha channel is not known then -1 must be returned for fpohasalphachannel which will instruct Taopix Designer to request the high resolution files.
If you do not wish Taopix Designer to download the high resolution files you must either have FPO files with alpha channels or avoid these files altogether.


4. The FPO file's dimensions must be proportionally correct to the high resolution file otherwise pictures may be printed with an incorrect crop.



• Notes On Pricing
Pricing should be returned in the correct currency. The API receives information on the currency and the integration must return the correct value.



• Folder Preview Pictures
It is possible to specify that an asset appears on a folder while navigating the asset service within Taopix.
This is done by adding a JPEG inside the asset folder with the filename defined in the constant kFolderThumbnailFileName (default _folderpreview.jpg).
The ID of the folder preview asset is returned via the folderpreviewid value within the response.



• API Calls
The main entry point called by Taopix is towards the end of the asset_management.php script.
This function is responsible for handling all security and processing the following commands:
	requesttoken	-	This command is sent when Taopix requires an authentication token for the session. The integration should use any supplied parameters to perform authentication and either return an authentication token or an error.
	checktoken		-	This command is sent when Taopix wishes to check the validity of the authentication token it has previously been supplied with.
	browse			-	This command is sent when Taopix wishes to browse a path within the asset service.
	getthumb		-	This command is sent when Taopix wishes to receive the thumbnail for an asset.
	getfpo			-	This command is sent when Taopix wishes to receive the FPO for an asset.
	gethires		-	This command is sent when Taopix wishes to receive the hi-res file for an asset. The integration is responsible for making sure that the request is valid.
	getinfo			-	This command is sent when Taopix wishes to receive updated meta-data (such as pricing an expiry information).
	getassets		-	This command is sent from Online Designer when Taopix wishes to receives meta-data (as per getinfo) for the items used for the Image Led workflow.
	search			-	This command is sent when Taopix wishes to search the asset service. The integration is responsible for handling the search and returning valid asset meta-data



• Authentication Tokens
Access to the service is controlled via an authentication token.
The first time that Taopix Designer connects to a service it will request a token. It is the responsibility of the integration to perform any authentication that is required before supplying a valid token.
It is the responsibility of the integration to generate and store authentication tokens. Depending on the security that is required it may not be necessary to generate unique tokens.
If unique tokens are being generated then they should not be expired as the requests using these tokens could arrive a long time after they were originally requested.



• DEBUGGING INFORMATION
This example writes debugging information to the location specified by the PHP error_log configuration. This can be controlled by the constant kDebugMode.



• IMPORTANT
Please note. This example is for reference only and should not be used in a production system due to the meta-data and indexes being stored as files within the folder structure. 
A real asset management system that uses a SQL based database should be used for the storage of meta-data as it will scale better and be more reliable than the file system.



• Compatibility
This example has been tested with PHP 5.2 / 5.3 and requires the GD2 and mcrypt libraries to operate.
*/


error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('display_errors', 'stderr');

define('kAPISecret', 'ed989b5058ee2b1a'); // api secret
define('kAssetRootFilePath', 'assetroot'); // root path of the assets
define('kAssetFPOCacheRootFilePath', 'fpocache'); // root path to the fpo cache
define('kAssetHiResCacheRootFilePath', 'hirescache'); // root path to the hi-res cache
define('kIDDictionaryFileName', '_iddictionary.inf'); // id dictionary filename
define('kIDDictionaryFileTypeFolder', 0); // type for folders within the id dictionary
define('kIDDictionaryFileTypeFile', 1); // type for files within the id dictionary
define('kTokenDictionaryFileName', '_tokendictionary.inf'); // authentication token dictionary filename
define('kFileFolderPermissions', octdec('0777')); // permissions for new files and folders
define('kEncryptHiResTransmission', true); // are we encrypting the transmission of hi-res data?
define('kEncryptHiResWhenCataloging', true); // are we encrypting the transmission of hi-res data when cataloging?
define('kEncryptHiResMode', 1); // 1 = encrypt the hi-res file for transmission  0 = copy the file to the cache unencrypted
define('kAPISourceDesktopDesigner', 0); // api request received from a desktop designer
define('kAPISourceProduction', 1); // api request received from production
define('kAPISourceOnlineDesigner', 2); // api request received from an online designer
define('kAPISourceOnlineImageServer', 3); // api request received from an online image server
define('kOnlineCORSDomain', 'http://www.taopix.com'); // the domain of the taopix online application

/*
convert the asset file path to the thumbnail cache path
*/
function assetPathToThumbnailCachePath($pAssetFilePath, $pEncrypted)
{
	if ($pEncrypted)
	{
		$fileExtension = 'ethmb';
	}
	else
	{
		$fileExtension = 'thumb';
	}
	
	return kAssetFPOCacheRootFilePath . substr($pAssetFilePath, strlen(kAssetRootFilePath)) . $fileExtension;
}


/*
convert the asset file path to the fpo cache path
*/
function assetPathToFPOCachePath($pAssetFilePath, $pEncrypted)
{
	/*
	add an extension to encryption fpo files
	*/
	if ($pEncrypted)
	{
		$fileExtension = 'efpo';
	}
	else
	{
		$fileExtension = '';
	}
	
	return kAssetFPOCacheRootFilePath . substr($pAssetFilePath, strlen(kAssetRootFilePath)) . $fileExtension;
}


/*
convert the asset file path to the encrypted high resolution file cache path
*/
function assetPathToEncryptedhiResCachePath($pAssetFilePath)
{
	return kAssetHiResCacheRootFilePath . substr($pAssetFilePath, strlen(kAssetRootFilePath));
}


/*
read the cached meta-data file and the optional user meta-data file for an image
*/
function readMetaData($pHiResFilePath, $pCacheFileHandle, $pCacheFileLength, &$pResultArray)
{
	$id = '';
	$fileName = '';
	$format = 'JPEG';
	$width = 0;
	$height = 0;
	$fileSize = 0;
	$fileModifiedTime = 0;
	$thumbWidth = 0;
	$thumbHeight = 0;
	$fpoWidth = 0;
	$fpoHeight = 0;
	$fpoHasAlphaChannel = 0;
	$fpoFileSize = 0;
	$name = '';
	$designerCanDownloadHiRes = 1;
	$encryptHiRes = 0;
	$priceType = 0;
	$unitCost = 0.00;
	$unitSell = 0.00;
	$reportAsset = 0;
	$metaDataLastModified = 0;
	$expirationDate = '';
	
	$fpoPath = assetPathToFPOCachePath($pHiResFilePath, false);
	if (file_exists($fpoPath))
	{
		$fpoFileSize = filesize($fpoPath);
	}
	
	if ($pCacheFileHandle)
	{
		$cacheData = fread($pCacheFileHandle, $pCacheFileLength);
		$cacheDataArray = explode("\n", $cacheData);
										
		$id = $cacheDataArray[0];
		$fileName = $cacheDataArray[1];
		$name = 'en ' . $fileName;
		$format = $cacheDataArray[2];
		$width = (int) $cacheDataArray[3];
		$height = (int) $cacheDataArray[4];
		$fileSize = (int) $cacheDataArray[5];
		$fileModifiedTime = (int) $cacheDataArray[6];
		$thumbWidth = (int) $cacheDataArray[7];
		$thumbHeight = (int) $cacheDataArray[8];
		$fpoWidth = (int) $cacheDataArray[9];
		$fpoHeight = (int) $cacheDataArray[10];
		$fpoHasAlphaChannel = (int) $cacheDataArray[11];
	}
	
	$pResultArray['id'] = $id;
	$pResultArray['filename'] = $fileName;
	$pResultArray['format'] = $format;
	$pResultArray['width'] = $width;
	$pResultArray['height'] = $height;
	$pResultArray['filesize'] = $fileSize;
	$pResultArray['filemodifiedtime'] = $fileModifiedTime;
	$pResultArray['thumbwidth'] = $thumbWidth;
	$pResultArray['thumbheight'] = $thumbHeight;
	$pResultArray['fpowidth'] = $fpoWidth;
	$pResultArray['fpoheight'] = $fpoHeight;
	$pResultArray['fpohasalphachannel'] = $fpoHasAlphaChannel;
	$pResultArray['fpofilesize'] = $fpoFileSize;
	
	
	/*
	read the meta-data file if it exists
	*/
	$metaDataFilePath = $pHiResFilePath . '.inf';
	if (file_exists($metaDataFilePath))
	{
		$metaDataFileHandle = fopen($metaDataFilePath, 'rb');
		if ($metaDataFileHandle)
		{
			$metaDataLineArray = Array();
			
			// determine if the file has a utf-8 bom and skip it if it does
			$bom = fread($metaDataFileHandle, 3);
			if ($bom != b"\xEF\xBB\xBF")
				rewind($metaDataFileHandle);
		
			while (! feof($metaDataFileHandle))
			{
				$metaDataLineArray[] = trim(fgets($metaDataFileHandle));
			}
		
			$metaDataArray = readCommandData($metaDataLineArray);
			fclose($metaDataFileHandle);
			
			$name = getCommandParam($metaDataArray, 'name', $name);
			$designerCanDownloadHiRes = (int) getCommandParam($metaDataArray, 'downloadhires', 1);
			$encryptHiRes = (int) getCommandParam($metaDataArray, 'encrypthires', 0);
			$priceType = (int) getCommandParam($metaDataArray, 'pricetype', 0);
			$unitCost = (float) getCommandParam($metaDataArray, 'unitcost', 0.00);
			$unitSell = (float) getCommandParam($metaDataArray, 'unitsell', 0.00);
			$reportAsset = (int) getCommandParam($metaDataArray, 'reportasset', 0);
			$metaDataLastModified = filemtime($metaDataFilePath);
			$expirationDate = getCommandParam($metaDataArray, 'expirationdate', '');
		}
	}

	$pResultArray['name'] = $name;
	$pResultArray['downloadhires'] = $designerCanDownloadHiRes;
	$pResultArray['encrypthires'] = $encryptHiRes;
	$pResultArray['pricetype'] = $priceType;
	$pResultArray['unitcost'] = $unitCost;
	$pResultArray['unitsell'] = $unitSell;
	$pResultArray['reportasset'] = $reportAsset;
	$pResultArray['metadatalastmodified'] = $metaDataLastModified;
	$pResultArray['expirationdate'] = $expirationDate;
	$pResultArray['assetlastmodified'] = filemtime($pHiResFilePath);
}


/*
find an image id within the dictionary file
*/
function findDictionaryID($pID, $pType, $pAddIfNotExists = false, $pFilePath = '')
{
	$result = '';

	$idExists = false;
	$dictionaryFilePath = kAssetFPOCacheRootFilePath . '/' . kIDDictionaryFileName;
	
	$dictionaryFileHandle = fopen($dictionaryFilePath, 'a+');
	if ($dictionaryFileHandle)
	{
		/*
		we have opened the file so now obtain an exclusive lock
		*/
		set_time_limit(180);
		flock($dictionaryFileHandle, LOCK_EX);
		
		clearstatcache();
		$dictionaryFileSize = filesize($dictionaryFilePath);
		if ($dictionaryFileSize > 0)
		{
			$dictionaryData = fread($dictionaryFileHandle, $dictionaryFileSize);
			$dictionaryDataArray = explode("\n", $dictionaryData);
			
			foreach ($dictionaryDataArray as &$item)
			{
				$itemDataArray = explode("\t", $item);
				
				if (($itemDataArray[0] == $pID) && (((int) $itemDataArray[1]) == $pType))
				{

					$result = $itemDataArray[2];
					break;
				}
			}
		}
		
		if (($result == '') && ($pAddIfNotExists))
		{
			fwrite($dictionaryFileHandle, $pID . "\t" . $pType . "\t" . $pFilePath . "\n");
		}
	
		fclose($dictionaryFileHandle);
		
		changePermissions($dictionaryFilePath);
	}
		
	return $result;
}


/*
create an encrypted high resolution file
the encryption here is for transmission purposes only as the final encryption of the downloaded data is controlled by the meta-data (or will be unencrypted within taopix production)
in this example the files are cached to disk as once created the service could rely on the web server serving the file rather than echo'ing the data back
in a real integration it would be beneficial to create these files before they are needed
*/
function createEncryptedHiRes($pHiResFilePath, $pEncryptionMode)
{
	$resultArray = Array();
	$hiResCreated = false;
	
	$encryptedHiResPath = assetPathToEncryptedhiResCachePath($pHiResFilePath);
	
	/*
	lock the meta-data file while creating the encrypted hi-res file
	this should prevent concurrency issues if multiple requests are made for the same hi-res file
	*/
	$fpoPath = assetPathToFPOCachePath($pHiResFilePath, false);
	$cacheFileHandle = fopen($fpoPath . '.inf', 'a+');
	if ($cacheFileHandle)
	{
		/*
		we have opened the file so now obtain an exclusive lock
		*/
		set_time_limit(180);
		flock($cacheFileHandle, LOCK_EX);
	
		/*
		just check to see if the encrypted file already exists
		*/
		if (! file_exists($encryptedHiResPath))
		{
			$imageData = file_get_contents($pHiResFilePath);

			/*
			calculate the cache folder path
			*/
			$cachePath = dirname(assetPathToEncryptedhiResCachePath($pHiResFilePath));
			
			/*
			if the hi-res cache path does not exist create it
			*/
			if (! file_exists($cachePath))
			{
				$origMask = umask(0);
				$dirResult = @mkdir($cachePath, kFileFolderPermissions, true);
				umask($origMask);
			}

			$fp = fopen($encryptedHiResPath, 'w');
			
			/*
			encrypt the hi-res data using the secret
			*/
			if ($pEncryptionMode == 1)
			{
				$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
				$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
				fwrite($fp, 'TAOPIXAT01' . strlen($imageData) . '.' . base64_encode($iv) . '.' . 
						mcrypt_encrypt(MCRYPT_BLOWFISH, kAPISecret, $imageData, MCRYPT_MODE_CBC, $iv));
			}
			else
			{
				fwrite($fp, $imageData);
			}
			
			fclose($fp);
		}
		
		$hiResCreated = true;
		
		fclose($cacheFileHandle);
	}

	$resultArray['result'] = $hiResCreated;
	$resultArray['encryptedfilepath'] = $encryptedHiResPath;
	
	return $resultArray;
}


/*
read the command data and return it as an exploded array
*/
function readCommandData($pCommandData)
{
	$resultArray = Array();
	
	if (is_array($pCommandData))
	{
		$commandDataArray = $pCommandData;
	}
	else
	{
		$commandDataArray = explode("\n", $pCommandData);
	}
	
	foreach ($commandDataArray as &$item)
	{
		$pieces = explode('=', $item);
		$option = trim($pieces[0]);
		if (count($pieces) > 1)
		{
			$value = trim($pieces[1]);
		}
		else
		{
			$value = '';
		}
		$resultArray[$option] = $value;
	}
			
	return $resultArray;
}


/*
return the parameter's value or the default value if it isn't present
*/
function getCommandParam($pCommandData, $pKey, $pDefaultValue = '')
{
	if (array_key_exists($pKey, $pCommandData))
	{
		return $pCommandData[$pKey];
	}
	else
	{
		return $pDefaultValue;
	}
}


/*
correct the supplied path making sure it either has or has not got a trailing separator
*/
function correctPath($pSourcePath, $pSeparator = "/", $pTrailing = false)
{
	$lastChar = substr($pSourcePath, -1, 1);
	
	if (($pTrailing) && ($lastChar != $pSeparator))
	{
		$pSourcePath = $pSourcePath . $pSeparator;
	}
	elseif (($pTrailing == false) && ($lastChar == $pSeparator))
	{
		$pSourcePath = substr($pSourcePath, 0, strlen($pSourcePath) -1);
	}
	
	return $pSourcePath;
}


/*
change the file/folder permissions to make sure that all parts of the integration have access
*/
function changePermissions($pSourcePath)
{
	$origMask = umask(0);
	$result = @chmod($pSourcePath, kFileFolderPermissions);
	umask($origMask);
}


/*
Base64 decode a string that might which might have been made url safe
*/
function tpBase64Decode($pSourceData)
{
	return base64_decode(strtr($pSourceData, '-_,', '+/='));
}

/*
Base64 encode a string based on the api source making it url safe for online designers
*/
function tpBase64Encode($pSourceData, $pAPISource)
{
	if ($pAPISource == kAPISourceOnlineDesigner)
	{
		return strtr(base64_encode($pSourceData), '+/=', '-_,');
	}
	else
	{
		return base64_encode($pSourceData);
	}
}
	
?>