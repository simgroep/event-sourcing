<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Reflector\Assets;


class DummyClass
{

    private $property1;

    protected $property2;

    public $property3;

    public function __construct()
    {
        $this->property1 = "private";
        $this->property2 = "protected";
        $this->property3 = "public";
    }
}