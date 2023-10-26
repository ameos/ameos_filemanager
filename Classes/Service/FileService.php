<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\File;
use Ameos\AmeosFilemanager\Domain\Repository\FileRepository;
use Ameos\AmeosFilemanager\Enum\Configuration;
use Ameos\AmeosFilemanager\Utility\FilemanagerUtility;
use TYPO3\CMS\Core\Resource\File as ResourceFile;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

class FileService
{
    /**
     * @param ResourceFactory $resourceFactory
     * @param FileRepository $fileRepository
     * @param MetaDataRepository $metaDataRepository
     * @param UserService $userService
     */
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly FileRepository $fileRepository,
        private readonly MetaDataRepository $metaDataRepository,
        private readonly UserService $userService
    ) {
    }


    /**
     * load File
     *
     * @param int $identifier
     * @return ?File
     */
    public function load(int $identifier): ?File
    {
        return $this->fileRepository->findByUid($identifier) ?? null;
    }

    /**
     * return true if file is an image
     *
     * @param File $file
     * @return bool
     */
    public function isImage(File $file): bool
    {
        return $this->getOriginalFileResource($file)->getType() === ResourceFile::FILETYPE_IMAGE;
    }

    /**
     * return ResourceFile corresponding to File
     *
     * @param File $file
     * @return ResourceFile
     */
    private function getOriginalFileResource(File $file): ResourceFile
    {
        return $this->resourceFactory->getFileObject($file->getUid());
    }

    /**
     * remove file
     *
     * @param File $file
     * @return bool
     */
    public function remove(File $file): bool
    {
        $file->getOriginalResource()->getStorage()->deleteFile($file->getOriginalResource());
        return true;
    }

    /**
     * update a file from a request
     *
     * @param File $file
     * @param RequestInterface $request
     * @param array $settings
     * @return File
     */
    public function update(File $file, RequestInterface $request, array $settings): File
    {
        $properties = $this->populatePropertiesFromRequest($request, $settings);
        // $file->setCategories($arguments['categories']); // TODO V12
        $this->metaDataRepository->update($file->getUid(), $properties);

        $this->indexContent($file);

        return $file;
    }

    /**
     * populate file from request
     *
     * @param RequestInterface $request
     * @param array $settings
     * @return array
     */
    public function populatePropertiesFromRequest(RequestInterface $request, array $settings): array
    {
        $properties = [];
        $properties['title'] = $request->getArgument('title');
        $properties['description'] = $request->getArgument('description');
        $properties['keywords'] = $request->getArgument('keywords');
        if ($request->hasArgument('fe_group_read')) {
            $properties['fe_group_read'] = implode(',', $request->getArgument('fe_group_read'));
        }
        if ($request->hasArgument('fe_group_write')) {
            $properties['fe_group_write'] = implode(',', $request->getArgument('fe_group_write'));
        }

        $properties['owner_has_read_access'] = 
            isset($settings['newFile']['owner_has_read_access'])
                ? $settings['newFile']['owner_has_read_access']
                : 1;
        
        $properties['owner_has_write_access'] =
            isset($settings['newFile']['owner_has_write_access'])
                ? $settings['newFile']['owner_has_write_access']
                : 1;

        return $properties;
    }

    /**
     * index file content
     * 
     * @param File $file
     * @return void
     */
    private function indexContent($file): void
    {
        if (FilemanagerUtility::fileContentSearchEnabled()) {
            $textExtractorRegistry = GeneralUtility::makeInstance(TextExtractorRegistry::class);
            try {
                $originalResource = $this->getOriginalFileResource($file);
                $textExtractor = $textExtractorRegistry->getTextExtractor($originalResource);
                if (!is_null($textExtractor)) {
                    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                    $connectionPool->getConnectionForTable(Configuration::TABLENAME_CONTENT)
                        ->insert(
                            Configuration::TABLENAME_CONTENT,
                            [
                                'file' => $file->getUid(),
                                'content' => $textExtractor->extractText($originalResource),
                            ]
                        );
                }
            } catch (\Exception $e) {
                //
            }
        }
    }
}
