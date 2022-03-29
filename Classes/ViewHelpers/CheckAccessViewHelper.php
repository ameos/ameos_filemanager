<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

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

class CheckAccessViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('folder', Folder::class, 'Folder', false);
        $this->registerArgument('file', File::class, 'File', false);
        $this->registerArgument('arguments', 'array', 'Arguments.', false);
        $this->registerArgument('right', 'string', 'right.', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    public static function evaluateCondition($arguments = null)
    {
        $access = false;
        $user = ($GLOBALS['TSFE']->fe_user->user);
        if (($arguments['file'] == null && $arguments['folder'] == null) || $arguments['right'] == null) {
            $access = false;
        } elseif ($arguments['folder'] != null) {
            $access = self::getFolderAccess($arguments, $user);
        } elseif ($arguments['file'] != null) {
            $access = self::getFileAccess($arguments, $user);
        }
        return $access;
    }

    private static function getFolderAccess($arguments, $user)
    {
        if ($arguments['right'] == 'r') {
            $access = AccessUtility::userHasFolderReadAccess($user, $arguments['folder'], $arguments['arguments'])
                ? true
                : false;
        } elseif ($arguments['right'] == 'w') {
            $access = AccessUtility::userHasFolderWriteAccess($user, $arguments['folder'], $arguments['arguments'])
                ? true
                : false;
        } else {
            $access = false;
        }
        return $access;
    }

    private static function getFileAccess($arguments, $user)
    {
        if ($arguments['right'] == 'r') {
            $access = AccessUtility::userHasFileReadAccess($user, $arguments['file'], $arguments['arguments'])
                ? true
                : false;
        } elseif ($arguments['right'] == 'w') {
            $access = AccessUtility::userHasFileWriteAccess($user, $arguments['file'], $arguments['arguments'])
                ? true
                : false;
        } else {
            $access = false;
        }
        return $access;
    }
}
