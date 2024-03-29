<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Service;

use Ameos\AmeosFilemanager\Domain\Model\Folder;
use Ameos\AmeosFilemanager\Domain\Repository\FolderRepository;

class TreeService
{
    /**
     * @param FolderRepository $folderRepository
     */
    public function __construct(private readonly FolderRepository $folderRepository)
    {
    }

    /**
     * flatten tree
     *
     * @param array $tree
     * @param string $prefix
     * @return array
     */
    public function flatten(array $tree, string $prefix = ''): array
    {
        $flatten = [];
        foreach ($tree as $treeItem) {
            /** @var Folder */
            $folder = $treeItem['folder'];
            $flatten[$folder->getUid()] = ($prefix == '' ? '' : $prefix . ' ') . $folder->getTitle();

            if (isset($treeItem['children']) && !empty($treeItem['children'])) {
                $flatChildren = $this->flatten($treeItem['children'], $prefix . '--');
                foreach ($flatChildren as $identifier => $label) {
                    $flatten[$identifier] = $label;
                }
            }
        }
        return $flatten;
    }

    /**
     * return folder tree (warning, recursive method)
     *
     * @param array<Folder> $folders
     * @return array
     */
    public function getFoldersTree(array $folders): array
    {
        $tree = [];

        foreach ($folders as $folder) {
            $item = ['folder' => $folder];

            $children = $this->folderRepository->findSubFolders($folder)->toArray();
            if (!empty($children)) {
                $item['children'] = $this->getFoldersTree($children);
            }

            $tree[] = $item;
        }

        return $tree;
    }

    /**
     * return folder children
     *
     * @param array<Folder> $folders
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getFoldersChildren(
        array $folders,
        string $sort = 'sys_file.name',
        string $direction = 'ASC'
    ): array {
        $children = [];

        foreach ($folders as $folder) {
            $children = array_merge(
                $children,
                $this->folderRepository->findSubFolders($folder, $sort, $direction)->toArray()
            );
        }

        return $children;
    }
}
