<?php
namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
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

class SortlinkViewHelper extends AbstractTagBasedViewHelper 
{
    /**
     * @var string
     */
    protected $tagName = 'a';
    
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
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('column', 'string', 'Column', true);
        $this->registerArgument('folder', 'string', 'Folder', false, 0);
    }
    
    /**
     * Renders sort link
     *
     * @return string html
     */
    public function render() 
    {
        $controllerContext = $this->renderingContext->getControllerContext();
        $uriBuilder = $controllerContext->getUriBuilder();
        $currentDirection = $controllerContext->getRequest()->hasArgument('direction')
            ? $controllerContext->getRequest()->getArgument('direction')
            : 'ASC';
            
        $currentColumn = $controllerContext->getRequest()->hasArgument('sort')
            ? $controllerContext->getRequest()->getArgument('sort')
            : false;
            
        $direction = 'ASC';
        if ($currentColumn == $this->arguments['column'] && $currentDirection == 'ASC') {
            $direction = 'DESC';
        }

        $uri = $uriBuilder->reset()->uriFor(
            null, 
            [
                'folder' => $this->arguments['folder'],
                'sort' => $this->arguments['column'],
                'direction' => $direction
            ]
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
