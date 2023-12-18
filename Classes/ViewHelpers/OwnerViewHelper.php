<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\ViewHelpers;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class OwnerViewHelper extends AbstractViewHelper
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
        $this->registerArgument('object', 'object', 'File or Folder', true);
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
        $ownerId = $arguments['object']->getFeuser();
        if ($ownerId) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $owner = $connectionPool
                ->getConnectionForTable('fe_users')
                ->select(['*'], 'fe_users', ['uid' => (int)$ownerId])
                ->fetchAssociative();
            if ($owner) {
                $templateVariableContainer = $renderingContext->getVariableProvider();
                $templateVariableContainer->add('owner', $owner);
                $output = $renderChildrenClosure();
                $templateVariableContainer->remove('owner');
                return $output;
            }
        }

        return '';
    }
}
