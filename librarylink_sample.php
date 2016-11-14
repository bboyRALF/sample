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

require_once('librarylink_common.php');

/*
********************************************************************************************
The following are functions that interface Taopix to the asset management system.
This would be replaced with whatever code is required to perform the integration.
********************************************************************************************
*/

define('kFolderThumbnailFileName', '_folderpreview.jpg'); // the filename for the folder preview asset
define('kCompressTransmission', true); // are we compressing the non-image data we return?
define('kDebugMode', true); // debug mode writes information to the log


/*
getAssetMetaData
This function is responsible for returning the meta-data for a single asset referenced by the file path.
The demonstration system retrieves the data from the file system.
A real integration would possibly use an api call or a database connection to the asset management system to achieve this task.
*/
function getAssetMetaData($pFilePath, $pAPIVersion, &$pResult)
{
	$resultArray = Array();
	$result = false;
	
	if (file_exists($pFilePath))
	{
		if (is_file($pFilePath))
		{
			/*
			make sure the cache file exists
			*/
			$cacheFilePath = assetPathToFPOCachePath($pFilePath, false) . '.inf';
			if (file_exists($cacheFilePath))
			{
				$cacheFileSize = filesize($cacheFilePath);
				
				$cacheFileHandle = fopen($cacheFilePath, 'rb');
				if ($cacheFileHandle)
				{
					readMetaData($pFilePath, $cacheFileHandle, $cacheFileSize, $resultArray);
					
					/*
					if the api version is greater than 1 then we need to convert the data
					*/
					if ($pAPIVersion > 1)
					{
						$resultArray['metadatalastmodified'] = date('Y-m-d H:i:s', $resultArray['metadatalastmodified']); // return the date in mysql format
						$resultArray['assetlastmodified'] = date('Y-m-d H:i:s', $resultArray['assetlastmodified']); // return the date in mysql format
					}
					else
					{
						/*
						version 1 only support one name so we must use the filename
						*/
						$resultArray['name'] = $resultArray['filename'];
					}
					
					/*
					obtain the fpo path and make sure it exists
					*/
					$fpoPath = assetPathToFPOCachePath($pFilePath, false);
					if (file_exists($fpoPath))
					{
						/*
						the fpo file exists so now check to see if we have a thumbnail
						*/
						if (($resultArray['thumbwidth'] > 0) && ($resultArray['thumbheight'] > 0))
						{
							$thumbnailPath = assetPathToThumbnailCachePath($pFilePath, false);
							if (file_exists($thumbnailPath))
							{
								/*
								we have a thumbnail file so we can use this asset
								*/
								$result = true;
							}
						}
						else
						{
							/*
							we don't have a thumbnail but have a fpo so we can use this asset
							*/
							$result = true;
						}
					
					}
					
					fclose($cacheFileHandle);
				}
			}
			
			$resultArray['file'] = 1;
		}
		else
		{
			/*
			processing a folder
			*/
			$id = md5(substr($pFilePath, strlen(kAssetRootFilePath))); // create the assetid from the path relative to the root path
			findDictionaryID($id, kIDDictionaryFileTypeFolder, true, $pFilePath);
			
			/*
			see if we have a folder preview asset
			these are stored in the folder with the name specified in the kFolderThumbnailFileName constant
			*/
			$folderPreviewID = '';
			$assetFolderPreviewPath = correctPath($pFilePath) . '/' . kFolderThumbnailFileName;
			if (file_exists($assetFolderPreviewPath))
			{
				$cacheFilePath = assetPathToFPOCachePath($assetFolderPreviewPath, false) . '.inf';
				if (file_exists($cacheFilePath))
				{
					$cacheFileSize = filesize($cacheFilePath);
					
					$cacheFileHandle = fopen($cacheFilePath, 'rb');
					if ($cacheFileHandle)
					{
						readMetaData($assetFolderPreviewPath, $cacheFileHandle, $cacheFileSize, $folderResultArray);
						$folderPreviewID = $folderResultArray['id'];
					}
					
					fclose($cacheFileHandle);
				}
			}
			
			$result = true;
			$resultArray['file'] = 0;
			$resultArray['filename'] = basename($pFilePath);
			
			/*
			if the api version is greater than 1 then we need to return the folder display name in Taopix multi-lingual format
			*/
			if ($pAPIVersion > 1)
			{
				$resultArray['name'] = 'en ' . $resultArray['filename'];
			}
			else
			{
				/*
				version 1 only support one name so we must use the folder name
				*/
				$resultArray['name'] = $resultArray['filename'];
			}
			
			$resultArray['id'] = $id;
			$resultArray['folderpreviewid'] = $folderPreviewID;
		}
	}
	
	$pResult = $result;

	return $resultArray;
}


/*
scanPath
This function is responsible for scanning an entire path and returning the meta-data for all items within the path.
The demonstration system retrieves the data from the file system.
A real integration would possibly use an api call or a database connection to the asset management system to achieve this task.
*/
function scanPath($pSourcePath, $pPage, $perPage, $pAPIVersion)
{
	$resultArray = Array();
	$scanResultArray = Array();
	$itemCount = 0;
	$count = 0;
	$page = 1;
	$pageCount = 0;
	
	if (is_dir($pSourcePath))
	{
		if ($dirHandle = opendir($pSourcePath))
		{
			while (($fileName = readdir($dirHandle)) !== false)
			{
				/*
				make sure the filename is valid
				*/
				if ((substr($fileName, 0, 1) != '.') && ($fileName != kFolderThumbnailFileName))
				{
					$filePath = correctPath($pSourcePath) . '/' . $fileName;
					$cacheResultArray = getAssetMetaData($filePath, $pAPIVersion, $result);
					if ($result)
					{
						/*
						if we are processing the current page add the result
						we also check the api version as version 1 does not allow chargeable assets, assets with an expiry date, assets that must be reported or the ability for production to download the assets
						*/
						
						$include = false;
						
						if ($page == $pPage)
						{
							if ($pAPIVersion == 1)
							{
								if ($cacheResultArray['file'] == 1)
								{
									if (($cacheResultArray['pricetype'] == 0) && ($cacheResultArray['expirationdate'] == '') && 
											($cacheResultArray['reportasset'] == 0) && ($cacheResultArray['downloadhires'] == 1))
									{
										$include = true;
									}
								}
								else
								{
									$include = true;
								}
							}
							else
							{
								$include = true;
							}
						}
						
						if ($include)
						{
							unset($cacheResultArray['filemodifiedtime']); // remove this property from the asset meta-data as it is only used for the cataloging process
							$scanResultArray[] = $cacheResultArray;
							$itemCount++;
						}
						
						
						/*
						increment the item counter and if we have reached the per page count increment the current page
						*/					
						$count++;					
						if ($count == 1)
						{
							$pageCount++;
						}
						
						if ($count == $perPage)
						{
							$page++;
							$count = 0;
						}
					}
				}					
				
			}
			closedir($dirHandle);
		}
	}
	
	$resultArray['itemcount'] = $itemCount;
	$resultArray['pages'] = $pageCount;
	$resultArray['items'] = $scanResultArray;
	
	return $resultArray;
}


/*
searchAssets
This function is responsible for searching for assets and returning the meta-data for all matching items.
The demonstration system searches the id dictionary for filenames containing the search string.
A real integration would possibly use an api call or a database connection to the asset management system to achieve this task.
The integration is responsible for making sure that all search results are valid for the login represented by the authentication token.
*/
function searchAssets($pRootPath, $pSearchString, $pPage, $perPage, $pAPIVersion)
{
	$resultArray = Array();
	$searchResultsArray = Array();
	$count = 0;
	$page = 1;
	$pageCount = 0;
	$pathArray = Array();
	$itemCount = 0;
	
	if ($pSearchString != '')
	{
		/*
		make the search string lowercase as we will perform a case insensitive search
		*/
		$pSearchString = mb_strtolower($pSearchString);
		
		$dictionaryFilePath = kAssetFPOCacheRootFilePath . '/' . kIDDictionaryFileName;
		$dictionaryFileHandle = fopen($dictionaryFilePath, 'r');
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
					if ((count($itemDataArray) == 3) && (((int) $itemDataArray[1]) == 1))
					{
						/*
						make sure that the asset filename matches, that it is within the supplied path and is not the folder preview asset
						*/
						$fileName = mb_strtolower(basename($itemDataArray[2]));
						if ((strpos($fileName, $pSearchString) !== false) && (substr($itemDataArray[2], 0, strlen($pRootPath)) == $pRootPath) && ($fileName != kFolderThumbnailFileName))
						{
							$pathArray[] = $itemDataArray[2];
						}
					}
				}
				
			}
		
			fclose($dictionaryFileHandle);
			
			
			/*
			process the search results after we have unlocked the id dictionary
			*/
			foreach ($pathArray as &$item)
			{
				$cacheResultArray = getAssetMetaData($item, $pAPIVersion, $result);
				if ($result)
				{
					/*
					if we are processing the current page add the result
					we also check the api version as version 1 does not allow chargeable assets, assets with an expiry date, assets that must be reported or the ability for production to download the assets
					*/
					
					$include = false;
						
					if ($page == $pPage)
					{
						if ($pAPIVersion == 1)
						{
							if ($cacheResultArray['file'] == 1)
							{
								if (($cacheResultArray['pricetype'] == 0) && ($cacheResultArray['expirationdate'] == '') && 
										($cacheResultArray['reportasset'] == 0) && ($cacheResultArray['downloadhires'] == 1))
								{
									$include = true;
								}
							}
							else
							{
								$include = true;
							}
						}
						else
						{
							$include = true;
						}
					}
					
					if ($include)
					{
						unset($cacheResultArray['filemodifiedtime']); // remove this property from the asset meta-data as it is only used for the cataloging process
						$searchResultsArray[] = $cacheResultArray;
						$itemCount++;
					}
					

					/*
					increment the item counter and if we have reached the per page count increment the current page
					*/					
					$count++;					
					if ($count == 1)
					{
						$pageCount++;
					}
					
					if ($count == $perPage)
					{
						$page++;
						$count = 0;
					}
				}
			}
	
		}
	}
	
	$resultArray['itemcount'] = $itemCount;
	$resultArray['pages'] = $pageCount;
	$resultArray['items'] = $searchResultsArray;
	
	return $resultArray;
}


/*
requestToken
This function is responsible for handling requests for authentication tokens.
The demonstration system simply creates a unique token and stores it within the file specified by the kTokenDictionaryFileName constant.
S real integration would possibly use an api call or a database connection to the asset management system to achieve this task.
Any user authentication that is required would also be performed here. This example performs no authentication.
Current authentication modes are:
1 = Auto
2 = Login ($pAuthParam1) & Password ($pAuthParam2)
3 = Login ($pAuthParam1)
4 = User Name ($pAuthParam1)
5 = Password ($pAuthParam1)
6 = Authorization Key ($pAuthParam1)
7 = Code ($pAuthParam1)
*/
function requestToken($pAuthMode, $pAuthParam1, $pAuthParam2, $pLicenseKeyCode, $pMountPoint, $pSSOToken, $pUserID)
{
	$resultArray = Array();
	$result = 'OK';
	$token = '';

	/*
	we would normally perform some validation before generating a token
	however, in this case we will just generate a token
	*/
	$tokenFilePath = kAssetFPOCacheRootFilePath . '/' . kTokenDictionaryFileName;
	$tokenFileHandle = fopen($tokenFilePath, 'a+');
	if ($tokenFileHandle)
	{
		/*
		we have opened the file so now obtain an exclusive lock
		*/
		set_time_limit(180);
		flock($tokenFileHandle, LOCK_EX);
		
		$token = uniqid('', true);
		
		/*
		write the token data
		NOTE. this example is writing all authentication parameters to the file. this could expose passwords which should not be done for security in a real integration
		*/
		fwrite($tokenFileHandle, $token . "\t" . $pAuthMode . "\t" . $pAuthParam1 . "\t" . $pAuthParam2 . "\t" . $pLicenseKeyCode . "\t" . $pMountPoint . "\t" . $pUserID . "\t" . $pSSOToken . "\n");
		
		fclose($tokenFileHandle);
	}
	else
	{
		$result = 'TOKENERROR';
	}
	
	$resultArray['result'] = $result;
	$resultArray['token'] = $token;
	
	return $resultArray;
}


/*
checkToken
This function is responsible for checking authentication tokens.
The demonstration system simply looks up the previously stored tokens within the file specified by the kTokenDictionaryFileName constant.
A real integration would possibly use an api call or a database connection to the asset management system to achieve this task.
*/
function checkToken($pToken)
{
	$resultArray = Array();
	$result = 'INVALIDTOKEN';
	$authMode = 0;
	$authParam1 = '';
	$authParam2 = '';
	$licenseKeyCode = '';
	$mountPoint = '';

	if ($pToken != '')
	{
		$tokenFilePath = kAssetFPOCacheRootFilePath . '/' . kTokenDictionaryFileName;
		$tokenFileHandle = fopen($tokenFilePath, 'a+');
		if ($tokenFileHandle)
		{
			/*
			we have opened the file so now obtain an exclusive lock
			*/
			set_time_limit(180);
			flock($tokenFileHandle, LOCK_EX);
			
			clearstatcache();
			$tokenFileSize = filesize($tokenFilePath);
			if ($tokenFileSize > 0)
			{
				$dictionaryData = fread($tokenFileHandle, $tokenFileSize);
				$dictionaryDataArray = explode("\n", $dictionaryData);
				
				foreach ($dictionaryDataArray as &$item)
				{
					$itemDataArray = explode("\t", $item);
					
					if ($itemDataArray[0] == $pToken)
					{
						$authMode = (int) $itemDataArray[1];
						$authParam1 = $itemDataArray[2];
						$authParam2 = $itemDataArray[3];
						$licenseKeyCode = $itemDataArray[4];
						$mountPoint = $itemDataArray[5];
						$ssoToken = $itemDataArray[6];
						$userID = $itemDataArray[7];
						
						$result = 'OK';
						
						break;
					}
				}
			}
			
			fclose($tokenFileHandle);
		}
	}
	
	$resultArray['result'] = $result;
	$resultArray['token'] = $pToken;
	$resultArray['authmode'] = $authMode;
	$resultArray['authparam1'] = $authParam1;
	$resultArray['authparam2'] = $authParam2;
	$resultArray['licensekeycode'] = $licenseKeyCode;
	$resultArray['mountpoint'] = $mountPoint;
	$resultArray['ssotoken'] = $ssoToken;
	$resultArray['userid'] = $userID;
	
	return $resultArray;
}


/*
****************************************************
main entry point that receives requests from Taopix
****************************************************
*/
$resultArray = Array();
$resultArray['version'] = '1';  // add the entry here so that it appears first in the json data
$resultArray['result'] = ''; // add the entry here so that it appears first in the json data

$result = 'OK';
$resultData = '';

/*
get the parameters passed to the service
*/
$serviceCode = getCommandParam($_GET, 'service');
$licenseKeyCode = getCommandParam($_GET, 'groupcode');
$dataString = getCommandParam($_GET, 'data');

/*
decode the blowfish encrypted data string
*/
if ($dataString != '')
{
	$pos = strpos($dataString, '.');
	$strLen = substr($dataString, 0, $pos);
	$strLen = (int)$strLen;
	$dataString = substr($dataString, $pos + 1);
	
	$pos = strpos($dataString, '.');
	$iv = tpBase64Decode(substr($dataString, 0, $pos));
	$dataString = tpBase64Decode(substr($dataString, $pos + 1));

	$dataString = mcrypt_decrypt(MCRYPT_BLOWFISH, kAPISecret, $dataString, MCRYPT_MODE_CBC, $iv);
	$dataString = substr($dataString, 0, $strLen);
	
	if (kDebugMode)
	{
		error_log('debug - input data=' . $dataString. "\n");
	}
}


/*
convert the data into parameters
this overwrites the service code and license key code with values that could not have been tampered with
*/
$commandDataArray = readCommandData($dataString);

/*
retrieve the standard parameters
*/
$apiVersion = (int)getCommandParam($commandDataArray, 'version', 1);
$serviceCode = getCommandParam($commandDataArray, 'servicecode');
$licenseKeyCode = getCommandParam($commandDataArray, 'groupcode');
$uuid = getCommandParam($commandDataArray, 'uuid');
$locale = getCommandParam($commandDataArray, 'langcode');
$command = getCommandParam($commandDataArray, 'command');
$token = getCommandParam($commandDataArray, 'token', '');
$apiSource = (int)getCommandParam($commandDataArray, 'apisource', 0);
$mountPoint = getCommandParam($commandDataArray, 'mountpoint');
$currencyCode = getCommandParam($commandDataArray, 'currencycode');
$currencyISONumber = (int)getCommandParam($commandDataArray, 'currencyisonumber');


/*
if the request is from an online designer set the access control headers
*/
if ($apiSource == kAPISourceOnlineDesigner)
{
	header('Access-Control-Allow-Origin: ' . kOnlineCORSDomain);
	header('Access-Control-Allow-Methods: GET, HEAD');
	header('Access-Control-Allow-Headers: Content-Type');
}


/*
process the commands
*/

/*
first determine if we need to check the token
*/
$performTokenCheck = true;

/*
if the command is empty, a request for a token or a request for existing asset meta-data then skip the check
we skip the check when requesting asset meta-data so that we can get price updates after the token has expired
*/
if (($command == '') || ($command == 'requesttoken') || ($command == 'getinfo'))
{
	$performTokenCheck = false;
}
else
{
	/*
	if the api source is production and we are requesting the hi-res skip the check incase the token has expired or is not present
	a real integration should never expire or delete a token but in this sample we allow it as someone experimenting with this code may delete the cache files holding the tokens
	*/
	if (($apiSource == kAPISourceProduction) && ($command == 'gethires'))
	{
		$performTokenCheck = false;
	}
}

if ($performTokenCheck)
{
	$checkTokenResultArray = checkToken($token);
	if ($checkTokenResultArray['result'] != 'OK')
	{
		$result = $checkTokenResultArray['result'];
	}
}


if ($result == 'OK')
{
	switch ($command)
	{
		case 'requesttoken': // get the authentication mode and parameters
		{
			$authenticationMode = (int) getCommandParam($commandDataArray, 'authenticationmode');
			$authParam1 = getCommandParam($commandDataArray, 'authparam1');
			$authParam2 = getCommandParam($commandDataArray, 'authparam2');
			
			$ssoToken = '';
			$userID = 0;

			// ssotoken and userid are only present for online designer calls
			if ($apiSource == kAPISourceOnlineDesigner)
			{
				$ssoToken = getCommandParam($commandDataArray, 'ssotoken');
				$userID = getCommandParam($commandDataArray, 'userid');
			}

			$resultArray = requestToken($authenticationMode, $authParam1, $authParam2, $licenseKeyCode, $mountPoint, $ssoToken, $userID);
			$result = $resultArray['result'];
			
			break;
		}
		case 'checktoken': // check the authentication token
		{	
			/*
			if we are here then the token will have already been checked so there is no need to do anything more but copy the result back
			*/
			
			$resultArray = $checkTokenResultArray;
			
			break;
		}
		case 'browse': // browse for assets within the specified sub path within the mount point and asset root path
		{
			$rootPath = kAssetRootFilePath;
			
			/*
			a mount point can be used to provide different entry points into the same asset management system so add it to the path if it exists
			*/
			if ($mountPoint != '')
			{
				$rootPath = correctPath($rootPath) . $mountPoint . '/';
			}
			
			/*
			get the path we wish to browse
			*/
			$path = getCommandParam($commandDataArray, 'path');
			if ($path != '')
			{
				$rootPath = correctPath($rootPath) . $path . '/';
			}
			
			/*
			get the page index and the per page count
			*/
			$pageIndex = getCommandParam($commandDataArray, 'page', 1);
			$perPage = getCommandParam($commandDataArray, 'perpage', 50);

			/*
			scan the path	
			*/
			$scanResultArray = scanPath($rootPath, $pageIndex, $perPage, $apiVersion);
			$resultArray['itemcount'] = $scanResultArray['itemcount'];
			$resultArray['pages'] = $scanResultArray['pages'];
			$resultArray['items'] = $scanResultArray['items'];
			
			break;
		}
		case 'getthumb': // get the thumbnail asset data
		{
			/*
			get the asset id and make sure it exists
			*/
			$id = getCommandParam($commandDataArray, 'id');
			if ($id != '')
			{
				$hiResPath = findDictionaryID($id, kIDDictionaryFileTypeFile);
			}
			else
			{
				$hiResPath = '';
			}
			
			/*
			get the thumbnail data if it exists and echo it back
			in a production system we would use something like x-sendfile
			as echoing the data back is extremely inefficient
			*/
			if ($hiResPath != '')
			{
				$scanResultArray = getAssetMetaData($hiResPath, $apiVersion, $getResult);
				
				if (($getResult) && ($scanResultArray['thumbwidth'] > 0) && ($scanResultArray['thumbheight'] > 0))
				{
					$thumbnailPath = assetPathToThumbnailCachePath($hiResPath, ($apiSource == kAPISourceDesktopDesigner));
					if (file_exists($thumbnailPath))
					{
						/*
						add the content length header so that the application receiving the file knows the length of the data
						*/
						header('Content-Length: ' . filesize($thumbnailPath));
						
						/*
						if the request wasn't just for the headers echo the content back
						*/
						if ($_SERVER['REQUEST_METHOD'] != 'HEAD')
						{
							echo file_get_contents($thumbnailPath);
						}
						
						return;	
					}
				}
			}
			
			/*
			the thumbnail file could not be retrieved so return an error
			*/
			header("HTTP/1.0 404 Not Found");
			return;
			
			break;
		}
		case 'getfpo': // get the fpo asset data
		{
			/*
			get the asset id and make sure it exists
			*/
			$id = getCommandParam($commandDataArray, 'id');
			if ($id != '')
			{
				$hiResPath = findDictionaryID($id, kIDDictionaryFileTypeFile);
			}
			else
			{
				$hiResPath = '';
			}

			/*
			get the fpo data if it exists and echo it back
			in a production system we would use something like x-sendfile
			as echoing the data back is extremely inefficient
			*/
			if ($hiResPath != '')
			{
				$scanResultArray = getAssetMetaData($hiResPath, $apiVersion, $getResult);
				if ($getResult)
				{
					$fpoPath = assetPathToFPOCachePath($hiResPath, ($apiSource == kAPISourceDesktopDesigner));
					if (file_exists($fpoPath))
					{
						/*
						add the content length header so that the application receiving the file knows the length of the data
						*/
						header('Content-Length: ' . filesize($fpoPath));
						
						/*
						if the request wasn't just for the headers echo the content back
						*/
						if ($_SERVER['REQUEST_METHOD'] != 'HEAD')
						{
							echo file_get_contents($fpoPath);
						}
						
						return;
					}
				}
			}
			
			/*
			the fpo file could not be retrieved so return an error
			*/
			header("HTTP/1.0 404 Not Found");
			return;
			
			break;
		}
		case 'gethires': // get the hi-res asset data
		{
			/*
			get the asset id and make sure it exists
			*/
			$id = getCommandParam($commandDataArray, 'id');
			if ($id != '')
			{
				$hiResPath = findDictionaryID($id, kIDDictionaryFileTypeFile);
			}
			else
			{
				$hiResPath = '';
			}
			
			/*
			get the hi-res data if it exists and echo it back
			in a production system we would use something like x-sendfile
			as echoing the data back is extremely inefficient
			*/
			if ($hiResPath != '')
			{
				$scanResultArray = getAssetMetaData($hiResPath, $apiVersion, $getResult);
				if ($getResult)
				{
					/*
					before we send the data back to the requesting application check the security
					if the source is a taopix designer then we can retrieve the file if the meta-data downloadhires = 1 or there is no fpo
					if the source is production or an image server then the file can be retrieved
					*/
					if (((($apiSource == kAPISourceDesktopDesigner) || ($apiSource == kAPISourceOnlineDesigner)) && ($scanResultArray['downloadhires'] == 1)) ||
						(($scanResultArray['fpowidth'] == 0) && ($scanResultArray['fpoheight'] == 0)) ||
						(($apiSource == kAPISourceProduction) || ($apiSource == kAPISourceOnlineImageServer)))
					{
						/*
						use an encrypted file if this the desktop designer of production
						*/
						if ((($apiSource == kAPISourceDesktopDesigner) || ($apiSource == kAPISourceProduction)) && (kEncryptHiResTransmission))
						{
							$createEncryptedHiResResultArray = createEncryptedHiRes($hiResPath, kEncryptHiResMode);
							if ($createEncryptedHiResResultArray['result'])
							{
								$encryptedHiResPath = $createEncryptedHiResResultArray['encryptedfilepath'];
								
								/*
								add the content length header so that the application receiving the file knows the length of the data
								*/
								header('Content-Length: ' . filesize($encryptedHiResPath));
								
								/*
								if the request wasn't just for the headers echo the content back
								*/
								if ($_SERVER['REQUEST_METHOD'] != 'HEAD')
								{
									echo file_get_contents($encryptedHiResPath);
								}
								
								return;
							}
						}
						else
						{
							/*
							add the content length header so that the application receiving the file knows the length of the data
							*/
							header('Content-Length: ' . filesize($hiResPath));
							
							/*
							if the request wasn't just for the headers echo the content back
							*/
							if ($_SERVER['REQUEST_METHOD'] != 'HEAD')
							{
								echo file_get_contents($hiResPath);
							}
						}
						
						return;
					}
				}
			}
			
			/*
			the hi-res file could not be retrieved so return an error
			*/
			header("HTTP/1.0 404 Not Found");
			return;
			
			break;
		}
		case 'getinfo': // get the asset meta-data
		{
			$infoArray = Array();
			
			/*
			the asset id's will be a comma separated list
			*/
			$idList = getCommandParam($commandDataArray, 'id');
			$idArray = explode(',', $idList);
			
			$itemCount = count($idArray);
			for ($i = 0; $i < $itemCount; $i++)
			{
				$id = $idArray[$i];
				
				if ($id != '')
				{
					$hiResPath = findDictionaryID($id, kIDDictionaryFileTypeFile);
				}
				else
				{
					$hiResPath = '';
				}
				
				if ($hiResPath != '')
				{
					$scanResultArray = getAssetMetaData($hiResPath, $apiVersion, $getResult);
					if ($getResult)
					{
						$infoArray[] = $scanResultArray;
					}
				}
			}
			
			$resultArray['itemcount'] = count($infoArray);
			$resultArray['pages'] = 1;
			$resultArray['items'] = $infoArray;
			
			break;
		}
		case 'getassets': // get the asset meta-data for the image led workflow
		{
			$infoArray = Array();
			
			/*
			the asset id's will be a comma separated list
			*/
			$idList = getCommandParam($commandDataArray, 'id');
			$idArray = explode(',', $idList);
			
			$itemCount = count($idArray);
			for ($i = 0; $i < $itemCount; $i++)
			{
				$id = $idArray[$i];
				
				if ($id != '')
				{
					$hiResPath = findDictionaryID($id, kIDDictionaryFileTypeFile);
				}
				else
				{
					$hiResPath = '';
				}
				
				if ($hiResPath != '')
				{
					$scanResultArray = getAssetMetaData($hiResPath, $apiVersion, $getResult);
					if ($getResult)
					{
						$infoArray[] = $scanResultArray;
					}
				}
			}
			
			$resultArray['itemcount'] = count($infoArray);
			$resultArray['items'] = $infoArray;
			
			break;
		}
		case 'search': // search assets
		{
			$rootPath = kAssetRootFilePath;
			
			/*
			a mount point can be used to provide different entry points into the same asset management system so add it to the path if it exists
			*/
			if ($mountPoint != '')
			{
				$rootPath .= $mountPoint . '/';
			}
			
			/*
			get the search string
			*/
			$searchString = getCommandParam($commandDataArray, 'text', '');
			
			/*
			get the page index and the per page count
			*/
			$pageIndex = getCommandParam($commandDataArray, 'page', 1);
			$perPage = getCommandParam($commandDataArray, 'perpage', 50);

			/*
			perform the search	
			*/
			$searchResultArray = searchAssets($rootPath, $searchString, $pageIndex, $perPage, $apiVersion);
			$resultArray['itemcount'] = $searchResultArray['itemcount'];
			$resultArray['pages'] = $searchResultArray['pages'];
			$resultArray['items'] = $searchResultArray['items'];
			
			break;
		}
		default:
		{
			$result = 'INVALIDCOMMAND';
			break;
		}
	}
}

$resultArray['result'] = $result;


/*
create the data we need to return and echo it back to Taopix
*/
$resultData = json_encode($resultArray);

if (kDebugMode)
{
	error_log('debug - output data=' . $resultData. "\n");
}

if (($apiSource != kAPISourceOnlineDesigner) && (kCompressTransmission))
{
	$resultData = '1' . strlen($resultData) . '.' . gzcompress($resultData);
}
else
{
	$resultData = '0' . $resultData;
}

$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
$encryptedData = mcrypt_encrypt(MCRYPT_BLOWFISH, kAPISecret, $resultData, MCRYPT_MODE_CBC, $iv);
if ($apiSource == kAPISourceOnlineDesigner)
{
	// base64 the encrypted data if this is the online designer 
	$encryptedData = tpBase64Encode($encryptedData, $apiSource);
}
$resultData = strlen($resultData) . '.' . tpBase64Encode($iv, $apiSource) . '.' . $encryptedData . "\nEOF";


if (kDebugMode)
{
	error_log('debug - echo data len=' . strlen($resultData));
}

echo $resultData;
	
?>