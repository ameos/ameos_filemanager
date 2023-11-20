<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Folder;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class NumberOfFilesViewHelper extends AbstractViewHelper
{
    /**
     * @param FolderService $folderService
     */
    public function __construct(private readonly FolderService $folderService)
    {
        
    }

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        $this->registerArgument('folder', Folder::class, 'Folder', true);
    }

    /**
     * Renders number of files
     *
     * @return string
     */
    public function render(): string
    {
        return (string)$this->folderService->getNumberOfFiles($this->arguments['folder']);
    }
}