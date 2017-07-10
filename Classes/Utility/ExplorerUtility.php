<?php
namespace Ameos\AmeosFilemanager\Utility;

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
 
class ExplorerUtility
{
    /**
     * return current display mode
     * @param string $availableMode
     * @param int $contentUid
     * @return string
     */
    public static function getDisplayMode($availableMode, $contentUid)
    {
        if (!is_array($availableMode)) {
            $availableMode = GeneralUtility::trimExplode(',', $availableMode);
        }
        if (count($availableMode) == 0) {
            return 'mosaic';
        }
        if (count($availableMode) == 1) {
            return $availableMode[0];
        }
        if ($GLOBALS['TSFE']->fe_user->getKey('ses', 'display_mode_' . $contentUid)) {
            return $GLOBALS['TSFE']->fe_user->getKey('ses', 'display_mode_' . $contentUid);
        }
        return 'mosaic';
    }

    /**
     * return current display mode
     * @param int $contentUid
     * @param string $mode
     * @return string
     */
    public static function updateDisplayMode($contentUid, $mode)
    {
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'display_mode_' . $contentUid, $mode);
    }
}
