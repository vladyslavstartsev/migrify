<?php

declare(strict_types=1);

namespace Migrify\EasyCI\Command;

use Migrify\EasyCI\Finder\SrcTestsDirectoriesFinder;
use Migrify\EasyCI\Sonar\PathsFactory;
use Migrify\EasyCI\ValueObject\SrcAndTestsDirectories;
use Nette\Utils\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\SmartFileSystem\SmartFileSystem;

final class GenerateSonarProjectCommand extends Command
{
    /**
     * @var string[]
     */
    private const POSSIBLE_DIRECTORIES = ['src', 'tests', 'packages', 'rules'];

    /**
     * @var string
     */
    private const SONAR_PROJECT_PROPERTIES = 'sonar-project.properties';

    /**
     * @var SmartFileSystem
     */
    private $smartFileSystem;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var SrcTestsDirectoriesFinder
     */
    private $srcTestsDirectoriesFinder;

    /**
     * @var PathsFactory
     */
    private $pathsFactory;

    public function __construct(
        SymfonyStyle $symfonyStyle,
        SmartFileSystem $smartFileSystem,
        SrcTestsDirectoriesFinder $srcTestsDirectoriesFinder,
        PathsFactory $pathsFactory
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->smartFileSystem = $smartFileSystem;
        $this->srcTestsDirectoriesFinder = $srcTestsDirectoriesFinder;
        $this->pathsFactory = $pathsFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));

        $description = sprintf('Generate "%s" path', self::SONAR_PROJECT_PROPERTIES);
        $this->setDescription($description);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $srcAndTestsDirectories = $this->srcTestsDirectoriesFinder->findSrcAndTestsDirectories(
            self::POSSIBLE_DIRECTORIES
        );

        if ($srcAndTestsDirectories === null) {
            $warning = sprintf(
                'No "src"/"tests" directories found in "%s" paths',
                implode('", ', self::POSSIBLE_DIRECTORIES)
            );
            $this->symfonyStyle->warning($warning);

            return ShellCode::SUCCESS;
        }

        $filePath = getcwd() . '/' . self::SONAR_PROJECT_PROPERTIES;
        $fileContent = $this->smartFileSystem->readFile($filePath);

        $originalFileContent = $fileContent;
        $fileContent = $this->updateFileContentWithPaths($srcAndTestsDirectories, $fileContent);

        if ($originalFileContent === $fileContent) {
            $this->symfonyStyle->success('Nothing to change');
            return ShellCode::SUCCESS;
        }

        $this->smartFileSystem->dumpFile($filePath, $fileContent);

        $message = sprintf('File "%s" was updated with new paths', $filePath);
        $this->symfonyStyle->success($message);

        return ShellCode::SUCCESS;
    }

    private function updateFileContentWithPaths(
        SrcAndTestsDirectories $srcAndTestsDirectories,
        string $fileContent
    ): string {
        if ($srcAndTestsDirectories->getTestsDirectories() !== []) {
            $sonarPathLine = $this->pathsFactory->createFromDirectories($srcAndTestsDirectories->getTestsDirectories());
            $fileContent = Strings::replace($fileContent, '#(sonar\.tests\=)(.*?)$#', '$1' . $sonarPathLine);
        }

        if ($srcAndTestsDirectories->getSrcDirectories() !== []) {
            $sonarPathLine = $this->pathsFactory->createFromDirectories($srcAndTestsDirectories->getSrcDirectories());
            $fileContent = Strings::replace($fileContent, '#(sonar\.sources\=)(.*?)$#', '$1' . $sonarPathLine);
        }

        return $fileContent;
    }
}