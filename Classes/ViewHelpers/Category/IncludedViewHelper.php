<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers\Category;

use Ameos\AmeosFilemanager\Domain\Model\Category;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IncludedViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('category', Category::class, 'Category', false);
        $this->registerArgument('list', ObjectStorage::class, 'List', false);
    }

    /**
     * This method decides if the condition is TRUE or FALSE
     *
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return bool
     */
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext)
    {
        return $arguments['list']->contains($arguments['category']);
    }
}
