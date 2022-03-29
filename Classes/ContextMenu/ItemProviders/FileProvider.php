<?php

namespace Ameos\AmeosFilemanager\ContextMenu\ItemProviders;

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
    private const EDIT_FOLDER_KEY = 'editFolder';

    /**
     * Extends constructor
     *
     * @param string $table      TableName
     * @param string $identifier Identifier
     * @param string $context    Context
     */
    public function __construct(string $table, string $identifier, string $context = '')
    {
        $this->itemsConfiguration[self::EDIT_FOLDER_KEY] = [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => self::EDIT_FOLDER_KEY,
        ];
        parent::__construct($table, $identifier, $context);
    }

    /**
     * Checks whether certain item can be rendered
     * (e.g. check for disabled items or permissions)
     *
     * @param string $itemName ItemName
     * @param string $type     Type
     *
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if ($itemName === self::EDIT_FOLDER_KEY) {
            return true;
        }
        return parent::canRender($itemName, $type);
    }

    /**
     * Returns additional attributes
     *
     * @param string $itemName Name of the item
     *
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        if ($itemName === self::EDIT_FOLDER_KEY) {
            return ['data-callback-module' => 'TYPO3/CMS/AmeosFilemanager/ContextMenuActions'];
        }
        return parent::getAdditionalAttributes($itemName);
    }
}
