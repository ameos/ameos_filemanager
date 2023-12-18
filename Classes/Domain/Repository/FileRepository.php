<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Repository;

use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class FileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = ['tstamp' => QueryInterface::ORDER_DESCENDING];

    /**
     * Initialization
     */
    public function initializeObject()
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * find files for a folder
     * @param Folder $folder
     * @param string $sort
     * @param string $direction
     * @return QueryResult
     */
    public function findFilesForFolder(Folder $folder, string $sort = 'sys_file.name', string $direction = 'ASC'): QueryResult
    {
        if (empty($folder)) {
            return $this->findAll();
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('sys_file.*')
            ->from('sys_file')
            ->join(
                'sys_file',
                'sys_file_metadata',
                'sys_file_metadata',
                'sys_file_metadata.file = sys_file.uid'
            )
            ->leftJoin(
                'sys_file_metadata',
                'fe_users',
                'fe_users',
                'fe_users.uid = sys_file_metadata.fe_user_id'
            )
            ->where($queryBuilder->expr()->eq('sys_file_metadata.folder_uid', $folder->getUid()));

        return $this->buildQueryWithSorting($queryBuilder, $sort, $direction)->execute();
    }

    /**
     * return files identifiers for folder recursively
     * @param string folders
     * @param int $recursiveLimit
     * @param int $currentRecursive
     */
    protected function getFilesIdentifiersRecursively($folders, $recursiveLimit = 0, $currentRecursive = 1)
    {
        $files = [];
        $folders = GeneralUtility::trimExplode(',', $folders);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $statement = $queryBuilder
            ->select('file')
            ->from('sys_file_metadata')
            ->where($queryBuilder->expr()->in('folder_uid', $folders))
            ->executeQuery();

        while ($file = $statement->fetchAssociative()) {
            $files[] = $file['file'];
        }

        if ($currentRecursive <= $recursiveLimit) {
            $currentRecursive++;

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(Configuration::TABLENAME_FOLDER);
            $statement = $queryBuilder
                ->select('uid')
                ->from(Configuration::TABLENAME_FOLDER)
                ->where($queryBuilder->expr()->in('uid_parent', $folders))
                ->executeQuery();

            $childs = [];
            while ($folder = $statement->fetchAssociative()) {
                $childs[] = $folder['uid'];
            }
            if (!empty($childs)) {
                $files = array_merge(
                    $files,
                    $this->getFilesIdentifiersRecursively(
                        implode(',', $childs),
                        $recursiveLimit,
                        $currentRecursive
                    )
                );
            }
        }
        return $files;
    }

    /**
     * Search files in $root folder and subfolder
     *
     * @param Folder $folder
     * @param string $query
     * @param string $sort
     * @param string $direction
     * @return QueryResult
     */
    public function search(
        Folder $root,
        string $query,
        string $sort = 'sys_file.name',
        string $direction = 'ASC'
    ): QueryResult {
        /** @var QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('sys_file.*')
            ->from('sys_file_metadata')
            ->join(
                'sys_file_metadata',
                'sys_file',
                'sys_file',
                'sys_file_metadata.file = sys_file.uid'
            )
            ->leftJoin(
                'sys_file_metadata',
                'sys_category_record_mm',
                'sys_category_record_mm',
                'sys_file_metadata.uid = sys_category_record_mm.uid_foreign 
                    AND sys_category_record_mm.tablenames LIKE \'sys_file_metadata\'
                    AND sys_category_record_mm.fieldname LIKE \'categories\''
            )
            ->leftJoin(
                'sys_category_record_mm',
                'sys_category',
                'sys_category',
                'sys_category_record_mm.uid_local = sys_category.uid'
            );

        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $queryBuilder->leftJoin(
                'sys_file_metadata',
                Configuration::TABLENAME_CONTENT,
                'filecontent',
                'filecontent.file = sys_file_metadata.file'
            );
        }

        $keywordContraints = [];
        $arrayKeywords = explode(' ', $query);
        foreach ($arrayKeywords as $keyword) {
            $keyword = '\'%' . $queryBuilder->escapeLikeWildcards($keyword) . '%\'';

            $keywordContraints[] = $queryBuilder->expr()->like('sys_file_metadata.title', $keyword);
            $keywordContraints[] = $queryBuilder->expr()->like('sys_file_metadata.description', $keyword);
            $keywordContraints[] = $queryBuilder->expr()->like('sys_file_metadata.keywords', $keyword);
            $keywordContraints[] = $queryBuilder->expr()->like('sys_file.name', $keyword);
            $keywordContraints[] = $queryBuilder->expr()->like('sys_category.title', $keyword);

            if (FilemanagerUtility::fileContentSearchEnabled()) {
                $keywordContraints[] = $queryBuilder->expr()->like('filecontent.content', $keyword);
            }
        }
        $queryBuilder->where(
            $queryBuilder->expr()->or(...$keywordContraints),
            $queryBuilder->expr()->like(
                'sys_file.identifier',
                '\'' . $queryBuilder->escapeLikeWildcards($root->getIdentifier()) . '%\''
            )
        );

        return $this->buildQueryWithSorting($queryBuilder, $sort, $direction)->execute();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $sort
     * @param string $direction
     */
    protected function buildQueryWithSorting(
        QueryBuilder $queryBuilder,
        string $sort = 'sys_file.name',
        string $direction = 'ASC'
    ): Query {
        $availableSorting = [
            'sys_file.name', 'sys_file.creation_date', 'sys_file.modification_date', 'sys_file.size',
            'sys_file.tstamp', 'sys_file.crdate',
            'sys_file_metadata.tstamp', 'sys_file_metadata.crdate', 'sys_file_metadata.description',
            'sys_file_metadata.title', 'sys_file_metadata.categories', 'sys_file_metadata.keywords',
            'fe_users.name', 'fe_users.username', 'fe_users.company',
        ];

        if ($sort && in_array($sort, $availableSorting)) {
            if ($direction == 'ASC' || $direction == 'DESC') {
                $queryBuilder->orderBy($sort, $direction);
            }
        }

        /** @var Query */
        $query = $this->createQuery();
        $query->statement($queryBuilder->getSQL());
        return $query;
    }
}
