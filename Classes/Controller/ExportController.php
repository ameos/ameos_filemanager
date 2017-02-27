<?php
namespace Ameos\AmeosFilemanager\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
 
class ExportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Ameos\AmeosFilemanager\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $feUserRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
     * @inject
     */
    protected $beUserRepository;

    /**
     * Homepage
     *
     * @return void
     */
    protected function indexAction()
    {
        
    }

    /**
     * Export the detailed downloaded file
     *
     * @return void
     */
    protected function exportDownloadsAction()
    {
        $where = '';
        if ($this->request->hasArgument('dateStart') && $this->request->getArgument('dateStart') != '') {
            $dateStart = explode('/', $this->request->getArgument('dateStart'));
            $i = 0;
            foreach( GeneralUtility::trimExplode('/', LocalizationUtility::translate('format', 'ameos_filemanager')) as $format) {
                switch ($format) {
                    case 'd': $day = $dateStart[$i];    break;
                    case 'm': $month = $dateStart[$i];  break;
                    case 'y': $year = $dateStart[$i];   break;
                    default:
                        return LocalizationUtility::translate('unknownFormat', 'ameos_filemanager');
                        break;
                }
                $i++;
            }
            if(!isset($day,$month,$year)) {
                return LocalizationUtility::translate('unknownFormat', 'ameos_filemanager');
            }
            $where .= 'fd.crdate > '.mktime(0,0,0,$day,$month,$year).' AND ';
        }

        if ($this->request->hasArgument('dateEnd') && $this->request->getArgument('dateEnd') != '') {
            $dateEnd = explode('/', $this->request->getArgument('dateEnd'));
            $where .= 'fd.crdate < '.mktime(23,59,59,$dateEnd[1],$dateEnd[0],$dateEnd[2]).' AND ';
        }
        $table = "exportDownloads";
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'count(*) as nb_downloads, sf.uid as uid', 
            'tx_ameosfilemanager_domain_model_filedownload fd, sys_file sf', 
            $where.'fd.file = sf.uid', 
            'fd.file'
        );
        $columns = explode(',', $this->settings['columnsExport']);
        
        $csv = $this->getCsvHeader($columns);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $csv .= $this->getCsvLine($columns,$row);
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$table.csv\";" );
        header("Content-Transfer-Encoding: binary"); 

        echo(utf8_decode($csv));                        
        exit;
    }
    
    /**
     * Return the first line of the csv file with the $colomns as columns
     *
     * @param string $columns 
     * @return string
     */
    protected function getCsvHeader($columns)
    {
        $header = '';
        foreach ($columns as $column) {
            if ($column=='title')       { $header .= '"' . LocalizationUtility::translate('export.title', 'ameos_filemanager') . '";'; }
            if ($column=='createdAt')   { $header .= '"' . LocalizationUtility::translate('export.createdAt', 'ameos_filemanager') . '";'; }
            if ($column=='updatedAt')   { $header .= '"' . LocalizationUtility::translate('export.updatedAt', 'ameos_filemanager') . '";'; }
            if ($column=='description') { $header .= '"' . LocalizationUtility::translate('export.description', 'ameos_filemanager') . '";'; }
            if ($column=='owner')       { $header .= '"' . LocalizationUtility::translate('export.owner', 'ameos_filemanager') . '";'; }
            if ($column=='size')        { $header .= '"' . LocalizationUtility::translate('export.size', 'ameos_filemanager') . '";'; }
            if ($column=='keywords')    { $header .= '"' . LocalizationUtility::translate('export.keywords', 'ameos_filemanager') . '";'; }
            if ($column=='path')        { $header .= '"' . LocalizationUtility::translate('export.path', 'ameos_filemanager') . '";'; }
            if ($column=='nbDownload')  { $header .= '"' . LocalizationUtility::translate('export.nbDownload', 'ameos_filemanager') . '";'; }
            if ($column=='extension')   { $header .= '"' . LocalizationUtility::translate('export.extension', 'ameos_filemanager') . '";'; }
        }
        $header .= "\n";
        return $header;
    }

    /**
     * Return the csv line corresponding to the $row parameter with the $colomns as columns
     *
     * @param string $columns 
     * @param array $row 
     * @return string
     */
    protected function getCsvLine($columns,$row)
    {
        $file = $this->fileRepository->findByUid($row['uid']);
        $line = '';
        if($file) {
            foreach ($columns as $column) {
                if ($column=='title')       { $line .= '"' . $file->getTitle().'";'; }
                if ($column=='createdAt')   { $line .= '"' . strftime('%d/%m/%Y',$file->getCrdate()) . '";'; }
                if ($column=='updatedAt')   { $line .= '"' . strftime('%d/%m/%Y',$file->getTstamp()) . '";'; }
                if ($column=='description') { $line .= '"' . $file->getDescription() . '";'; }
                if ($column=='owner')       { $line .= '"' . $file->getOwnerUsername() . '";'; }
                if ($column=='size')        { $line .= '"' . $file->getOriginalResource()->getSize() . '";'; }
                if ($column=='keywords')    { $line .= '"' . $file->getkeywords() . '";'; }
                if ($column=='path')        { $line .= '"' . $file->getGedPath() . '";'; }
                if ($column=='nbDownload')  { $line .= '"' . $row['nb_downloads'] . '";'; }
                if ($column=='extension')   { $line .= '"' . $file->getOriginalResource()->getExtension() . '";'; }
            }
            $line .= "\n";    
        }
        return $line;
    }
}

