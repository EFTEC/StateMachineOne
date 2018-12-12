<?php

namespace eftec\tests;



class CompilationTest extends AbstractStateMachineOneTestCase {
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


	    self::assertEquals('1',$job->fields['field1'],'field1 must be 1');
	    self::assertEquals('2',$job->fields['field2'],'field2 must be 2');
	    self::assertEquals('3',$job->fields['counter'],'counter must be 3');
	    self::assertEquals('1',$job->state,'state must be 1');
	    self::assertEquals('active',$job->getActive(),'active must be active');
    }

}