<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Enum\DisplayMode;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class ExplorerService
{
    /**
     * return current display mode
     * @param array $availableMode
     * @param int $contentUid
     * @return string
     */
    public function getCurrentDisplayMode(array $availableModes, int $contentId): string
    {
        /** @var FrontendUserAuthentication */
        $frontendUserAuthentication = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user');
        
        $displayMode = DisplayMode::DISPLAYMODE_MOSAIC;
        if (empty($availableModes)) {
            $displayMode = DisplayMode::DISPLAYMODE_MOSAIC;
        } elseif (count($availableModes) == 1) {
            $displayMode = $availableModes[0];
        } elseif ($frontendUserAuthentication->getSession()->get('display_mode_' . $contentId)) {
            $displayMode = $frontendUserAuthentication->getSession()->get('display_mode_' . $contentId);
        }
        return $displayMode;
    }

    /**
     * return current display mode
     * @param int $contentId
     * @param string $mode
     * @return void
     */
    public function updateDisplayMode(int $contentId, string $mode): void
    {
        /** @var FrontendUserAuthentication */
        $frontendUserAuthentication = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user');
        $frontendUserAuthentication->getSession()->set('display_mode_' . $contentId, $mode);
    }
}
