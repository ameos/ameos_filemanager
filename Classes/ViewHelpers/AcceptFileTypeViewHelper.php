<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class AcceptFileTypeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * accept attribute for upload file
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (!isset($arguments['allowedFileTypes'])) {
            $allowedFileTypes = $renderChildrenClosure();
        }

        $allowedFileTypes = array_map(function ($v) {
            return '.' . $v;
        }, GeneralUtility::trimExplode(',', $allowedFileTypes));
        return implode(',', $allowedFileTypes);
    }
}
