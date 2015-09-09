<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Serializer;

use Spray\Serializer\BoundClosureSerializer;
use Spray\Serializer\SerializerInterface;

class DomainEventStreamSerializer extends BoundClosureSerializer
{
    public function __construct()
    {
        parent::__construct(
            'Broadway\Domain\DomainEventStream'
        );
    }

    /**
     * @return callable
     */
    protected function bindSerializer()
    {
        return function($subject, array &$data, SerializerInterface $serializer) {
            $data['events'] = array();
            foreach ($subject->events as $event) {
                $data['events'][] = $serializer->serialize($event);
            }
            return $data;
        };
    }

    /**
     * @return callable
     */
    protected function bindDeserializer()
    {
        return function($subject, array &$data, SerializerInterface $serializer) {
            $subject->events = array();
            foreach ($data['events'] as $event) {
                $subject->events[] = $serializer->deserialize(null, $event);
            }
            return $subject;
        };
    }


}
