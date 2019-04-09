<?php
namespace Ameos\AmeosFilemanager\Controller\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;

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
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
     * @inject
     */
    protected $folderRepository;

    /**
     * index action
     */
    protected function indexAction()
    {
        $folderIdentifier = GeneralUtility::_GET('id');
        $folderResource = ResourceFactory::getInstance()->retrieveFileOrFolderObject($folderIdentifier);

        $folder = $this->folderRepository->findRawByStorageAndIdentifier(
            $folderResource->getStorage()->getUid(),
            $folderResource->getIdentifier()
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
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_folder');
            $folder = $queryBuilder
                ->select('*')
                ->from('tx_ameosfilemanager_domain_model_folder', 'folder')
                ->where($queryBuilder->expr()->eq('uid', (int)$this->request->getArgument('folder')))
                ->execute()
                ->fetch();

            $subfolders = $this->folderRepository->findRawByStorageAndIdentifier(
                $folder['storage'] ,
                $folder['identifier'] . '%'
            );
            foreach ($subfolders as $subfolder) {
                $folders[] = $subfolder['uid'];
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('meta.*', 'file.size', 'file.extension', 'file.identifier', 'users.username')
            ->from('sys_file_metadata', 'meta')
            ->join('meta', 'sys_file', 'file', 'file.uid = meta.file')
            ->leftJoin('meta', 'fe_users', 'users', 'users.uid = meta.fe_user_id')
            ->where($queryBuilder->expr()->in('meta.folder_uid', $folders))
            ->execute();

        while ($row = $statement->fetch()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_ameosfilemanager_domain_model_filedownload');
            $downloaded = $queryBuilder
                ->count('uid', 'nb_downloads')
                ->from('tx_ameosfilemanager_domain_model_filedownload')
                ->where(
                    $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter((int)$row['file'], \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchColumn(0);

            echo '"' . ($row['title'] ? $row['title'] : $row['name']) .  '";';
            echo '"' . strftime('%d/%m/%Y', $row['crdate']) . '";';
            echo '"' . strftime('%d/%m/%Y', $row['tstamp']) . '";';
            echo '"' . $row['description'] . '";';
            echo '"' . $row['username'] . '";';
            echo '"' . $row['size'] . '";';
            echo '"' . $row['keywords'] . '";';
            echo '"' . $row['identifier'] . '";';
            echo '"' . (int)$downloaded . '";';
            echo '"' . $row['extension'] . '";' . "\n";     
               
        }
        exit;
    }
}

