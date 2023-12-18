<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Enum\DisplayMode;

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
        $displayMode = DisplayMode::DISPLAYMODE_MOSAIC;
        if (empty($availableModes)) {
            $displayMode = DisplayMode::DISPLAYMODE_MOSAIC;
        } elseif (count($availableModes) == 1) {
            $displayMode = $availableModes[0];
        } elseif ($GLOBALS['TSFE']->fe_user->getKey('ses', 'display_mode_' . $contentId)) {
            $displayMode = $GLOBALS['TSFE']->fe_user->getKey('ses', 'display_mode_' . $contentId);
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
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'display_mode_' . $contentId, $mode);
    }
}
