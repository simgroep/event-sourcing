<?php

namespace Simgroep\EventSourcing\CommandHandling;

use Simgroep\EventSourcing\Repository\Exception\ConcurrencyException;
use Simgroep\EventSourcing\TestCase;
use Broadway\CommandHandling\CommandBusInterface;
use stdClass;
use Exception;

class CommandGatewayTest extends TestCase
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;
    
    /**
     * @var Scheduler
     */
    private $scheduler;
    
    /**
     * @var Callback
     */
    private $callback;
    
    protected function setUp()
    {
        $this->commandBus = $this->getMock('Broadway\CommandHandling\CommandBusInterface');
        $this->scheduler = $this->getMock('Simgroep\EventSourcing\CommandHandling\Scheduler');
        $this->callback = $this->getMock('Simgroep\EventSourcing\CommandHandling\Callback');
    }
    
    protected function createGateway()
    {
        return new CommandGateway($this->commandBus, new IntervalScheduler(1, 2));
    }
    
    public function testSendCommand()
    {
        $command = new stdClass;
        
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($command));
        
        $gateway = $this->createGateway();
        $gateway->send($command);
    }
    
    public function testSendCommandWithCallback()
    {
        $command = new stdClass;
        
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($command));
        
        $this->callback
            ->expects($this->once())
            ->method('onSuccess')
            ->will($this->returnValue(true));
        
        $gateway = $this->createGateway();
        $this->assertTrue($gateway->send($command, $this->callback));
    }
    
    public function testRetrySentCommand()
    {
        $command = new stdClass;
        
        $this->commandBus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->will($this->throwException(new ConcurrencyException('Concurrency')));
        $this->commandBus
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->identicalTo($command));
        
        $gateway = $this->createGateway();
        $gateway->send($command);
    }
    
    public function testFailRetrySentCommandNoRetriesLeft()
    {
        $this->setExpectedException('Simgroep\EventSourcing\Repository\Exception\ConcurrencyException');
        
        $command = new stdClass;
        
        $this->commandBus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->will($this->throwException(new ConcurrencyException('Concurrency')));
        $this->commandBus
            ->expects($this->at(1))
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->will($this->throwException(new ConcurrencyException('Concurrency')));
        $this->commandBus
            ->expects($this->at(2))
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->will($this->throwException(new ConcurrencyException('Concurrency')));
        
        $gateway = $this->createGateway();
        $gateway->send($command);
    }
    
    public function testDoNotRetryGenericException()
    {
        $this->setExpectedException('Exception');
        
        $command = new stdClass;
        
        $this->commandBus
            ->expects($this->at(0))
            ->method('dispatch')
            ->with($this->identicalTo($command))
            ->will($this->throwException(new Exception('Generic')));
        
        $gateway = $this->createGateway();
        $gateway->send($command);
    }
}
