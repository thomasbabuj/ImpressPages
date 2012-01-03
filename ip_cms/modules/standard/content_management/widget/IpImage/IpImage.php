<?php
/**
 * @package ImpressPages
 * @copyright   Copyright (C) 2011 ImpressPages LTD.
 * @license GNU/GPL, see ip_license.html
 */
namespace Modules\standard\content_management\widget;

if (!defined('CMS')) exit;

require_once(BASE_DIR.MODULE_DIR.'standard/content_management/widget.php');
require_once(BASE_DIR.LIBRARY_DIR.'php/file/functions.php');
require_once(BASE_DIR.LIBRARY_DIR.'php/image/functions.php');


class IpImage extends \Modules\standard\content_management\Widget{



    public function update($widgetId, $postData, $currentData) {
        global $parametersMod;
        $answer = '';


        $destinationDir = BASE_DIR.TMP_IMAGE_DIR;

        $newData = $currentData;
        $newData['imageWindowWidth'] = $postData['imageWindowWidth'];

        if (isset($postData['newImage']) && file_exists(BASE_DIR.$postData['newImage']) && is_file(BASE_DIR.$postData['newImage'])) {

            if (TMP_FILE_DIR.basename($postData['newImage']) != $postData['newImage']) {
                throw new \Exception("Security notice. Try to access an image (".$postData['newImage'].") from a non temporary folder.");
            }

            
            //new original image
            $newData['imageOriginal'] = \Modules\administrator\repository\Model::addFile($postData['newImage'], 'standard/content_management', $widgetId);


            $tmpBigImageName = \Library\Php\Image\Functions::resize(
            $postData['newImage'],
            $parametersMod->getValue('standard', 'content_management', 'widget_photo', 'big_width'),
            $parametersMod->getValue('standard', 'content_management', 'widget_photo', 'big_height'),
            BASE_DIR.TMP_IMAGE_DIR,
            \Library\Php\Image\Functions::CROP_TYPE_FIT,
            false,
            $parametersMod->getValue('standard', 'content_management', 'widget_photo', 'big_quality')
            );
            $newData['imageBig'] = \Modules\administrator\repository\Model::addFile(TMP_IMAGE_DIR.$tmpBigImageName, 'standard/content_management', $widgetId);
            unlink(BASE_DIR.TMP_IMAGE_DIR.$tmpBigImageName);
        }

        if (isset($postData['cropX1']) && isset($postData['cropY1']) && isset($postData['cropX2']) && isset($postData['cropY2']) && isset($postData['scale']) ) {
            //remove old file
            if(isset($currentData['imageSmall'])) {
                \Modules\administrator\repository\Model::unbindFile($currentData['imageSmall'], 'standard/content_management', $widgetId);
            }
            
            //new small image
            $ratio = ($postData['cropX2'] - $postData['cropX1']) / ($postData['cropY2'] - $postData['cropY1']);
            $requiredWidth = round($parametersMod->getValue('standard', 'content_management', 'widget_photo', 'width') * $postData['scale']);
            $requiredHeight = round($requiredWidth / $ratio);
            $tmpSmallImageName = \Library\Php\Image\Functions::crop (
            $newData['imageOriginal'],
            $destinationDir,
            $postData['cropX1'],
            $postData['cropY1'],
            $postData['cropX2'],
            $postData['cropY2'],
            $parametersMod->getValue('standard', 'content_management', 'widget_photo', 'quality'),
            $requiredWidth,
            $requiredHeight
            );
            
            $newData['imageSmall'] = \Modules\administrator\repository\Model::addFile(TMP_IMAGE_DIR.$tmpSmallImageName, 'standard/content_management', $widgetId);
            unlink(BASE_DIR.TMP_IMAGE_DIR.$tmpSmallImageName);
            
            $newData['scale'] = $postData['scale'];
            $newData['cropX1'] = $postData['cropX1'];
            $newData['cropY1'] = $postData['cropY1'];
            $newData['cropX2'] = $postData['cropX2'];
            $newData['cropY2'] = $postData['cropY2'];

        }



        if (isset($postData['title'])) {
            $newData['title'] = $postData['title'];
        }

        return $newData;
    }

    public function delete($widgetId, $data) {
        self::_deleteOneImage($data, $widgetId);
    }
    
    private function _deleteOneImage($data, $widgetId) {
        if (!is_array($data)) {
            return;
        }
        if (isset($data['imageOriginal']) && $data['imageOriginal']) {
            \Modules\administrator\repository\Model::unbindFile($data['imageOriginal'], 'standard/content_management', $widgetId);
        }
        if (isset($data['imageBig']) && $data['imageBig']) {
            \Modules\administrator\repository\Model::unbindFile($data['imageBig'], 'standard/content_management', $widgetId);
        }
        if (isset($data['imageSmall']) && $data['imageSmall']) {
            \Modules\administrator\repository\Model::unbindFile($data['imageSmall'], 'standard/content_management', $widgetId);
        }        
    }    
   



}