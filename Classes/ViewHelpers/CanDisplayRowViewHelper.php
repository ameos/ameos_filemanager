<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use Ameos\AmeosFilemanager\Utility\AccessUtility;

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
 
class CanDisplayRowViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper
{

    /**
     * Initializes arguments
     */
    public function __construct()
    {
        parent::__construct();
        $this->registerArgument('folder',     Folder::class, 'Folder', false);
        $this->registerArgument('file',       File::class, 'File', false);
        $this->registerArgument('folderRoot', 'int', 'Root Folder', false);
        $this->registerArgument('settings',   'array', 'Settings.', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    static protected function evaluateCondition($arguments = null)
    {
        if (!is_a($arguments['folder'], Folder::class) && !is_a($arguments['file'], File::class)) {
            throw new \Exception('DisplayRowViewHelper : Folder or File are required');
        }

        $hasAccess = true;
        if ($arguments['settings']['displayArchive'] != 1) {
            
            if (is_a($arguments['folder'], Folder::class)) {
                if ($arguments['folder']->getRealstatus() > 0) {
                    $hasAccess = $arguments['folder']->getRealstatus() == 1 ? true : false;
                } else {
                    $realstatus = FilemanagerUtility::updateFolderCacheStatus($arguments['folder']->toArray());
                    $hasAccess = $realstatus == 1 ? true : false;
                }
            }

            if (is_a($arguments['file'], File::class)) {
                if ($arguments['file']->getRealstatus() > 0) {
                    $hasAccess = $arguments['file']->getRealstatus() == 1 ? true : false;
                } else {                
                    $realstatus = FilemanagerUtility::updateFileCacheStatus($arguments['file']->getMeta());
                    $hasAccess = $realstatus == 1 ? true : false;
                }
            }
        }

        if ($hasAccess) {
            // check read access
            $user = ($GLOBALS['TSFE']->fe_user->user);
            if (is_a($arguments['folder'], Folder::class)) {
                $hasAccess = AccessUtility::userHasFolderReadAccess($user, $arguments['folder'], $arguments) ? true : false;
            }
            if (is_a($arguments['file'], File::class)) {
                $hasAccess = AccessUtility::userHasFileReadAccess($user, $arguments['file'], $arguments) ? true : false;
            }
        }
        
        return $hasAccess;
    }
}
