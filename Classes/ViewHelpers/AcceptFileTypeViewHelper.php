<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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

class AcceptFileTypeViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * accept attribute for upload file
     *
     * @param string $allowedFileTypes
     * @return string
     */
    public function render($allowedFileTypes = null)
    {
        if (is_null($allowedFileTypes)) {
            $allowedFileTypes = $this->renderChildren();
        }
        
        $allowedFileTypes = array_map(function ($v) {
            return '.' . $v;
        }, GeneralUtility::trimExplode(',', $allowedFileTypes));
        return implode(',', $allowedFileTypes);
    }
}
