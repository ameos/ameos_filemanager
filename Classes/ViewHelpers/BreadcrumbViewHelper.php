<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Ameos\AmeosFilemanager\Domain\Model\Folder;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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

class BreadcrumbViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Arguments initialization
     *
     * @return void
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
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
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
    protected static function getBreadcrumb($breadcrumb = [], $folder, $activeFolder, $startFolder, $renderingContext, $renderChildrenClosure)
    {
        $uri = $renderingContext->getControllerContext()->getUriBuilder()->reset()
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['id'])
            ->uriFor('index', ['folder' => $folder->getUid()]);


        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add('item', [
            'uri'       => $uri,
            'title'     => $folder->getTitle(),
            'is_active' => $folder->getUid() == $activeFolder->getUid()
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
