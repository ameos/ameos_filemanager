<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Domain\Model;

use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;

class Category extends \TYPO3\CMS\Extbase\Domain\Model\Category
{
    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @param CategoryRepository $categoryRepository */
    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * return sub categories
     */
    public function getSubCategories()
    {
        return $this->categoryRepository->findByParent($this->getUid());
    }
}
