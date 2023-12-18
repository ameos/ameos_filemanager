<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Explorer;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Service\MassActionService;
use Ameos\AmeosFilemanager\Utility\FileUtility;
use Ameos\AmeosFilemanager\Utility\FolderUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class MassactionController extends ActionController
{
    public const ARG_TARGETFOLDER = 'targetfolder';
    public const ARG_SELECTEDFILES = 'selectedfiles';
    public const ARG_SELECTEDFOLDERS = 'selectedfolders';
    public const ARG_ACTION = 'massaction';

    /**
     * construct
     * @param MassActionService $massActionService
     */
    public function __construct(private readonly MassActionService $massActionService)
    {
    }

    /**
     * Index action
     *
     * @return ResponseInterface
     */
    protected function indexAction(): ResponseInterface
    {
        $selectedFolders = $selectedFiles = [];
        if ($this->request->hasArgument(self::ARG_SELECTEDFOLDERS)) {
            $selectedFolders = $this->request->getArgument(self::ARG_SELECTEDFOLDERS);
        }
        if ($this->request->hasArgument(self::ARG_SELECTEDFILES)) {
            $selectedFiles = $this->request->getArgument(self::ARG_SELECTEDFILES);
        }

        $targetFolder = null;
        if ($this->request->hasArgument(self::ARG_TARGETFOLDER)) {
            $targetFolder = (int)$this->request->getArgument(self::ARG_TARGETFOLDER);
        }

        if ($this->request->hasArgument(self::ARG_ACTION)) {
            switch ($this->request->getArgument(self::ARG_ACTION)) {
                case 'remove':
                    $this->massActionService->remove(
                        empty($selectedFolders) ? [] : $selectedFolders,
                        empty($selectedFiles) ? [] : $selectedFiles
                    );
                    $this->addFlashMessage(LocalizationUtility::translate('fileRemoved', Configuration::EXTENSION_KEY));
                    break;
                case 'copy':
                    $this->massActionService->copy(
                        empty($selectedFolders) ? [] : $selectedFolders,
                        empty($selectedFiles) ? [] : $selectedFiles,
                        $targetFolder
                    );
                    $this->addFlashMessage(LocalizationUtility::translate('fileCopied', Configuration::EXTENSION_KEY));
                    break;
                case 'move':
                    $this->massActionService->move(
                        empty($selectedFolders) ? [] : $selectedFolders,
                        empty($selectedFiles) ? [] : $selectedFiles,
                        $targetFolder
                    );
                    $this->addFlashMessage(LocalizationUtility::translate('fileMoved', Configuration::EXTENSION_KEY));
                    break;
                default:
                    break;
            }
        }

        // TODO : retour sur même dossier
        return $this->redirect('index', 'Explorer\\Explorer');
    }

    /*
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

    }

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


        }
    }

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


        }
    }*/
}
