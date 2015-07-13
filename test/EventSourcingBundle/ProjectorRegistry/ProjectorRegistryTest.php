<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry;

class ProjectorRegistryTest extends \Simgroep\EventSourcing\TestCase
{
    public function testIsIteratable()
    {
        $registry = new ProjectorRegistry();
        $this->assertInstanceOf("IteratorAggregate",$registry);
    }

    public function testHasArrayAccess()
    {
        $registry = new ProjectorRegistry();
        $this->assertInstanceOf("ArrayAccess",$registry);
    }

    public function testGetProjectorOnAssociativeKeys()
    {
        $projector = $this->getProjectorMock();
        $registry  = new ProjectorRegistry();
        $registry->addProjector($projector, "someKey");

        $this->assertEquals(true, $registry->offsetExists("someKey"));
        $this->assertEquals($projector, $registry['someKey']);

        foreach ($registry as $key => $valueProjector) {
            $this->assertEquals("someKey",$key);
            $this->assertEquals($projector, $valueProjector);
        }
    }

    private function getProjectorMock()
    {
        return $this->getMock('Broadway\ReadModel\ProjectorInterface');
    }
}