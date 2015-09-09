<?php
namespace Simgroep\EventSourcing\EventSourcingBundle\Reflector;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Simgroep\EventSourcing\EventSourcingBundle\Reflector\Assets\DummyClass;
use Simgroep\EventSourcing\TestCase;

class DomainMessageReflectorTest extends TestCase
{
    public function testReflectorWithArrays()
    {
        $reflector = new DomainMessageReflector(
            new DomainMessage('id', 1, new Metadata(array()), new DummyClass(), DateTime::now())
        );

        $reflections = $reflector->reflect(DomainMessageReflector::PAYLOAD);

        $this->assertEquals(
            array(
                "property1" => "private",
                "property2" => "protected",
                "property3" => "public"
            ),
            $reflections
        );
    }
}