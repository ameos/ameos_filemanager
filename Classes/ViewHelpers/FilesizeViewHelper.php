<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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
    use CompileWithRenderStatic;

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments() 
    {
        $this->registerArgument('size', 'string', 'File Size', true);
    }

    /**
     * Renders icon of extension $type
     *
     * @param string $size 
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $stringLength = strlen($arguments['size']);
        $temp = $stringLength % 3;
        $packOfThree = floor($stringLength / 3);
        if ($temp != 0) {
            $newString = substr($arguments['size'], 0, $temp) . '.' . substr($arguments['size'], $temp);
        } else {
            $newString = substr($arguments['size'], 0, 3) . '.' . substr($arguments['size'], 3);
            $packOfThree--;
        }
        return round($newString, 2) . ' ' . static::getUnit($packOfThree);
    }

    /**
     * return unit
     * @param int $packOfThree
     * @return string
     */ 
    protected static function getUnit($packOfThree)
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
