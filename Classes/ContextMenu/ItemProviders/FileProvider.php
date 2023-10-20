<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ContextMenu\ItemProviders;

class FileProvider extends \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider
{
    private const EDIT_FOLDER_KEY = 'editFolder';

    /**
     * Extends constructor
     */
    public function __construct()
    {
        $this->itemsConfiguration[self::EDIT_FOLDER_KEY] = [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => self::EDIT_FOLDER_KEY,
        ];
        parent::__construct();
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
