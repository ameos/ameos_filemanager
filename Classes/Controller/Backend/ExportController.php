<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Controller\Backend;

use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;
use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Service\FolderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[Controller]
class ExportController extends ActionController
{
    protected const ARG_FOLDER = 'folder';
    protected const ARG_SUBFOLDER = 'subfolders';
    protected const ARG_STARTTIME = 'start';
    protected const ARG_ENDTIME = 'end';

    /**
     * @param ResourceFactory $resourceFactory
     * @param FolderService $folderService
     * @param FolderRepository $folderRepository
     * @param ConnectionPool $connectionPool
     * @param ModuleTemplateFactory $moduleTemplateFactory
     */
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FolderService $folderService,
        private readonly FolderRepository $folderRepository,
        private readonly ConnectionPool $connectionPool,
        private readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $this->exportAction($request);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Module d\'export / import de pages');
        return $this->indexAction($request, $moduleTemplate);
    }

    /**
     * Index action
     *
     * @param ServerRequestInterface $request
     * @param ModuleTemplate $view
     * @return ResponseInterface
     */
    protected function indexAction(ServerRequestInterface $request, ModuleTemplate $view): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['id'])) {
            $folderResource = $this->resourceFactory->retrieveFileOrFolderObject($queryParams['id']);
            $folder = $this->folderService->loadByResourceFolder($folderResource);
            $view->assign('folder', $folder);
        }

        return $view->renderResponse('Backend/Export/Index');
    }

    /**
     * Export action
     *
     * @param ServerRequestInterface $request
     */
    protected function exportAction(ServerRequestInterface $request)
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv";');
        header('Content-Transfer-Encoding: binary');

        echo '"' . LocalizationUtility::translate('export.filename', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.createdAt', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.updatedAt', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.description', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.owner', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.size', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.keywords', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.path', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.nbDownload', Configuration::EXTENSION_KEY) . '";';
        echo '"' . LocalizationUtility::translate('export.extension', Configuration::EXTENSION_KEY) . '"' . "\n";

        $body = $request->getParsedBody();

        $folders = [];
        $folders[] = (int)$body[self::ARG_FOLDER];

        if (isset($body[self::ARG_SUBFOLDER]) && (int)$body[self::ARG_SUBFOLDER] === 1) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);
            $folder = $queryBuilder
                ->select('*')
                ->from(Configuration::TABLENAME_FOLDER)
                ->where($queryBuilder->expr()->eq('uid', (int)$body[self::ARG_FOLDER]))
                ->executeQuery()
                ->fetchAssociative();

            $subfolders = $this->folderRepository->findRawByStorageAndIdentifier(
                $folder['storage'],
                $folder['identifier'] . '%'
            );
            if ($subfolders) {
                foreach ($subfolders as $subfolder) {
                    $folders[] = $subfolder['uid'];
                }
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Configuration::TABLENAME_FILEMETADATA);
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('meta.*', 'file.name', 'file.size', 'file.extension', 'file.identifier', 'users.username')
            ->from(Configuration::TABLENAME_FILEMETADATA, 'meta')
            ->join('meta', 'sys_file', 'file', 'file.uid = meta.file')
            ->leftJoin('meta', 'fe_users', 'users', 'users.uid = meta.fe_user_id')
            ->where($queryBuilder->expr()->in('meta.folder_uid', $folders))
            ->executeQuery();

        $startDate = $endDate = null;
        if (isset($body[self::ARG_STARTTIME]) && preg_match('/\d{2}\.\d{2}\.\d{4}/i', $body[self::ARG_STARTTIME])) {
            $startDate = \DateTime::createFromFormat('d.m.Y H:i:s', $body[self::ARG_STARTTIME] . ' 00:00:00');
        }
        if (isset($body[self::ARG_ENDTIME]) && preg_match('/\d{2}\.\d{2}\.\d{4}/i', $body[self::ARG_ENDTIME])) {
            $endDate = \DateTime::createFromFormat('d.m.Y H:i:s', $body[self::ARG_ENDTIME] . ' 23:59:59');
        }

        while ($row = $statement->fetchAssociative()) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable(Configuration::TABLENAME_DOWNLOAD);
            $constraints = [$queryBuilder->expr()->eq(
                'file',
                $queryBuilder->createNamedParameter((int)$row['file'], \PDO::PARAM_INT)
            )];
            if ($startDate) {
                $constraints[] = $queryBuilder->expr()->gte(
                    'crdate',
                    $queryBuilder->createNamedParameter((int)$startDate->getTimestamp(), \PDO::PARAM_INT)
                );
            }
            if ($endDate) {
                $constraints[] = $queryBuilder->expr()->lte(
                    'crdate',
                    $queryBuilder->createNamedParameter((int)$endDate->getTimestamp(), \PDO::PARAM_INT)
                );
            }
            $downloaded = $queryBuilder
                ->count('uid', 'nb_downloads')
                ->from(Configuration::TABLENAME_DOWNLOAD)
                ->where(...$constraints)
                ->executeQuery()
                ->fetchOne();

            echo '"' . ($row['title'] ? $row['title'] : $row['name']) . '";';
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
