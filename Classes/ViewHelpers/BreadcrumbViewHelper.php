<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Enum\Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class BreadcrumbViewHelper extends AbstractViewHelper
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
        $this->registerArgument('folder', Folder::class, 'Current folder', true);
        $this->registerArgument('startFolder', 'int', 'Start folder', true);
    }

    /**
     * Renders line for folder or file
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if ($arguments['folder'] != null) {
            $breadcrumb = [];
            return static::getBreadcrumb(
                $breadcrumb,
                $arguments['folder'],
                $arguments['folder'],
                $arguments['startFolder'],
                $renderingContext,
                $renderChildrenClosure
            );
        }
        return '';
    }

    /**
     * return breadcrumb
     * $param Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * $param int $startFolder
     * @param string $separator
     * @param RenderingContextInterface $renderingContext
     * @param Closure $renderChildrenClosure
     * @return string
     */
    protected static function getBreadcrumb(
        $breadcrumb,
        $folder,
        $activeFolder,
        $startFolder,
        $renderingContext,
        $renderChildrenClosure
    ) {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder->reset()
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['id'])
            ->uriFor(
                'index',
                ['folder' => $folder->getUid()],
                $renderingContext->getControllerName(),
                Configuration::EXTENSION_KEY
            );

        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add('item', [
            'uri'       => $uri,
            'title'     => $folder->getTitle(),
            'is_active' => $folder->getUid() == $activeFolder->getUid(),
        ]);
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove('item');

        if ($folder->getParent() && $folder->getUid() != $startFolder) {
            $parentOutput = static::getBreadcrumb(
                $breadcrumb,
                $folder->getParent(),
                $activeFolder,
                $startFolder,
                $renderingContext,
                $renderChildrenClosure
            );
            return $parentOutput . $output;
        }
        return $output;
    }
}
