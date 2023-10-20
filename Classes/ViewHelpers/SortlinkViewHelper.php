<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class SortlinkViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

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
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $currentDirection = $request->hasArgument('direction') ? $request->getArgument('direction') : 'ASC';
        $currentColumn = $request->hasArgument('sort') ? $request->getArgument('sort') : false;

        $direction = 'ASC';
        if ($currentColumn == $this->arguments['column'] && $currentDirection == 'ASC') {
            $direction = 'DESC';
        }

        $uri = $uriBuilder->reset()->uriFor(
            null,
            [
                'folder' => $this->arguments['folder'],
                'sort' => $this->arguments['column'],
                'direction' => $direction,
            ]
        );
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
