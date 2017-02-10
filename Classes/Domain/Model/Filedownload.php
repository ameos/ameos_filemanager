<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

class Filedownload extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {


	/**
	 * @var Tx_AmeosFilemanager_Domain_Model_File
	 */
	protected $file;
	
	/**
	 * @var int
	 */
	protected $crdate;

	/**
	 * @var TYPO3\CMS\Extbase\Domain\Model\FrontendUser
	 */
	protected $userDownload;


	public function getFile() {
		return $this->file;
	}

	/**
     * @return integer
     */
	public function getCrdate() {
		return $this->crdate;
	}

	/**
	 * Setter for file
	 *
	 * @param Tx_AmeosFilemanager_Domain_Model_File $file
	 * @return void
	 */
	public function setFile($file) {
		$this->file = $file;
	}

	/**
	 * Setter for crdate
	 *
	 * @param int $crdate
	 * @return void
	 */
	public function setCrdate($crdate) {
		$this->crdate = $crdate;
	}

	/**
	 * Setter for downloader
	 *
	 * @param TYPO3\CMS\Extbase\Domain\Model\FrontendUser $userDownload
	 * @return void
	 */
	public function setUserDownload($userDownload) {
		$this->userDownload = $userDownload;
	}
}