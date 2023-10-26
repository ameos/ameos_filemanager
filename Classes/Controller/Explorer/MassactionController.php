<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Configuration\Configuration;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class MassactionController extends ActionController
{
    /**
     * Index action
     */
    protected function indexAction()
    {
        if ($this->request->hasArgument('massaction')) {
            switch ($this->request->getArgument('massaction')) {
                case 'remove':
                    $this->remove();
                    break;
                case 'copy':
                    $this->copy();
                    break;
                case 'move':
                    $this->move();
                    break;
                default:
                    break;
            }
        }

        $arguments = [];
        if ($this->request->hasArgument(Configuration::FOLDER_ARGUMENT_KEY)) {
            $arguments[Configuration::FOLDER_ARGUMENT_KEY]
                = (int)$this->request->getArgument(Configuration::FOLDER_ARGUMENT_KEY);
        }
        $this->redirect(
            Configuration::INDEX_ACTION_KEY,
            Configuration::EXPLORER_CONTROLLER_KEY,
            Configuration::EXTENSION_KEY,
            $arguments
        );
    }

    /**
     * Remove
     */
    protected function remove()
    {
        if (
            $this->request->hasArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY)
            && !empty($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY))
        ) {
            foreach ($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY) as $folder) {
                FolderUtility::remove(
                    $folder,
                    $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                    $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                );
            }
        }
        if (
            $this->request->hasArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY)
            && !empty($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY))
        ) {
            foreach ($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY) as $file) {
                FileUtility::remove(
                    $file,
                    $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                    $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                );
            }
        }
        $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', Configuration::EXTENSION_KEY));
    }

    /**
     * Move
     */
    protected function move()
    {
        $targetFolder = $this->request->hasArgument(Configuration::TARGETFOLDER_ARGUMENT_KEY)
            ? $this->request->getArgument(Configuration::TARGETFOLDER_ARGUMENT_KEY)
            : false;
        if ($targetFolder) {
            if (
                $this->request->hasArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY)
                && !empty($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY))
            ) {
                foreach ($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY) as $folder) {
                    FolderUtility::move(
                        $folder,
                        $targetFolder,
                        $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                        $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                    );
                }
            }

            if (
                $this->request->hasArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY)
                && !empty($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY))
            ) {
                foreach ($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY) as $file) {
                    FileUtility::move(
                        $file,
                        $targetFolder,
                        $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                        $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                    );
                }
            }

            $this->addFlashMessage(LocalizationUtility::translate('fileMoved', Configuration::EXTENSION_KEY));
        }
    }

    /**
     * Copy
     */
    protected function copy()
    {
        $targetFolder = $this->request->hasArgument(Configuration::TARGETFOLDER_ARGUMENT_KEY)
            ? $this->request->getArgument(Configuration::TARGETFOLDER_ARGUMENT_KEY)
            : false;
        if ($targetFolder) {
            if (
                $this->request->hasArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY)
                && !empty($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY))
            ) {
                foreach ($this->request->getArgument(Configuration::SELECTEDFOLDERS_ARGUMENT_KEY) as $folder) {
                    FolderUtility::copy(
                        $folder,
                        $targetFolder,
                        $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                        $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                    );
                }
            }

            if (
                $this->request->hasArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY)
                && !empty($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY))
            ) {
                foreach ($this->request->getArgument(Configuration::SELECTEDFILES_ARGUMENT_KEY) as $file) {
                    FileUtility::copy(
                        $file,
                        $targetFolder,
                        $this->settings[Configuration::STORAGE_SETTINGS_KEY],
                        $this->settings[Configuration::START_FOLDER_SETTINGS_KEY]
                    );
                }
            }

            $this->addFlashMessage(LocalizationUtility::translate('fileCopied', Configuration::EXTENSION_KEY));
        }
    }
}
