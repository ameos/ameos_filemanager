<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class IconViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Arguments initialization
     */
    public function initializeArguments()
    {
        $this->registerArgument('type', 'string', 'File type', true);
    }

    /**
     * Renders icon of extension $type
     *
     * @param string $type
     * @param string $iconFolder
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        return FilemanagerUtility::getImageIconeTagForType($arguments['type']);
    }
}
