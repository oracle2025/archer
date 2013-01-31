<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\Process\PHPUnitExecutableFinder;
use Icecave\Testing\Process\ProcessFactory;
use Icecave\Testing\Support\Isolator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\PhpExecutableFinder;

abstract class AbstractPHPUnitCommand extends Command
{
    /**
     * @param PhpExecutableFinder|null     $phpFinder
     * @param PHPUnitExecutableFinder|null $phpunitFinder
     * @param ProcessFactory|null          $processFactory
     * @param Isolator|null                $isolator
     */
    public function __construct(
        PhpExecutableFinder $phpFinder = null,
        PHPUnitExecutableFinder $phpunitFinder = null,
        ProcessFactory $processFactory = null,
        Isolator $isolator = null
    ) {
        if (null === $phpFinder) {
            $phpFinder = new PhpExecutableFinder;
        }
        $this->phpFinder = $phpFinder;
        $this->isolator = Isolator::get($isolator);
        if (null === $processFactory) {
            $processFactory = new ProcessFactory;
        }
        $this->processFactory = $processFactory;
        if (null === $phpunitFinder) {
            $phpunitFinder = new PHPUnitExecutableFinder(
                null,
                $this->processFactory,
                $this->isolator
            );
        }
        $this->phpunitFinder = $phpunitFinder;

        parent::__construct();
    }

    /**
     * @return PhpExecutableFinder
     */
    public function phpFinder()
    {
        return $this->phpFinder;
    }

    /**
     * @return PHPUnitExecutableFinder
     */
    public function phpunitFinder()
    {
        return $this->phpunitFinder;
    }

    /**
     * @return ProcessFactory
     */
    public function processFactory()
    {
        return $this->processFactory;
    }

    /**
     * @return Isolator
     */
    protected function isolator()
    {
        return $this->isolator;
    }

    /**
     * @return array<string>
     */
    protected function phpConfigurationArguments()
    {
        $arguments = array();
        foreach ($this->phpConfigurationSettings() as $key => $value) {
            $arguments[] = '--define';
            $arguments[] = sprintf('%s=%s', $key, $value);
        }

        return $arguments;
    }

    /**
     * @return array<string>
     */
    protected function phpConfigurationSettings()
    {
        $settings = array();
        foreach ($this->candidatePHPConfigurationPaths() as $path) {
            if ($this->isolator->is_file($path)) {
                $settings = array_merge(
                    $settings,
                    $this->isolator->parse_ini_file($path)
                );
            }
        }

        return $settings;
    }

    /**
     * @return string
     */
    protected function findPHPUnitConfiguration()
    {
        foreach ($this->candidatePHPUnitConfigurationPaths() as $path) {
            if ($this->isolator->is_file($path)) {
                return $path;
            }
        }

        return $this->defaultPHPUnitConfigurationPath();
    }

    /**
     * @return array<string>
     */
    abstract protected function candidatePHPConfigurationPaths();

    /**
     * @return array<string>
     */
    abstract protected function candidatePHPUnitConfigurationPaths();

    /**
     * @return string
     */
    abstract protected function defaultPHPUnitConfigurationPath();

    private $phpFinder;
    private $phpunitFinder;
    private $processFactory;
    private $isolator;
}
