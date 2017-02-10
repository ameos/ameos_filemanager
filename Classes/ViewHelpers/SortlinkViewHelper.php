<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

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

class SortlinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper 
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments() 
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('target', 'string', 'Target of link', false);
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document', false);
    }
    
    /**
     * Renders sort link
     *
     * @param string $column the column
     * @return string html
     */
    public function render($column) 
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $currentDirection = $this->controllerContext->getRequest()->hasArgument('direction')
            ? $this->controllerContext->getRequest()->getArgument('direction')
            : 'ASC';
            
        $currentColumn = $this->controllerContext->getRequest()->hasArgument('sort')
            ? $this->controllerContext->getRequest()->getArgument('sort')
            : false;
            
        $direction = 'ASC';
        if ($currentColumn == $column && $currentDirection == 'ASC') {
            $direction = 'DESC';
        }
        
        $uri = $uriBuilder->reset()
            ->setAddQueryString(true)
            ->uriFor(null, ['sort' => $column, 'direction' => $direction]);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
