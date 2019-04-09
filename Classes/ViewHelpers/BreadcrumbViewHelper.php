<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

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

class BreadcrumbViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
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
        $this->registerArgument('folder', \Ameos\AmeosFilemanager\Domain\Model\Folder::class, 'Current folder', true);
        $this->registerArgument('startFolder', 'int', 'Start folder', true);
        $this->registerArgument('separator', 'string', 'separator', false, ' / ');
        $this->registerArgument('contentUid', 'int', 'Content uid', false, 0);
    }

    /**
     * Renders line for folder or file
     *
     * @return string 
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
    	if ($arguments['folder'] != null) {
            return static::getBreadcrumb(
                $arguments['folder'],
                $arguments['startFolder'],
                $arguments['separator'],
                $arguments['contentUid'],
                $renderingContext
            );
    	}
        return '';
    }

    /**
     * return breadcrumb
     * $param Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * $param int $startFolder
     * @param string $separator
     * @param int $contentUid 
     * @param RenderingContextInterface $renderingContext 
     * @return string
     */ 
    protected static function getBreadcrumb($folder, $startFolder, $separator = ' / ', $contentUid = 0, $renderingContext)
    {
        $uri = $renderingContext->getControllerContext()->getUriBuilder()->reset()
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['id'])
            ->uriFor('index', ['folder' => $folder->getUid()]);

        $link = '<a href="' . $uri . '" data-ged-reload="1" data-ged-uid="' . $contentUid . '">' . $folder->getTitle() . '</a>';
    	
    	if ($folder->getParent() && $folder->getUid() != $startFolder) {
            $output = static::getBreadcrumb(
                $folder->getParent(), 
                $startFolder, 
                $separator, 
                $contentUid,
                $renderingContext
            );
    		return $output . $separator . $link;
    	} else {
    		return $link;
    	}
    }   
}
