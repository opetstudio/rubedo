<?php
/**
 * Rubedo -- ECM solution Copyright (c) 2012, WebTales
 * (http://www.webtales.fr/). All rights reserved. licensing@webtales.fr
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license.
 *
 * @category Rubedo
 * @package Rubedo
 * @copyright Copyright (c) 2012-2012 WebTales (http://www.webtales.fr)
 * @license http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
namespace Rubedo\Image;

use Rubedo\Services\Manager, Rubedo\Interfaces\Image\IImage;

/**
 * Image transofmration service
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Image implements IImage
{

    public function resizeImage ($fileName, $mode = null, $width = null, $height = null, $size = null)
    {
        $imgInfos = getimagesize($fileName);
        $imgWidth = $imgInfos[0];
        $imgHeight = $imgInfos[1];
        $mime = $imgInfos['mime'];
        list ($mainType, $type) = explode('/', $mime);
        
        $gdCreateClassName = 'imagecreatefrom' . $type;
        $gdReturnClassName = 'image' . $type;
        $image = $gdCreateClassName($fileName);
        
        $ratio = $imgWidth / $imgHeight;
        if ((is_null($width) || $imgWidth == $width) && (is_null($height) || ($imgHeight == $height))) { // do not transform anything : return original image
            $newImage = $image;
        } elseif ($mode == 'morph') { // transform image so that new one fit exactly the dimension with anamorphic resizing
            $width = isset($width) ? $width : $height * $ratio;
            $height = isset($height) ? $height : $width / $ratio;
            
            $newImage = imagecreatetruecolor($width, $height);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $imgWidth, $imgHeight);
        } elseif ($mode == 'boxed') { // respect ratio, tallest image which fit the box
            if (is_null($width) || is_null($height)) {
                $width = isset($width) ? $width : $height * $ratio;
                $height = isset($height) ? $height : $width / $ratio;
            } else {
                $newRatio = $width / $height;
                // which dimension should be modified
                if ($newRatio > $ratio) {
                    $width = $height * $ratio;
                } else {
                    $height = $width / $ratio;
                }
            }
            $newImage = imagecreatetruecolor($width, $height);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $imgWidth, $imgHeight);
        } elseif ($mode == 'crop') { //respect ratio but crop part which do not fit the box.
            $width = isset($width) ? $width : $imgWidth;
            $height = isset($height) ? $height : $imgHeight;
            
            $widthCoeff = $width / $imgWidth;
            $heightCoeff = $height / $imgHeight;
            $transformCoeff = max($widthCoeff, $heightCoeff);
            
            $tmpWidth = $transformCoeff * $imgWidth;
            $tmpHeight = $transformCoeff * $imgHeight;
            
            $tmpImage = imagecreatetruecolor($tmpWidth, $tmpHeight);
            imagecopyresampled($tmpImage, $image, 0, 0, 0, 0, $tmpWidth, $tmpHeight, $imgWidth, $imgHeight);
            
            if ($tmpWidth > $width) {
                $marginWidth = ($tmpWidth - $width) / 2;
            } else {
                $marginWidth = 0;
            }
            
            if ($tmpHeight > $height) {
                $marginHeight = ($tmpHeight - $height) / 2;
            } else {
                $marginHeight = 0;
            }
            
            $newImage = imagecreatetruecolor($width, $height);
            imagecopy($newImage, $tmpImage, 0, 0, $marginWidth, $marginHeight, $tmpWidth, $tmpHeight);
            imagedestroy($tmpImage);
        } else {
            throw new \Exception("unimplemented resize mode", 1);
        }
        return $newImage;
    }
}