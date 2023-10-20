<?php

declare(strict_types=1);

namespace Ameos\AmeosFilemanager\Command;

use Ameos\AmeosFilemanager\Service\IndexationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexCommand extends Command
{
    private const STORAGE_OPTION_KEY = 'storage';

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Index new directory from the command line.')
            ->setHelp(
                'Call it like this:
                typo3/sysext/core/bin/typo3 filemanager:index --storage=1'
            )
            ->addOption(
                self::STORAGE_OPTION_KEY,
                's',
                InputOption::VALUE_REQUIRED,
                'UID of storage'
            );
    }

    /**
     * Execute scheduler tasks
     *
     * @param InputInterface  $input  InputInterfaceObject
     * @param OutputInterface $output OutputInterfaceObject
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        if (
            (bool)$input->hasOption(self::STORAGE_OPTION_KEY)
            && (int)$input->getOption(self::STORAGE_OPTION_KEY) > 0
        ) {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)
                ->getStorageObject((int)$input->getOption(self::STORAGE_OPTION_KEY));
        } else {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();
        }

        if ($storage) {
            GeneralUtility::makeInstance(IndexationService::class)->run($storage);
            $io->success(sprintf('Indexation of %s finished.', $storage->getName()));
        } else {
            $io->error('No storage found.');
        }

        return 0;
    }
}
