<?php
namespace Ameos\AmeosFilemanager\Controller\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
 
class ExportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository;

    /**
     * index action
     */
    protected function indexAction()
    {
        $folderIdentifier = GeneralUtility::_GET('id');
        $folderResource = ResourceFactory::getInstance()->retrieveFileOrFolderObject($folderIdentifier);

        $folder = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            'tx_ameosfilemanager_domain_model_folder.*',
            'tx_ameosfilemanager_domain_model_folder',
            'tx_ameosfilemanager_domain_model_folder.deleted = 0
                AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folderResource->getStorage()->getUid() . '
                AND tx_ameosfilemanager_domain_model_folder.identifier = \'' . $folderResource->getIdentifier() . '\''
        );

        $this->view->assign('folder', $folder);
    }

    /**
     * export action
     */
    protected function exportAction()
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv";');
        header('Content-Transfer-Encoding: binary');

        echo '"' . LocalizationUtility::translate('export.title', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.createdAt', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.updatedAt', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.description', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.owner', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.size', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.keywords', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.path', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.nbDownload', 'ameos_filemanager') . '";';
        echo '"' . LocalizationUtility::translate('export.extension', 'ameos_filemanager') . '"' . "\n";

        $folders = [];
        $folders[] = (int)$this->request->getArgument('folder');
        if ($this->request->hasArgument('subfolders') && $this->request->getArgument('subfolders') == 1) {
            $folder = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'tx_ameosfilemanager_domain_model_folder.*',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.uid = ' . (int)$this->request->getArgument('folder')
            );
            $subfolders = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'tx_ameosfilemanager_domain_model_folder.*',
                'tx_ameosfilemanager_domain_model_folder',
                'tx_ameosfilemanager_domain_model_folder.deleted = 0
                    AND tx_ameosfilemanager_domain_model_folder.storage = ' . $folder['storage'] . '
                    AND tx_ameosfilemanager_domain_model_folder.identifier LIKE \'' . $folder['identifier'] . '%\''
            );
            while (($subfolder = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($subfolders)) !== false) {
                $folders[] = $subfolder['uid'];
            }
        }

        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'sys_file_metadata.*,
                sys_file.name,
                sys_file.size,
                sys_file.extension,
                sys_file.identifier,
                fe_users.username',
            'sys_file_metadata
                JOIN sys_file ON sys_file.uid = sys_file_metadata.file
                LEFT JOIN fe_users ON fe_users.uid = sys_file_metadata.fe_user_id',
            'sys_file_metadata.folder_uid IN (' . implode(',', $folders) . ')'
        );

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $downloaded = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                'count(*) as nb_downloads',
                'tx_ameosfilemanager_domain_model_filedownload',
                'tx_ameosfilemanager_domain_model_filedownload.file = ' . (int)$row['file']
            );           
            echo '"' . ($row['title'] ? $row['title'] : $row['name']) .  '";';
            echo '"' . strftime('%d/%m/%Y', $row['crdate']) . '";';
            echo '"' . strftime('%d/%m/%Y', $row['tstamp']) . '";';
            echo '"' . $row['description'] . '";';
            echo '"' . $row['username'] . '";';
            echo '"' . $row['size'] . '";';
            echo '"' . $row['keywords'] . '";';
            echo '"' . $row['identifier'] . '";';
            echo '"' . (int)$downloaded['nb_downloads'] . '";';
            echo '"' . $row['extension'] . '";' . "\n";            
        }
        exit;
    }
}

