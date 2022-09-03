<?php /** @noinspection PhpIllegalPsrClassPathInspection */

/** @noinspection PhpUnhandledExceptionInspection */

use eftec\statemachineone\StateMachineOne;

use PHPUnit\Framework\TestCase;

class StateMachineOne_all_Test extends TestCase
{
    /** @var StateMachineOne */
    protected $state;

    public function setUp():void {

        $this->state = new StateMachineOne(null);
        $this->state->setDebug();
        if(!defined('PARKED')) {
            define('PARKED', 1);
        }
        if(!defined('IDLING')) {
            define('IDLING', 2);
        }
        if(!defined('DRIVING')) {
            define('DRIVING', 3);
        }
    }


    public function test_1(): void
    {

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
        $this->state->addTransition(IDLING, PARKED, 'when turnkey = 0 and speed = 0','stop');

        $job = $this->state->createJob([
                                         'pedal' => 0,
                                         'turnkey' => 0,
                                         'gas' => 100,
                                         'brake' => 0,
                                         'speed' => 0
                                     ]);

        // car is pakred
        $this->state->checkJob($job);
        self::assertEquals(['pedal'=>0,'turnkey'=>0,'gas'=>100,'brake'=>0,'speed'=>0],$job->fields);
        $job->fields['pedal']=1;
        $job->fields['turnkey']=1;

        // I push the pedal and I turn the key. gas > 0
        $this->state->checkJob($job);
        self::assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>0,'speed'=>0],$job->fields);
        self::assertEquals(IDLING,$job->state);

        // i speed
        $job->fields['speed']=2;
        $this->state->checkJob($job);
        self::assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>0,'speed'=>2],$job->fields);
        self::assertEquals(DRIVING,$job->state);

        // speed is zero, I am braking
        $job->fields['speed']=0;
        $job->fields['brake']=1;
        $this->state->checkJob($job);
        self::assertEquals(['pedal'=>1,'turnkey'=>1,'gas'=>100,'brake'=>1,'speed'=>0],$job->fields);
        self::assertEquals(IDLING,$job->state);

        // it's stopped and key is not turned.
        $job->fields['speed']=0;
        $job->fields['turnkey']=0;
        $this->state->checkJob($job);
        self::assertEquals(['pedal'=>1,'turnkey'=>0,'gas'=>100,'brake'=>1,'speed'=>0],$job->fields);
        self::assertEquals(PARKED,$job->state);

    }



    public function test_2(): void
    {
        $this->state->setDefaultInitState(PARKED);
        $this->state->setStates([
            PARKED => 'Parked',
            IDLING => 'Idling',
            DRIVING => 'Driving'
        ]);
        $this->state->fieldDefault = [
            'arr1' => ['a1'=>1,'a2'=>2],
            'arr2' => ['a1'=>1,'a2'=>2]
        ];

        $this->state->addTransition(PARKED, PARKED
            , 'when arr1.a1=1 and arr1.a2=2 set arr2.a1="ok" else arr2.a1="wrong"','stay');
        $this->state->addTransition(PARKED, PARKED
            , 'when 1=arr1.a1 and 2=arr1.a2 set arr2.a1="ok" else arr2.a1="wrong"','stay');
        $this->state->addTransition(PARKED, PARKED
            , 'when arr1.a1=1 and arr1.a2=3 set arr2.a2="wrong" else arr2.a2="ok"','stay');


        $job = $this->state->createJob($this->state->fieldDefault);

        // car is pakred
        $this->state->checkAllJobs();

        self::assertEquals(
            [
                'arr1'=>['a1'=>1,'a2'=>2]
                ,'arr2'=>['a1'=>"ok",'a2'=>"ok"]
            ]
            ,$job->fields);


    }

    public function test_3(): void
    {

        $this->state->setDefaultInitState(PARKED);
        $this->state->setStates([
            PARKED => 'Parked',
            IDLING => 'Idling',
            DRIVING => 'Driving'
        ]);
        $this->state->fieldDefault = [
            'TIMEELAPSED'=>50,
            'MEDICIONES' => [0,100,256],
            'MSG'=>'??',
            'MSG2'=>'??',
        ];

        $this->state->addTransition(PARKED, PARKED
            , 'when MEDICIONES._count>=3 and TIMEELAPSED>=MEDICIONES.0 and TIMEELAPSED<=MEDICIONES.1 and MEDICIONES.2=256
            set MSG="OK" else MSG="FALLA"','stay');
        $this->state->addTransition(PARKED, PARKED
            , 'when MEDICIONES.2=768
            set MSG2="FALLA" else MSG2="OK"','stay');
        //var_dump($this->state->transitions[0]->caller->miniLang);

        $job = $this->state->createJob($this->state->fieldDefault);

        // car is pakred
        $this->state->checkAllJobs();
        self::assertEquals('OK',$job->fields['MSG2']);
        self::assertEquals('OK',$job->fields['MSG']);



    }

}
