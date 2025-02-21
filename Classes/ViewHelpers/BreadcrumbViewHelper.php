<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Enum\Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as ExtbaseUriBuilder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class BreadcrumbViewHelper extends AbstractViewHelper
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
    public function render()
    {
        if ($this->arguments['folder'] != null) {
            $breadcrumb = [];
            return $this->getBreadcrumb(
                $breadcrumb,
                $this->arguments['folder'],
                $this->arguments['folder'],
                $this->arguments['startFolder'],
                $this->renderingContext,
                $this->buildRenderChildrenClosure()
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
    protected function getBreadcrumb(
        $breadcrumb,
        $folder,
        $activeFolder,
        $startFolder,
        $renderingContext,
        $renderChildrenClosure
    ) {
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add('item', [
            'uid'       => $folder->getUid(),
            'title'     => $folder->getTitle(),
            'is_active' => $folder->getUid() == $activeFolder->getUid(),
        ]);
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove('item');

        if ($folder->getParent() && $folder->getUid() != $startFolder) {
            $parentOutput = $this->getBreadcrumb(
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
