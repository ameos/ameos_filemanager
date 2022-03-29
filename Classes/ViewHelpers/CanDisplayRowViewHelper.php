<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Utility\AccessUtility;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

class CanDisplayRowViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('folder', Folder::class, 'Folder', false);
        $this->registerArgument('file', File::class, 'File', false);
        $this->registerArgument('folderRoot', 'int', 'Root Folder', false);
        $this->registerArgument('settings', 'array', 'Settings.', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    public static function evaluateCondition($arguments = null)
    {
        if (!is_a($arguments['folder'], Folder::class) && !is_a($arguments['file'], File::class)) {
            throw new \Ameos\AmeosFilemanager\Exception\MissingArgumentException(
                'DisplayRowViewHelper : Folder or File are required'
            );
        }

        // check read access
        $userHasAccess = false;
        $user = ($GLOBALS['TSFE']->fe_user->user);
        if (is_a($arguments['folder'], Folder::class)) {
            $userHasAccess = AccessUtility::userHasFolderReadAccess($user, $arguments['folder'], $arguments)
                ? true
                : false;
            if (!empty($arguments['folderRoot'])) {
                $folderRoot = GeneralUtility::makeInstance(FolderRepository::class)
                    ->findByUid($arguments['folderRoot']);
                $userHasAccess = $userHasAccess
                    && !FileManagerUtility::hasTooMuchRecursion(
                        $folderRoot,
                        $arguments['folder'],
                        $arguments['settings']['recursion']
                    );
            }
        }
        if (is_a($arguments['file'], File::class)) {
            $userHasAccess = AccessUtility::userHasFileReadAccess($user, $arguments['file'], $arguments) ? true : false;
        }
        return $userHasAccess;
    }
}
