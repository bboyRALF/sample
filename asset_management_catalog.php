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

define('kMaxThumbnailSize', 250); // max. side dimensions of the thumbnail or 0 for no thumbnail
define('kThumbnailJPEGQuality', 50); // thumbnail jpeg output quality
define('kMaxFPOSize', 600); // max. side dimensions of the fpo
define('kFPOJPEGQuality', 80); // fpo jpeg output quality


/*
****************************************************
sample file based asset management system functions
****************************************************
*/

/*
determine if the file type is valid
*/
function imageValid($pImageType)
{
	switch ($pImageType)
	{
		case IMAGETYPE_JPEG:
		{
			return true;
			break;
		}
		case IMAGETYPE_PNG:
		{
			return true;
			break;
		}
	}
	
	return false;
}


/*
calculate the thumbnail file dimensions
*/
function calcThumbnailSize($pHiResWidth, $pHiResHeight, &$pThumbnailWidth, &$pThumbnailHeight)
{
	/*
	calculate the thumbnail dimensions or use the original dimensions if they are smaller
	*/
	
	if (kMaxThumbnailSize > 0)
	{
		$ratio = min(kMaxThumbnailSize / $pHiResWidth, kMaxThumbnailSize / $pHiResHeight);
		
		if ($ratio < 1.0)
		{
			$pThumbnailWidth = (int)($pHiResWidth * $ratio);
			$pThumbnailHeight = (int)($pHiResHeight * $ratio);
		}
		else
		{
			$pThumbnailWidth = $pHiResWidth;
			$pThumbnailHeight = $pHiResHeight;
		}
	}
	else
	{
		$pThumbnailWidth = 0;
		$pThumbnailHeight = 0;
	}
}


/*
calculate the fpo file dimensions
*/
function calcFPOSize($pHiResWidth, $pHiResHeight, &$pFPOWidth, &$pFPOHeight)
{
	/*
	calculate the fpo dimensions or use the original dimensions if they are smaller
	*/
	
	$ratio = min(kMaxFPOSize / $pHiResWidth, kMaxFPOSize / $pHiResHeight);
	
	if ($ratio < 1.0)
	{
		$pFPOWidth = (int)($pHiResWidth * $ratio);
		$pFPOHeight = (int)($pHiResHeight * $ratio);
	}
	else
	{
		$pFPOWidth = $pHiResWidth;
		$pFPOHeight = $pHiResHeight;
	}
}


/*
createThumbnail
Thumbnails are optional. They can be used to send back a very low resolution rough preview to the file browser quickly before it downloads the FPO data. 
Thumbnails also do not have to be proportionally correct and do not require an alpha channel.

This function will perform the following actions:
1. Create a low resolution thumbnail image
2. Write the thumbnail to disk
3. Write an encrypted version of the thumbnail to disk

In a real integration the asset management system should at minimum create a low resolution thumbnail automatically when it catalogues an asset (if thumbnails are required).
The encryption is optional and is only for transmission purposes to the desktop designer. The online designer does not support this.
The final encryption of the downloaded data for the desktop designer is controlled by the meta-data.
If the transmission must be encrypted it is recommended that the encrypted file is written to disk to increase performance as the cached version can then be used in the future.
*/
function createThumbnail($pHiResFilePath, $pFPOFilePath, $pThumbnailFilePath, $pEncryptedThumbnailFilePath)
{
	$resultArray = Array();
	$thumbnailCreated = false;
	$width = 0;
	$height = 0;
	
	/*
	lock the meta-data file while creating the thumbnail
	this should prevent concurrency issues if multiple requests are made for the same thumbnail
	*/
	$cacheFileHandle = fopen($pFPOFilePath . '.inf', 'a+');
	if ($cacheFileHandle)
	{
		/*
		we have opened the file so now obtain an exclusive lock
		*/
		set_time_limit(180);
		flock($cacheFileHandle, LOCK_EX);
	
		/*
		check if we need to create a thumbnail
		*/
		if (kMaxThumbnailSize > 0)
		{
			if ((($pThumbnailFilePath != '') && (! file_exists($pThumbnailFilePath))) ||
				(($pEncryptedThumbnailFilePath != '') && (! file_exists($pEncryptedThumbnailFilePath))))
			{
				/*
				the thumbnail does not exist so attempt to create it
				*/
				set_time_limit(180);
				
				list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = getimagesize($pHiResFilePath);
				
				switch ($sourceImageType)
				{
					case IMAGETYPE_JPEG:
					{
						$sourceImage = imagecreatefromjpeg($pHiResFilePath);
						break;
					}
					case IMAGETYPE_PNG:
					{
						$sourceImage = imagecreatefrompng($pHiResFilePath);
						break;
					}
					default:
					{
						$sourceImage = NULL;
						break;
					}
				}
			
				if ($sourceImage)
				{
					/*
					create the thumbnail
					*/
					calcThumbnailSize($sourceImageWidth, $sourceImageHeight, $width, $height);
					
					if (($width < $sourceImageWidth) && ($height < $sourceImageHeight))
					{
						$destImage = imagecreatetruecolor($width, $height);
						imagealphablending($destImage, true);
						imagesavealpha($destImage, false);
							
						$rgbWhite = imagecolorallocate($destImage, 255, 255, 255);	
						imagefill($destImage, 0, 0, $rgbWhite);
						imagecolordeallocate($destImage, $rgbWhite);
						
						imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceImageWidth, $sourceImageHeight);
						
						ob_start();
						ob_clean();
						
						imagejpeg($destImage, NULL, kThumbnailJPEGQuality);
						
						$imageData = ob_get_clean();
						
						imagedestroy($destImage);
					}
					else
					{
						$imageData = file_get_contents($pHiResFilePath);
					}
					
					
					/*
					write the thumbnail file
					*/
					if ($pThumbnailFilePath != '')
					{
						$fp = fopen($pThumbnailFilePath, 'w');
						fwrite($fp, $imageData);
						fclose($fp);
						
						changePermissions($pThumbnailFilePath);
					}
					
					
					/*
					write the encrypted thumbnail file
					*/
					if ($pEncryptedThumbnailFilePath != '')
					{
						$fp = fopen($pEncryptedThumbnailFilePath, 'w');
						
						$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
						$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
						
						fwrite($fp, 'TAOPIXAT01' . strlen($imageData) . '.' . base64_encode($iv) . '.' . 
							mcrypt_encrypt(MCRYPT_BLOWFISH, kAPISecret, $imageData, MCRYPT_MODE_CBC, $iv));
						
						fclose($fp);
						
						changePermissions($pEncryptedThumbnailFilePath);
					}
					
					
					/*
					clean-up
					*/
					imagedestroy($sourceImage);	
					
					$thumbnailCreated = true;
				}
			}
			else
			{
				/*
				the thumbnail already exists
				*/
				$thumbnailCreated = true;
			}
		}
		else
		{
			/*
			the thumbnail size is 0 so if we have a thumbnail we need to remove it
			*/
			if (($pThumbnailFilePath != '') && (file_exists($pThumbnailFilePath)))
			{
				unlink($pThumbnailFilePath);
			}
			
			if (($pEncryptedThumbnailFilePath != '') && (file_exists($pEncryptedThumbnailFilePath)))
			{
				unlink($pEncryptedThumbnailFilePath);
			}
		}
		
		fclose($cacheFileHandle);
	}
	
	$resultArray['result'] = $thumbnailCreated;
	$resultArray['width'] = $width;
	$resultArray['height'] = $height;
	
	return $resultArray;
}


/*
createFPO
FPOs need to be in the same proportions as the high resolution image and must also contain an alpha channel if the high resolution image contains an alpha channel.

This function will perform the following actions:
1. Create a low resolution fpo image
2. Write the fpo to disk
3. Write an encrypted version of the fpo to disk

In a real integration the asset management system should at minimum create a low resolution thumbnail automatically when it catalogues an asset (if thumbnails are required).
The encryption is optional and is only for transmission purposes to the desktop designer. The online designer does not support this.
The final encryption of the downloaded data for the desktop designer is controlled by the meta-data.
If the transmission must be encrypted it is recommended that the encrypted file is written to disk to increase performance as the cached version can then be used in the future.
*/
function createFPO($pHiResFilePath, $pFPOFilePath, $pEncryptedFPOFilePath)
{
	$resultArray = Array();
	$fpoCreated = false;
	$width = 0;
	$height = 0;
	
	/*
	lock the meta-data file while creating the fpo
	this should prevent concurrency issues if multiple requests are made for the same fpo
	*/
	$cacheFileHandle = fopen($pFPOFilePath . '.inf', 'a+');
	if ($cacheFileHandle)
	{
		/*
		we have opened the file so now obtain an exclusive lock
		*/
		set_time_limit(180);
		flock($cacheFileHandle, LOCK_EX);
	
		/*
		just check to see if the fpo files already exists
		*/
		if ((($pFPOFilePath != '') && (! file_exists($pFPOFilePath))) ||
				(($pEncryptedFPOFilePath != '') && (! file_exists($pEncryptedFPOFilePath))))
		{
			set_time_limit(180);
			
			list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = getimagesize($pHiResFilePath);
			
			switch ($sourceImageType)
			{
				case IMAGETYPE_JPEG:
				{
					$sourceImage = imagecreatefromjpeg($pHiResFilePath);
					break;
				}
				case IMAGETYPE_PNG:
				{
					$sourceImage = imagecreatefrompng($pHiResFilePath);
					break;
				}
				default:
				{
					$sourceImage = NULL;
					break;
				}
			}
		
			if ($sourceImage)
			{
				/*
				create the fpo
				*/
				calcFPOSize($sourceImageWidth, $sourceImageHeight, $width, $height);
				
				if (($width < $sourceImageWidth) && ($height < $sourceImageHeight))
				{
					$destImage = imagecreatetruecolor($width, $height);
					imagealphablending($destImage, false);
					imagesavealpha($destImage, true);
						
					imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $width, $height, $sourceImageWidth, $sourceImageHeight);
					
					ob_start();
					ob_clean();
					
					switch ($sourceImageType)
					{
						case IMAGETYPE_JPEG:
						{
							imagejpeg($destImage, NULL, kFPOJPEGQuality);
							break;
						}
						case IMAGETYPE_PNG:
						{
							imagepng($destImage);
							break;
						}
					}
					
					$imageData = ob_get_clean();
					
					imagedestroy($destImage);
				}
				else
				{
					$imageData = file_get_contents($pHiResFilePath);
				}
				
				
				/*
				write the fpo file
				*/
				if ($pFPOFilePath != '')
				{
					$fp = fopen($pFPOFilePath, 'w');
					fwrite($fp, $imageData);
					fclose($fp);
					
					changePermissions($pFPOFilePath);
				}
				
				
				/*
				write the encrypted fpo file
				*/
				if ($pEncryptedFPOFilePath != '')
				{
					$fp = fopen($pEncryptedFPOFilePath, 'w');
					
					$iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC);
					$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
					
					fwrite($fp, 'TAOPIXAT01' . strlen($imageData) . '.' . base64_encode($iv) . '.' . 
						mcrypt_encrypt(MCRYPT_BLOWFISH, kAPISecret, $imageData, MCRYPT_MODE_CBC, $iv));
					
					fclose($fp);
					
					changePermissions($pEncryptedFPOFilePath);
				}
				
				
				/*
				clean-up
				*/
				imagedestroy($sourceImage);	
				
				$fpoCreated = true;
			}
		}
		else
		{
			$fpoCreated = true;
		}
		
		fclose($cacheFileHandle);
	}
	
	$resultArray['result'] = $fpoCreated;
	$resultArray['width'] = $width;
	$resultArray['height'] = $height;
	
	return $resultArray;
}


/*
cache the file properties to disk
*/
function cacheFile($pFilePath)
{
	$resultArray = Array();
	$fileCached = false;
	$id = '';
	
	if (is_file($pFilePath))
	{
		list($hiResWidth, $hiResHeight, $hiResFormat) = getimagesize($pFilePath);
		
		if (imageValid($hiResFormat))
		{
			$fileSize = filesize($pFilePath);
			$fileModifiedTime = filemtime($pFilePath);
		
			/*
			calculate the cache folder path
			*/
			$cachePath = dirname(assetPathToFPOCachePath($pFilePath, false));
			
			/*
			if the fpo cache path does not exist create it
			*/
			if (! file_exists($cachePath))
			{
				$origMask = umask(0);
				$dirResult = @mkdir($cachePath, kFileFolderPermissions, true);
				umask($origMask);
			}
			
			/*
			make sure the cache path exists
			*/
			if (file_exists($cachePath))
			{
				/*
				now check for the meta-data file
				*/
				$cacheFilePath = $cachePath . '/' . basename($pFilePath) . '.inf';
				$cacheFileHandle = fopen($cacheFilePath, 'a+b');
				if ($cacheFileHandle)
				{
					/*
					we have opened the file so now obtain an exclusive lock
					*/
					set_time_limit(180);
					flock($cacheFileHandle, LOCK_EX);
					
					
					/*
					calculate the fpo and thumbnail file paths
					*/
					$fpoPath = assetPathToFPOCachePath($pFilePath, false);
					$encryptedFPOPath = assetPathToFPOCachePath($pFilePath, true);
					
					$thumbnailPath = assetPathToThumbnailCachePath($pFilePath, false);
					$encryptedThumbnailPath = assetPathToThumbnailCachePath($pFilePath, true);
					
					
					/*
					we have an exclusive lock on the file
					if the file is empty write the meta-data
					*/
					clearstatcache();
					$cacheFileSize = filesize($cacheFilePath);
					if ($cacheFileSize > 0)
					{
						readMetaData($pFilePath, $cacheFileHandle, $cacheFileSize, $resultArray);
						
						/*
						check for a change in the hi-res file size or modified time and clear the cache if there has been
						*/
						if (($resultArray['filesize'] != $fileSize) || ($resultArray['filemodifiedtime'] != $fileModifiedTime))
						{
							$cacheFileSize = 0;
							ftruncate($cacheFileHandle, 0);
							
							if (file_exists($fpoPath))
							{
								unlink($fpoPath);	
							}
							
							if (file_exists($encryptedFPOPath))
							{
								unlink($encryptedFPOPath);	
							}
							
							if (file_exists($thumbnailPath))
							{
								unlink($thumbnailPath);	
							}
							
							if (file_exists($encryptedThumbnailPath))
							{
								unlink($encryptedThumbnailPath);	
							}
							
							$encryptedHiResPath = assetPathToEncryptedhiResCachePath($pFilePath);
							if (file_exists($encryptedHiResPath))
							{
								unlink($encryptedHiResPath);	
							}
						}
					}
					
					if ($cacheFileSize == 0)
					{
						/*
						calculate the fpo dimensions
						*/
						calcFPOSize($hiResWidth, $hiResHeight, $fpoWidth, $fpoHeight);
						if (($fpoWidth >= $hiResWidth) || ($fpoHeight >= $hiResHeight))
						{
							$fpoWidth = 0;
							$fpoHeight = 0;
						}
						
						/*
						if a thumbnail size has been provided calculate it's dimensions now
						*/
						if (kMaxThumbnailSize > 0)
						{
							calcThumbnailSize($hiResWidth, $hiResHeight, $thumbWidth, $thumbHeight);
							if (($thumbWidth >= $fpoWidth) || ($thumbHeight >= $fpoHeight))
							{
								$thumbWidth = 0;
								$thumbHeight = 0;
							}
						}
						else
						{
							$thumbWidth = 0;
							$thumbHeight = 0;
						}
						
						/*
						determine if the file has an alpha channel
						*/
						if ($hiResFormat == IMAGETYPE_PNG)
						{
							/*
							this is a rough test for the colour mode (it could fail on some files and should not be used in production)
							4 = gray + alpha
							6 = rgb + alpha
							*/
							$colourMode = ord(file_get_contents($pFilePath, NULL, NULL, 25, 1));
							if (($colourMode == 4) || ($colourMode == 6))
							{
								$fpoHasAlphaChannel = 1;								
							}
							else
							{
								$fpoHasAlphaChannel = 0;
							}
						}
						else
						{
							$fpoHasAlphaChannel = 0;
						}
						
						$id = md5(substr($pFilePath, strlen(kAssetRootFilePath))); // create the assetid from the file path relative to the root path
						$name = basename($pFilePath);

						/*
						convert the format into what taopix is expecting
						taopix can also support TIFF but PHP does not seem to support this format
						*/
						if ($hiResFormat == IMAGETYPE_PNG)
						{
							$hiResFormat = 'PNG';
						}
						else
						{
							$hiResFormat = 'JPEG';
						}

						fwrite($cacheFileHandle, $id . "\n");
						fwrite($cacheFileHandle, $name . "\n");
						fwrite($cacheFileHandle, $hiResFormat . "\n");
						fwrite($cacheFileHandle, $hiResWidth . "\n");
						fwrite($cacheFileHandle, $hiResHeight . "\n");
						fwrite($cacheFileHandle, $fileSize . "\n");
						fwrite($cacheFileHandle, $fileModifiedTime . "\n");
						fwrite($cacheFileHandle, $thumbWidth . "\n");
						fwrite($cacheFileHandle, $thumbHeight . "\n");
						fwrite($cacheFileHandle, $fpoWidth . "\n");
						fwrite($cacheFileHandle, $fpoHeight . "\n");
						fwrite($cacheFileHandle, $fpoHasAlphaChannel . "\n");
						
						$resultArray['id'] = $id;
						$resultArray['filename'] = $name;
						$resultArray['format'] = $hiResFormat;
						$resultArray['width'] = $hiResWidth;
						$resultArray['height'] = $hiResHeight;
						$resultArray['filesize'] = $fileSize;
						$resultArray['filemodifiedtime'] = $fileModifiedTime;
						$resultArray['thumbwidth'] = $thumbWidth;
						$resultArray['thumbheight'] = $thumbHeight;
						$resultArray['fpowidth'] = $fpoWidth;
						$resultArray['fpoheight'] = $fpoHeight;
						$resultArray['fpohasalphachannel'] = $fpoHasAlphaChannel;
					}
					
					fclose($cacheFileHandle);
					
					changePermissions($cacheFilePath);
					
					/*
					now update the id dictionary
					*/
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
								
								if ($itemDataArray[0] == $id)
								{
									$idExists = true;
									break;
								}
							}
						}
						
						if (! $idExists)
						{
							fwrite($dictionaryFileHandle, $id . "\t1\t" . $pFilePath . "\n");
						}
						
						fclose($dictionaryFileHandle);
						
						changePermissions($dictionaryFilePath);
						
						$fileCached = true;
					}
					
					if ($fileCached)
					{
						/*
						now create the fpo and thumbnail files
						*/
						$createFPOResultArray = createFPO($pFilePath, $fpoPath, $encryptedFPOPath);
						if ($createFPOResultArray['result'])
						{
							$createThumbnailResultArray = createThumbnail($pFilePath, $fpoPath, $thumbnailPath, $encryptedThumbnailPath);
							
							if (! $createThumbnailResultArray['result'])
							{
								$fileCached = false;
							}
						}
						else
						{
							$fileCached = false;
						}
						
						if ($fileCached)
						{
							if ((kEncryptHiResTransmission) && (kEncryptHiResWhenCataloging))
							{
								$createEncryptedHiResResultArray = createEncryptedHiRes($pFilePath, kEncryptHiResMode);
							}
						}
					}
					
					if (! $fileCached)
					{
						if (file_exists($fpoPath))
						{
							unlink($fpoPath);	
						}
						
						if (file_exists($encryptedFPOPath))
						{
							unlink($encryptedFPOPath);	
						}
						
						if (file_exists($thumbnailPath))
						{
							unlink($thumbnailPath);	
						}
						
						if (file_exists($encryptedThumbnailPath))
						{
							unlink($encryptedThumbnailPath);	
						}
					}
				}
			}
		}
	}
		
	return $resultArray;
}


/*
scan a path entry
*/
function scanEntry($pFilePath)
{
	if (file_exists($pFilePath))
	{
		if (is_file($pFilePath))
		{
			cacheFile($pFilePath);
		}
		else
		{
			$id = md5(substr($pFilePath, strlen(kAssetRootFilePath))); // create the assetid from the path relative to the root path
			findDictionaryID($id, kIDDictionaryFileTypeFolder, true, $pFilePath);
		}
	}
}


/*
scan an entire path
*/
function scanPath($pSourcePath, $pDepthString)
{
	echo $pDepthString . 'Checking Directory: ' . $pSourcePath . "\n";
	
	if (is_dir($pSourcePath))
	{
		if ($dirHandle = opendir($pSourcePath))
		{
			while (($fileName = readdir($dirHandle)) !== false)
			{
				set_time_limit(60);
				
				if ((substr($fileName, 0, 1) != '.') && (substr($fileName, -4) != '.inf'))
				{
					$filePath = correctPath($pSourcePath) . '/' . $fileName;
					
					if (is_file($filePath))
					{
						echo $pDepthString . '     ' . 'Checking File: ' . $filePath . "\n";
					}
					
					flush();
					
					scanEntry($filePath);
					
					if (is_dir($filePath))
					{
						scanPath($filePath, $pDepthString . '     ');
					}
				}					
			}

			closedir($dirHandle);
		}
	}
}


/*
****************************************************
main cataloguing entry point
****************************************************
*/


scanPath(kAssetRootFilePath, '');

echo "Finished\n";

	
?>