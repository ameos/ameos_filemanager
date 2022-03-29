<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
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

class FilesizeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        $this->registerArgument('size', 'string', 'File Size', true);
    }

    /**
     * Renders icon of extension $type
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $stringLength = strlen($arguments['size']);
        $temp = $stringLength % 3;
        $packOfThree = (int)floor($stringLength / 3);
        if ($temp != 0) {
            $newString = substr($arguments['size'], 0, $temp) . '.' . substr($arguments['size'], $temp);
        } else {
            $newString = substr($arguments['size'], 0, 3) . '.' . substr($arguments['size'], 3);
            $packOfThree--;
        }
        return round((float)$newString, 2) . ' ' . static::getUnit($packOfThree);
    }

    /**
     * return unit
     * @param int $packOfThree
     * @return string
     */
    protected static function getUnit($packOfThree)
    {
        $unit = '';
        switch ($packOfThree) {
            case 0:
                $unit = LocalizationUtility::translate('filesizeO', Configuration::EXTENSION_KEY);
                break;
            case 1:
                $unit = LocalizationUtility::translate('filesizeKO', Configuration::EXTENSION_KEY);
                break;
            case 2:
                $unit = LocalizationUtility::translate('filesizeMO', Configuration::EXTENSION_KEY);
                break;
            case 3:
                $unit = LocalizationUtility::translate('filesizeGO', Configuration::EXTENSION_KEY);
                break;
            case 4:
                $unit = LocalizationUtility::translate('filesizeTO', Configuration::EXTENSION_KEY);
                break;
            case 5:
                $unit = LocalizationUtility::translate('filesizePO', Configuration::EXTENSION_KEY);
                break;
            default:
                $unit = '';
                break;
        }
        return (string)$unit;
    }
}
