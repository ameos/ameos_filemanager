<?php
namespace Ameos\AmeosFilemanager\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
 
class PluginPreview implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{
    /**
     * @var array
     */
    protected $row = [];

    /**
     * @var array
     */
    protected $flexFormData;

    /**
     * @var string
     */
    protected $templatePathAndFile = 'EXT:ameos_filemanager/Resources/Private/Templates/Hooks/PluginPreview.html';

    /**
     * Preprocesses the preview rendering of a content element
     *
     * @param PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionality
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     * @return void
     */
    public function preProcess(PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row) {
        if ($row['CType'] === 'list' && ($row['list_type'] == 'ameosfilemanager_fe_filemanager' || $row['list_type'] == 'ameosfilemanager_fe_filemanager_flat')) {
            $this->initialize($row);

            $drawItem = false;

            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);

            $llprefix = 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:';
            switch ($row['list_type']) {
                case 'ameosfilemanager_fe_filemanager':
                    $title = LocalizationUtility::translate($llprefix . 'plugin.fe_filemanager.title', 'AmeosFilemanager');
                    break;
                case 'ameosfilemanager_fe_filemanager_flat': 
                    $title = LocalizationUtility::translate($llprefix . 'plugin.fe_filemanager_flat.title', 'AmeosFilemanager');
                    break;
            }
            $headerContent = '<strong><a href="' . $url . '">'  . $title . '</a></strong><br/>';

            $folder = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'tx_ameosfilemanager_domain_model_folder.*',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$this->flexFormData['settings']['startFolder']
            );
            $storage = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'sys_file_storage.*',
                'sys_file_storage',
                'sys_file_storage.uid = ' . (int)$this->flexFormData['settings']['storage']
            );

            $standaloneView = GeneralUtility::makeInstance(ObjectManager::class)->get(StandaloneView::class);
            $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->templatePathAndFile));
            $standaloneView->assignMultiple([
                'row'          => $row,
                'flexFormData' => $this->flexFormData,
                'folder'       => $folder,
                'storage'      => $storage,
            ]);
            $itemContent = $standaloneView->render();            
        }
    }

    /**
     * @param array $row
     * @return void
     */
    protected function initialize(array $row)
    {
        $this->row = $row;

        /** @var FlexFormService $flexFormService */
        $flexFormService = GeneralUtility::makeInstance(ObjectManager::class)->get(FlexFormService::class);
        $this->flexFormData = $flexFormService->convertFlexFormContentToArray($this->row['pi_flexform']);
    }
}
