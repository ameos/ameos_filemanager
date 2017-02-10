<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

class Category extends \TYPO3\CMS\Extbase\Domain\Model\Category {
	public function getSubCategories() {
		$extbaseObjectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$repo = $extbaseObjectManager->get('TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository');
		return $repo->findByParent($this->getUid());
	}
}
