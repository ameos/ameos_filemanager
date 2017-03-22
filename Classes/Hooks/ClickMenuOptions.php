<?php
namespace Ameos\AmeosFilemanager\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;

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
 
class ClickMenuOptions
{

    /**
     * Add edit folder icon
     *
     * @param \TYPO3\CMS\Backend\ClickMenu\ClickMenu $parentObject Back-reference to the calling object
     * @param array $menuItems Current list of menu items
     * @param string $combinedIdentifier The combined identifier
     * @param integer $uid Id of the clicked on item
     * @return array Modified list of menu items
     */
    public function main(\TYPO3\CMS\Backend\ClickMenu\ClickMenu $parentObject, $menuItems, $combinedIdentifier, $uid)
    {
        try {
            $folder = ResourceFactory::getInstance()->retrieveFileOrFolderObject($combinedIdentifier);

            $folderRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'tx_ameosfilemanager_domain_model_folder.uid',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder->getStorage()->getUid() . '
                    AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $folder->getIdentifier() . '\''
            );
            
            if ($folderRecord) {
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $icon = $iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL);
        
                $parameters = ['edit' => ['tx_ameosfilemanager_domain_model_folder' => [$folderRecord['uid'] => 'edit']]];
                $url = $parentObject->urlRefForCM(BackendUtility::getModuleUrl('record_edit', $parameters));

                $label = $GLOBALS['LANG']->sL('LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:module.edit');

                $menuItems[] = $parentObject->linkItem(
                    '<span title="' . $label . '">' . $label . '</span>',
                    $parentObject->excludeIcon($icon),
                    $url
                );
            }
        } catch (\Exception $e) {
            
        }

        return $menuItems;
    }
}
