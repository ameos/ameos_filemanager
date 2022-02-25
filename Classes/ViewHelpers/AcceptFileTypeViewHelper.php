<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

class AcceptFileTypeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * accept attribute for upload file
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (!isset($arguments['allowedFileTypes'])) {
            $allowedFileTypes = $renderChildrenClosure();
        }
        
        $allowedFileTypes = array_map(function ($v) {
            return '.' . $v;
        }, GeneralUtility::trimExplode(',', $allowedFileTypes));
        return implode(',', $allowedFileTypes);
    }
}
