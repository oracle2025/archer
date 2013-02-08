<?php
namespace Icecave\Archer\Console;

use Phake;
use PHPUnit_Framework_TestCase;
use ReflectionObject;
use Symfony\Component\Console\Input\ArrayInput;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Command\Internal\AbstractInternalCommand::setIsEnabled(null);

        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->_application = Phake::partialMock(
            __NAMESPACE__ . '\Application',
            'foo',
            $this->_fileSystem
        );
        $this->_reflector = new ReflectionObject($this->_application);
    }

    public function testConstructor()
    {
        $this->assertSame('foo', $this->_application->packageRoot());
        $this->assertSame($this->_fileSystem, $this->_application->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->_application = new Application(
            'foo'
        );

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->_application->fileSystem()
        );
    }

    public function testEnabledCommands()
    {
        $expected = array(
            'help',
            'list',
            'coverage',
            'test',
            'update',
            'github:create-token',
            'github:fetch-token',
            'travis:build'
        );

        $this->assertSame($expected, array_keys($this->_application->all()));
    }

    public function testEnabledCommandsArcher()
    {
        Command\Internal\AbstractInternalCommand::setIsEnabled(null);
        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"name": "icecave/archer"}')
        ;
        $this->_application = new Application('foo', $this->_fileSystem);
        $expected = array(
            'help',
            'list',
            'coverage',
            'test',
            'update',
            'github:create-token',
            'github:fetch-token',
            'internal:update-binaries',
            'travis:build'
        );

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('foo/composer.json'),
            Phake::verify($this->_fileSystem)->read('foo/composer.json')
        );
        $this->assertSame($expected, array_keys($this->_application->all()));
    }

    public function testDoRun()
    {
        $commandName = uniqid();
        Phake::when($this->_application)
            ->defaultCommandName(Phake::anyParameters())
            ->thenReturn($commandName)
        ;
        Phake::when($this->_application)
            ->rawArguments(Phake::anyParameters())
            ->thenReturn(array())
        ;
        $command = Phake::partialMock(
            'Symfony\Component\Console\Command\Command',
            $commandName
        );
        $this->_application->add($command);
        $this->_application->setAutoExit(false);
        $input = new ArrayInput(array());
        $output = Phake::mock('Symfony\Component\Console\Output\OutputInterface');
        $this->_application->run($input, $output);
        $expectedInput = new ArrayInput(array('command' => $commandName));

        Phake::inOrder(
            Phake::verify($this->_application)->defaultCommandName(),
            Phake::verify($command)->run(Phake::capture($actualInput), $output)
        );
        $this->assertSame($commandName, $actualInput->getFirstArgument());
    }

    public function testRawArguments()
    {
        $method = $this->_reflector->getMethod('rawArguments');
        $method->setAccessible(true);
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = array('foo', 'bar', 'baz');
        $actual = $method->invoke($this->_application);
        $_SERVER['argv'] = $argv;

        $this->assertSame(array('bar', 'baz'), $actual);
    }

    public function testDefaultCommandName()
    {
        $method = $this->_reflector->getMethod('defaultCommandName');
        $method->setAccessible(true);

        $this->assertSame('test', $method->invoke($this->_application));
    }
}
