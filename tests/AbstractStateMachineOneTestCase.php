<?php

namespace eftec\tests;


use eftec\statemachineone\StateMachineOne;
use PHPUnit\Framework\TestCase;


abstract class AbstractStateMachineOneTestCase extends TestCase {
    protected $statemachineone;
    
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $serviceObject=new ServiceClass();
        $this->statemachineone=new StateMachineOne($serviceObject);
        //$this->statemachineone->setDebug(true);
    }
}

class ServiceClass {
    public static function ping($value) { // this method could be non-static
        return "pong $value";
    }
}
