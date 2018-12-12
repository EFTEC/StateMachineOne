<?php

namespace eftec\tests;


use eftec\statemachineone\StateMachineOne;
use PHPUnit\Framework\TestCase;


abstract class AbstractStateMachineOneTestCase extends TestCase {
    protected $statemachineone;
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        $this->statemachineone=new StateMachineOne();
        //$this->statemachineone->setDebug(true);
    }

}