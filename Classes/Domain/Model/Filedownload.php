<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

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

class Filedownload extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var \Ameos\AmeosFilemanager\Domain\Model\File
     */
    protected $file;

    /**
     * @var int
     */
    protected $crdate;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    protected $userDownload;

    /**
     * @return \Ameos\AmeosFilemanager\Domain\Model\File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Setter for file
     *
     * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Setter for crdate
     *
     * @param int $crdate
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Setter for downloader
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $userDownload
     */
    public function setUserDownload($userDownload)
    {
        $this->userDownload = $userDownload;
    }
}
