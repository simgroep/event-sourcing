<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Reflector;


use Broadway\Domain\DomainMessage;

class DomainMessageReflector
{

    const METADATA = 'Metadata';
    const PAYLOAD  = 'Payload';

    /**
     * @var DomainMessage
     */
    private $domainMessage;
    /**
     * @param DomainMessage $domainMessage
     */
    public function __construct(DomainMessage $domainMessage)
    {
        $this->domainMessage = $domainMessage;
    }
    /**
     * @param string $reflectionType
     * @return array
     */
    public function reflect(string $reflectionType) : array
    {
        $objectToReflect = $this->domainMessage->{'get' . $reflectionType}();
        $reflector = new \ReflectionClass($objectToReflect);
        $properties = $reflector->getProperties();
        $reflectionResult = array();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $propertyValue = $property->getValue($objectToReflect);
            switch (true) {
                case is_array($propertyValue):
                    $reflectionResult[$propertyName] = json_encode($propertyValue, JSON_PRETTY_PRINT);
                    break;
                case is_object($propertyValue):
                    $reflectionResult[$propertyName] = serialize($propertyValue);
                    if (method_exists($propertyValue, '__toString')) {
                        $reflectionResult[$propertyName] = (string)$propertyValue;
                    }
                    break;
                default:
                    $reflectionResult[$propertyName] = $propertyValue;
                    break;
            }
        }
        return $reflectionResult;
    }
}
