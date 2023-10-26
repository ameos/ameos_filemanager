<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryService
{
    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
        
    }

    /**
     * Returns available categories
     *
     * @param array $settings
     * @return array
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
            $categories = $this->categoryRepository->findByParent(0);
        }
        return $categories;
    }
}
