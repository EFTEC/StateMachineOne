<?php

use eftec\statemachineone\StateMachineOne;

use PHPUnit\Framework\TestCase;

class StateMachineOne_all_Test extends TestCase
{
    /** @var StateMachineOne */
    protected $state;

    public function setUp() {

        $this->state = new StateMachineOne(null);
        $this->state->setDebug(true);
    }


    public function test_1() {
        define('PARKED', 1);
        define('IDLING', 2);
        define('DRIVING', 3);

        $this->state->setDefaultInitState(PARKED);

        $this->state->setStates([
                                    PARKED => 'Parked',
                                    IDLING => 'Idling',
                                    DRIVING => 'Driving'
                                ]);


        $this->state->fieldDefault = [
            'pedal' => 0,
            'turnkey' => 0,
            'gas' => 100,
            'brake' => 0,
            'speed' => 0
        ];

        $this->state->addTransition(PARKED, IDLING, 'when pedal = 1 and turnkey = 1 and gas > 0');
        $this->state->addTransition(IDLING, DRIVING, 'when gas > 0 and speed > 0');
        $this->state->addTransition(DRIVING, IDLING, 'when brake = 1 and speed = 0');
        $this->state->addTransition(IDLING, PARKED, 'when turnkey = 0 and speed = 0');

        $job = $this->state->createJob([
                                         'pedal' => 0,
                                         'turnkey' => 0,
                                         'gas' => 100,
                                         'brake' => 0,
                                         'speed' => 0
                                     ]);
   
        // car is pakred
        $this->state->checkJob($job);
        $this->assertEquals(['pedal'=>0,'turnkey'=>0,'gas'=>100,'brake'=>0,'speed'=>0],$job->fields);
        $job->fields['pedal']=1;
        $job->fields['turnkey']=1;
        
        // i push the pedal and i turn the key. gas > 0
        $this->state->checkJob($job);
        $this->assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>0,'speed'=>0],$job->fields);
        $this->assertEquals(IDLING,$job->state);

        // i speed
        $job->fields['speed']=2;
        $this->state->checkJob($job);
        $this->assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>0,'speed'=>2],$job->fields);
        $this->assertEquals(DRIVING,$job->state);

        // speed is zero, I am braking
        $job->fields['speed']=0;
        $job->fields['brake']=1;
        $this->state->checkJob($job);
        $this->assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>1,'speed'=>0],$job->fields);
        $this->assertEquals(IDLING,$job->state);

        // it's stopped and key is not turned.
        $job->fields['speed']=0;
        $job->fields['turnkey']=0;
        $this->state->checkJob($job);
        $this->assertEquals(['pedal'=>1,'turnkey'=>0,'gas'=>100,'brake'=>1,'speed'=>0],$job->fields);
        $this->assertEquals(PARKED,$job->state);

    }

}