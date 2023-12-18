<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Folder;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Service\FolderService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SizeOfFilesViewHelper extends AbstractViewHelper
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
        $size = $this->folderService->getSizeOfFiles($this->arguments['folder']);
        $stringLength = strlen((string)$size);
        $temp = $stringLength % 3;
        $packOfThree = (int)floor($stringLength / 3);
        if ($temp != 0) {
            $newString = substr((string)$size, 0, $temp) . '.' . substr((string)$size, $temp);
        } else {
            $newString = substr((string)$size, 0, 3) . '.' . substr((string)$size, 3);
            $packOfThree--;
        }

        return round((float)$newString, 2) . ' ' . $this->getUnit($packOfThree);
    }


    /**
     * return unit
     * @param int $packOfThree
     * @return string
     */
    private function getUnit(int $packOfThree): string
    {
        $unit = '';
        switch ($packOfThree) {
            case 0:
                $unit = LocalizationUtility::translate('filesizeO', Configuration::EXTENSION_KEY);
                break;
            case 1:
                $unit = LocalizationUtility::translate('filesizeKO', Configuration::EXTENSION_KEY);
                break;
            case 2:
                $unit = LocalizationUtility::translate('filesizeMO', Configuration::EXTENSION_KEY);
                break;
            case 3:
                $unit = LocalizationUtility::translate('filesizeGO', Configuration::EXTENSION_KEY);
                break;
            case 4:
                $unit = LocalizationUtility::translate('filesizeTO', Configuration::EXTENSION_KEY);
                break;
            case 5:
                $unit = LocalizationUtility::translate('filesizePO', Configuration::EXTENSION_KEY);
                break;
            default:
                $unit = '';
                break;
        }
        return (string)$unit;
    }
}
