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
        $registry->addProjector($projector, "serviceId", "repository");

        $this->assertEquals(true, $registry->offsetExists("serviceId"));
        $this->assertEquals($projector, $registry['serviceId']['projector']);
        $this->assertEquals("repository", $registry['serviceId']['repository']);

        foreach ($registry as $key => $projectorMetaData) {
            $this->assertEquals("serviceId",$key);
            $this->assertEquals(
                $projectorMetaData,
                [
                    "projector" => $projector,
                    "repository" => "repository"
                ]
            );
        }
    }

    private function getProjectorMock()
    {
        return $this->getMock('Broadway\ReadModel\ProjectorInterface');
    }
}