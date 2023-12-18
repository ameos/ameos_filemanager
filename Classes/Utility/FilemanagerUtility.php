<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FilemanagerUtility
{
    /**
     * return the image corresponding to the given extension
     * @param string $type extension of the file
     * @return string
     */
    public static function getImageIconeTagForType($type)
    {
        $iconTag = '';
        switch (strtolower($type)) {
            case 'folder':
                $iconTag = '<i class="fa fa-2x fa-folder" aria-hidden="true"></i>';
                break;
            case 'previous_folder':
                $iconTag = '<i class="fa fa-2x fa-folder" aria-hidden="true"></i>';
                break;
            case 'pdf':
                $iconTag = '<i class="fa fa-2x fa-file-pdf" aria-hidden="true"></i>';
                break;
            case 'xls':
            case 'xlsx':
            case 'ods':
                $iconTag = '<i class="fa fa-2x fa-file-excel-o" aria-hidden="true"></i>';
                break;
            case 'doc':
            case 'docx':
            case 'odt':
                $iconTag = '<i class="fa fa-2x fa-file-word-o" aria-hidden="true"></i>';
                break;
            case 'ppt':
            case 'pptx':
            case 'odp':
                $iconTag = '<i class="fa fa-2x fa-file-powerpoint-o" aria-hidden="true"></i>';
                break;
            case 'avi':
            case 'mpeg':
            case 'mp4':
            case 'mov':
            case 'flv':
            case 'youtube':
            case 'vimeo':
            case 'dailymotion':
                $iconTag = '<i class="fa fa-2x fa-file-video-o" aria-hidden="true"></i>';
                break;
            case 'jpg':
            case 'jpeg':
            case 'svg':
            case 'png':
            case 'bmp':
            case 'gif':
            case 'eps':
            case 'tiff':
                $iconTag = '<i class="fa fa-2x fa-file-image-o" aria-hidden="true"></i>';
                break;
            case 'mp3':
            case 'oga':
            case 'ogg':
            case 'midi':
                $iconTag = '<i class="fa fa-2x fa-file-audio-o" aria-hidden="true"></i>';
                break;
            default:
                $iconTag = '<i class="fa fa-2x fa-file-text-o" aria-hidden="true"></i>';
                break;
        }
        return $iconTag;
    }

    /**
     * return true if file content search is enable and tika installed
     *
     * @return bool
     */
    public static function fileContentSearchEnabled(): bool
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ameos_filemanager');
        return isset($configuration['enable_filecontent_search'])
            && (int)$configuration['enable_filecontent_search'] === 1;
    }
}
