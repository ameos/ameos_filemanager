<?php
namespace Ameos\AmeosFilemanager\Controller\Explorer;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;

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
 
class MassactionController extends AbstractController
{

    /**
     * index action
     */
    protected function indexAction()
    {
        if ($this->request->hasArgument('massaction')) {
            switch ($this->request->getArgument('massaction')) {
                case 'remove': $this->remove(); break;
                case 'copy':   $this->copy();   break;
                case 'move':   $this->move();   break;
            }
        }

        $this->redirect('index', 'Explorer\\Explorer');
    }

    /**
     * remove
     */
    protected function remove()
    {
        if ($this->request->hasArgument('selectedfolders') && !empty($this->request->getArgument('selectedfolders'))) {
            foreach ($this->request->getArgument('selectedfolders') as $folder) {
                FolderUtility::remove($folder, $this->settings['storage'], $this->settings['startFolder']);
            }
        }
        if ($this->request->hasArgument('selectedfiles') && !empty($this->request->getArgument('selectedfiles'))) {
            foreach ($this->request->getArgument('selectedfiles') as $file) {
                FileUtility::remove($file, $this->settings['storage'], $this->settings['startFolder']);
            }
        }
        $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', 'AmeosFilemanager'));
    }

    /**
     * move
     */
    protected function move()
    {
        $targetFolder = $this->request->hasArgument('targetfolder') ? $this->request->getArgument('targetfolder') : false;
        if ($targetFolder) {
            if ($this->request->hasArgument('selectedfolders') && !empty($this->request->getArgument('selectedfolders'))) {
                foreach ($this->request->getArgument('selectedfolders') as $folder) {
                    FolderUtility::move($folder, $targetFolder, $this->settings['storage'], $this->settings['startFolder']);
                }
            }
            
            if ($this->request->hasArgument('selectedfiles') && !empty($this->request->getArgument('selectedfiles'))) {
                foreach ($this->request->getArgument('selectedfiles') as $file) {
                    FileUtility::move($file, $targetFolder, $this->settings['storage'], $this->settings['startFolder']);                    
                }
            }
            
            $this->addFlashMessage(LocalizationUtility::translate('fileMoved', 'AmeosFilemanager'));
        }
    }

    /**
     * copy
     */
    protected function copy()
    {
        $targetFolder = $this->request->hasArgument('targetfolder') ? $this->request->getArgument('targetfolder') : false;
        if ($targetFolder) {
            if ($this->request->hasArgument('selectedfolders') && !empty($this->request->getArgument('selectedfolders'))) {
                foreach ($this->request->getArgument('selectedfolders') as $folder) {
                    FolderUtility::copy($folder, $targetFolder, $this->settings['storage'], $this->settings['startFolder']);
                }
            }
            
            if ($this->request->hasArgument('selectedfiles') && !empty($this->request->getArgument('selectedfiles'))) {
                foreach ($this->request->getArgument('selectedfiles') as $file) {
                    FileUtility::copy($file, $targetFolder, $this->settings['storage'], $this->settings['startFolder']);                    
                }
            }
            
            $this->addFlashMessage(LocalizationUtility::translate('fileCopied', 'AmeosFilemanager'));
        }        
    }    
}
