<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
 
class FilesizeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Renders icon of extension $type
     *
     * @param string $size 
     * @return string
     */
    public function render($size)
    {
        $stringLength = strlen($size);
        $separatorNumber = 3;
        $separatorChar = ' ';
        $temp = $stringLength % $separatorNumber;
        $packOfThree = floor($stringLength / $separatorNumber);
        if ($temp != 0) {
            $newString = substr($size, 0,$temp ).".".substr($size, $temp);
        } else {
            $newString = substr($size, 0,$separatorNumber ).".".substr($size, $separatorNumber);
            $packOfThree--;
        }
        return round($newString, 2) . ' ' . $this->getUnit($packOfThree);
    }

    /**
     * return unit
     * @param int $packOfThree
     * @return string
     */ 
    protected function getUnit($packOfThree)
    {
        switch ($packOfThree) {
            case 0:  return LocalizationUtility::translate('filesizeO',  'ameos_filemanager');  break;
            case 1:  return LocalizationUtility::translate('filesizeKO', 'ameos_filemanager'); break;
            case 2:  return LocalizationUtility::translate('filesizeMO', 'ameos_filemanager'); break;
            case 3:  return LocalizationUtility::translate('filesizeGO', 'ameos_filemanager'); break;
            case 4:  return LocalizationUtility::translate('filesizeTO', 'ameos_filemanager'); break;
            case 5:  return LocalizationUtility::translate('filesizePO', 'ameos_filemanager'); break;
            default: return ''; break;
        }
    }
}
