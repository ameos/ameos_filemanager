<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\Category;
use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryService
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * attach $categories to $file
     *
     * @param array<int> $categoriesIds
     * @param File $file
     * @return void
     */
    public function attachToFile(?array $categoriesIds, File $file): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder
            ->delete('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
                $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('categories')),
                $queryBuilder->expr()->eq('uid_foreign', (int)$file->getMetaData()->offsetGet('uid'))
            )
            ->executeStatement();

        if ($categoriesIds) {
            $sorting = 1;
            foreach ($categoriesIds as $categoryId) {
                $this->connectionPool
                    ->getConnectionForTable('sys_category_record_mm')
                    ->insert('sys_category_record_mm', [
                        'uid_local' => (int)$categoryId,
                        'uid_foreign' => $file->getMetaData()->offsetGet('uid'),
                        'tablenames' => 'sys_file_metadata',
                        'fieldname' => 'categories',
                        'sorting_foreign' => $sorting,
                    ]);
                $sorting++;
            }
        }
    }

    /**
     * retrieve categories for $file
     *
     * @param File $file
     * @return iterable<Category>
     */
    public function retrieveForFile(File $file): iterable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $results = $queryBuilder
            ->select('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->like('tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
                $queryBuilder->expr()->like('fieldname', $queryBuilder->createNamedParameter('categories')),
                $queryBuilder->expr()->eq('uid_foreign', (int)$file->getMetaData()->offsetGet('uid'))
            )
            ->executeQuery();
        $categoriesIds = [];
        while ($categoryId = $results->fetchOne()) {
            $categoriesIds[] = $categoryId;
        }
        return $this->getCategories($categoriesIds);
    }

    /**
     * Returns sub categories of $parent
     *
     * @param int $parent
     * @return iterable<Category>
     */
    public function getSubCategories(int $parent): iterable
    {
        return $this->categoryRepository->findBy(['parent' => $parent]);
    }

    /**
     * Returns categories by uids
     *
     * @param array $uids
     * @return iterable<Category>
     */
    public function getCategories(array $uids): iterable
    {
        if (empty($uids)) {
            $uids = [0];
        }
        $query = $this->categoryRepository->createQuery();
        return $query
            ->matching($query->in('uid', $uids))
            ->execute();
    }

    /**
     * Returns available categories
     *
     * @param array $settings
     * @return iterable<Category>
     */
    public function getAvailableCategories(array $settings)
    {
        if ($settings['authorizedCategories']) {
            $query = $this->categoryRepository->createQuery();
            $categories = $query
                ->matching(
                    $query->in(
                        'uid',
                        GeneralUtility::trimExplode(',', $settings['authorizedCategories'])
                    )
                )
                ->execute();
        } else {
            $categories = $this->getSubCategories(0);
        }
        return $categories;
    }
}
