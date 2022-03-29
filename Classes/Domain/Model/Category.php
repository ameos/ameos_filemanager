<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

use Ameos\AmeosFilemanager\Domain\Repository\CategoryRepository;

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
