<?php
namespace Icecave\Testing\Console;

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

        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_application = Phake::partialMock(
            __NAMESPACE__ . '\Application',
            'foo',
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame('foo', $this->_application->packageRoot());
    }

    public function testEnabledCommands()
    {
        $expected = array(
            'help',
            'list',
            'test',
            'update',
            'github:create-token',
            'github:fetch-token',
            'github:set-token',
            'travis:fetch-public-key',
            'travis:update-config',
        );

        $this->assertSame($expected, array_keys($this->_application->all()));
    }

    public function testEnabledCommandsICT()
    {
        Command\Internal\AbstractInternalCommand::setIsEnabled(null);
        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{"name": "icecave/testing"}')
        ;
        $this->_application = new Application('foo', $this->_isolator);
        $expected = array(
            'help',
            'list',
            'test',
            'update',
            'github:create-token',
            'github:fetch-token',
            'github:set-token',
            'travis:fetch-public-key',
            'travis:update-config',
            'internal:update-binaries',
        );

        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('foo/composer.json'),
            Phake::verify($this->_isolator)->file_get_contents('foo/composer.json')
        );
        $this->assertSame($expected, array_keys($this->_application->all()));
    }

    public function testDoRun()
    {
        $commandName = uniqid();
        Phake::when($this->_application)
            ->getDefaultCommandName(Phake::anyParameters())
            ->thenReturn($commandName)
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
            Phake::verify($this->_application)->getDefaultCommandName(),
            Phake::verify($command)->run(Phake::capture($actualInput), $output)
        );
        $this->assertSame($commandName, $actualInput->getFirstArgument());
    }

    public function testGetDefaultCommandName()
    {
        $reflector = new ReflectionObject($this->_application);
        $method = $reflector->getMethod('getDefaultCommandName');
        $method->setAccessible(true);

        $this->assertSame('list', $method->invoke($this->_application));
    }
}
