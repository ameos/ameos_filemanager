<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Category;

use Ameos\AmeosFilemanager\Domain\Model\Category;
use Ameos\AmeosFilemanager\Service\CategoryService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class SubcategoriesViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * constructor
     * @param CategoryService $categoryService
     */
    public function __construct(private readonly CategoryService $categoryService)
    {
        
    }

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        $this->registerArgument('category', Category::class, 'Category', true);
        $this->registerArgument('as', 'string', 'as', true);
    }

    /**
     * Loop on subcategory
     *
     * @return string
     */
    public function render(): string
    {
        $output = '';
        $templateVariableContainer = $this->renderingContext->getVariableProvider();
        $subcategories = $this->categoryService->getSubCategories((int)$this->arguments['category']->getUid());

        if ($subcategories) {
            $templateVariableContainer->add($this->arguments['as'], $subcategories);
            $output .= $this->renderChildren();
            $templateVariableContainer->remove($this->arguments['as']);
        }
        return $output;
    }
}
