<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

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

    /**
     * Renders line for folder or file
     *
     * @param Folder $folder 
     * @param int $startFolder 
     * @param string $separator 
     * @param int $contentUid 
     * @return string 
     */
    public function render($folder=null, $startFolder = null, $separator = ' / ', $contentUid = 0)
    {
    	if ($folder != null) {
            return $this->getBreadcrumb($folder, $startFolder, $separator, $contentUid);
    	}
    }

    /**
     * return breadcrumb
     * $param Ameos\AmeosFilemanager\Domain\Model\Folder $folder
     * $param int $startFolder
     * @param string $separator
     * @param int $contentUid 
     * @return string
     */ 
    public function getBreadcrumb($folder, $startFolder, $separator = ' / ', $contentUid)
    {
        $uri = $this->controllerContext->getUriBuilder()->reset()
            ->setAddQueryString(true)
            ->setArgumentsToBeExcludedFromQueryString(['id'])
            ->uriFor('index', ['folder' => $folder->getUid()]);

        $link = '<a href="' . $uri . '" data-ged-reload="1" data-ged-uid="' . $contentUid . '">' . $folder->getTitle() . '</a>';
    	
    	if ($folder->getParent() && $folder->getUid() != $startFolder) {
    		return $this->getBreadcrumb($folder->getParent(), $startFolder, $separator, $contentUid) . $separator . $link;
    	} else {
    		return $link;
    	}
    }   
}
