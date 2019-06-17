<?php

namespace eftec\tests;



use eftec\statemachineone\Flag;

class StateMachineTest extends AbstractStateMachineOneTestCase {
    /**
     * @throws \Exception
     */
    public function test1() {
	    $this->statemachineone->setStates([1,2,3]);
	    $this->statemachineone->setDefaultInitState(1);
	    $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'counter'=>0];
	    $this->statemachineone->addTransition(1,2,'when field1 = 1 set counter + 1 timeout 100 fulltimeout 200','change');
	    $this->statemachineone->addTransition(2,3,'when field2 = 0 set field1 = 2 , counter + 1','stop');

	    $this->statemachineone->createJob($this->statemachineone->fieldDefault);
	    $this->statemachineone->checkAllJobs();
	    $job=$this->statemachineone->getLastJob();
        // let's check consistency
	    self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');

		// testing if the timeouts are set
	    self::assertEquals('100',$this->statemachineone->getTransitions()[0]->getDuration($job),'duration must be 100');
	    self::assertEquals('200',$this->statemachineone->getTransitions()[0]->getFullDuration($job),'full duration must be 200');
	    // testing the result fields
	    self::assertEquals('2',$job->fields['field1'],'field1 must be 2');
	    self::assertEquals('0',$job->fields['field2'],'field0 must be 0');
	    self::assertEquals('2',$job->fields['counter'],'counter must be 2');
	    self::assertEquals('3',$job->state,'state must be 3');
	    self::assertEquals('stop',$job->getActive(),'active must be stop');


	    // this job will freeze for 100 seconds
	    $this->statemachineone->createJob(['field1'=>1,'field2'=>2,'counter'=>3]);
	    //$this->statemachineone->checkAllJobs();
	    $job=$this->statemachineone->getLastJob();

        // testing event
	    self::assertEquals('1',$job->fields['field1'],'field1 must be 1');
	    self::assertEquals('2',$job->fields['field2'],'field2 must be 2');
	    self::assertEquals('3',$job->fields['counter'],'counter must be 3');
	    self::assertEquals('1',$job->state,'state must be 1');
	    self::assertEquals('active',$job->getActive(),'active must be active');
	    // testing events..
	    $this->statemachineone->addEvent("testme","set field1='hello' , field2='world'");
	    $this->statemachineone->callEvent("testme",$job);

	    self::assertEquals('hello',$job->fields['field1'],'field1 must be "hello"');
	    self::assertEquals('world',$job->fields['field2'],'field2 must be "world"');  
    }
    /**
     * @throws \Exception
     */
    public function test2() {
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'counter'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2=200','stay');

        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');
        
        self::assertEquals('200',$job->fields['field2'],'field2 must be 200');
        self::assertEquals('STATE1',$this->statemachineone->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }
    /**
     * @throws \Exception
     */
    public function test3() {
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>new Flag(),'counter'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2.setflag("msg",2)','stay');

        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');

        self::assertEquals('msg;;2;;1',$job->fields['field2']->toString(),'field2 must be a flag');
        self::assertEquals('STATE1',$this->statemachineone->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }
}
