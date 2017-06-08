<?php
namespace Ameos\AmeosFilemanager\ContextMenu\ItemProviders;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
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

class FileProvider extends \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider
{
    /**
     * Extends constructor
     *
     * @param string $table
     * @param string $identifier
     * @param string $context
     */
    public function __construct(string $table, string $identifier, string $context = '')
    {
        $this->itemsConfiguration['editFolder'] = [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'editFolder'
        ];
        parent::__construct($table, $identifier, $context);
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if ($itemName == 'editFolder') {
            return true;
        }
        return parent::canRender($itemName, $type);
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        if ($itemName == 'editFolder') {
            return [
                'data-callback-module' => 'TYPO3/CMS/AmeosFilemanager/ContextMenuActions'
            ];
        }
        return parent::getAdditionalAttributes($itemName);
    }
}
