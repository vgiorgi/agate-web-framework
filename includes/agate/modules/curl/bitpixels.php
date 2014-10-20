<?php
/**
 *<b>Get thumbnails from bitpixels.com</b>
 * @author Vasile Giorgi
 * @license lgpl
 * @copyright 2013 (c) Vasile Giorgi
 * @version 1.0.0
 *
 *----------
 * INFO from bitpixels.com:
 * 1.  Use the link below to get images. Just replace www.example.com with the site you want to get the thumbnail for.
 *	http://img.bitpixels.com/getthumbnail?code=45935&size=200&url=http://www.example.com
 *
 * 2. Add this attribution link on pages which display our thumbnails. Attribution is all we ask for in return for providing this free service.
 * <a href='http://www.bitpixels.com/'>Website thumbnails provided by BitPixels</a>
 *
 * API & TOOLS: http://img.bitpixels.com/register
 */

define('ERR_MSG_BITPIXELS_INVALID_URL', 'bitpixels.com Error:Invalid url to generate thumbnail !');
define('ERR_MSG_BITPIXELS_QUOTA_EXCEEDED', 'bitpixels.com Error:Quota Exceeded !');
class aexBitPixels extends a
{
/**
 * Generate thumbnail from url using img.bitpixels.com
 * Save the image on the specified path using a generated name.
 * @param string $sUrl
 * @return boolean
 */
	private function getWsThumbnail($sUrl, $aOptions = array()) {
//debug{
		a::addDebugCall('getWsThumbnail');
//}

		$aOptions = self::applyDefault($aOptions, array(
			'curlConnectionTimeout' => 20,
			'curlLowSpeedLimit' => 1000,
			'curlLowSpeedTime' => 1,
			'curlReferer' => '',
			'curlTimeout' => 20,
			'httpUserAgent' => $_SERVER['HTTP_USER_AGENT'],
			'keyBitpixels' => '',
			'maxReload' => 10,
			'pictureName' => false,
			'pictureSize' => 200,
			'savePath' => false
		));

		if ($aOptions['keyBitpixels'] === '') {
			return false;
		}
		$ch = curl_init();
		//are you a robot?
		$sModulePath =
			$_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR
			.'includes'.DIRECTORY_SEPARATOR
			.'agate'.DIRECTORY_SEPARATOR
			.'modules'.DIRECTORY_SEPARATOR
			.'curl'.DIRECTORY_SEPARATOR
			.'bitpixels'.DIRECTORY_SEPARATOR;

//Set curl general options:
		curl_setopt_array($ch, array(
			CURLOPT_CONNECTTIMEOUT => $aOptions['curlConnectionTimeout'],
			CURLOPT_HEADER => false,
			CURLOPT_LOW_SPEED_LIMIT => $aOptions['curlLowSpeedLimit'],
			CURLOPT_LOW_SPEED_TIME => $aOptions['curlLowSpeedTime'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => $aOptions['httpUserAgent'],
			CURLOPT_TIMEOUT => $aOptions['curlTimeout'],
			CURLOPT_ENCODING => '', // handle all encodings
			CURLOPT_REFERER => $aOptions['curlReferer']
		));

//load specific bitpixel images for comparison in case of failure:
		curl_setopt ($ch, CURLOPT_URL, $sModulePath.'comming-soon.jpg');
		$sCommingSoon = curl_exec($ch);
		curl_setopt ($ch, CURLOPT_URL, $sModulePath.'invalid-url.jpg');
		$sInvalidUrl = curl_exec($ch);
		curl_setopt ($ch, CURLOPT_URL, $sModulePath.'quota-exceeded.jpg');
		$sQuotaExceeded = curl_exec($ch);


		curl_setopt ($ch, CURLOPT_URL,
			'http://img.bitpixels.com/getthumbnail'
				.'?code='.$aOptions['keyBitpixels']
				.'&size='.$aOptions['pictureSize']
				.'&url='.urlencode($sUrl));
		for ($i = 0; $i < $iMaxReload; $i++) {
//get the thumbnail:
			$thImage = curl_exec($ch);
			$thInfo = curl_getinfo($ch);
			$thInfo['size_download'] = (int) $thInfo['size_download'];
			$thInfo['filename'] = $aOptions['pictureName'];


			if ($thImage !== false && !in_array($thInfo['size_download'], array(4503, 5124, 5186))) {
//everything seems to be fine here so we stop:
				break;
			}

//is possible that image is not ready:
			if ($thInfo['size_download'] === 4503) {
				if ($thImage !== $sCommingSoon) {
					break;
				}
			}

//is possible that url is invalid:
			if ($thInfo['size_download'] === 5186) {
				if ($thImage === $sInvalidUrl) {
					self::$arDebug['error'][] = ERR_MSG_BITPIXELS_INVALID_URL;
					return false; //error
				}
				else {
					break;
				}
			}

//is possible that quota exceed:
			if ($thInfo['size_download'] === 5124) {
				if ($thImage === $sQuotaExceeded) {
					self::$arDebug['error'][] = ERR_MSG_BITPIXELS_QUOTA_EXCEEDED;
					return false; //error
				}
				else {
					break;
				}
			}
//progressive wait for bitpixels to generate the picture:
			sleep(.2*$i);
		}

		curl_close($ch);

		if ($aOptions['savePath'] !== false && $aOptions['pictureName'] !== false) {
			file_put_contents($sSavePath.$aOptions['pictureName'].'.jpg', $thImage);
		}
		return array('content' => $thImage, 'info' => $thImage);
	}
}

aexBitPixels::addStaticMethod('getWsThumbnail', 'aexDbMySql::getWsThumbnail');
?>