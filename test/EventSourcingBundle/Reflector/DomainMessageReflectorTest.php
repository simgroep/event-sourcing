<?php
namespace Simgroep\EventSourcing\EventSourcingBundle\Reflector;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Simgroep\EventSourcing\TestCase;
class DomainMessageReflectorTest extends TestCase
{
    public function testReflector()
    {
    }

    public function testReflectorWithArrays()
    {
//        $metadata  = new Metadata();
//        $payload   = array("some_key" => "some_value");
//        $dateTime  = new \DateTime('now');
//        $reflector = new DomainMessageReflector(
//            new DomainMessage('1234', 0, $metadata, $payload, $dateTime)
//        );
//
//        $reflections = $reflector->reflect(DomainMessageReflector::PAYLOAD);
//        var_dump($reflections);
//        $this->assertEquals(json_encode($payload), $reflections);
    }

    public function testReflectorWithObjects()
    {
    }
}