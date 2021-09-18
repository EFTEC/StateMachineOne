<?php

namespace eftec\tests;



use eftec\PdoOne;
use eftec\statemachineone\Flags;
use eftec\statemachineone\StateMachineOne;
use Exception;

class StateMachineTest extends AbstractStateMachineOneTestCase {
    /**
     * @throws Exception
     */
    public function test1() {
	    $this->statemachineone->setStates([1,2,3]);
	    $this->statemachineone->setDefaultInitState(1);
	    $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'field3'=>0,'field4'=>123,'counter'=>0];
	    $this->statemachineone->addTransition(1,2,'when field1 = 1 set counter + 1 timeout 100 fulltimeout 200','change');
        $this->statemachineone->addTransition(2,2,'when field2 = 0 set field3 = -1','stay');
        $this->statemachineone->addTransition(2,2,'when field2 = 0 set field4 + field1-field3','stay'); // field4=field4(123)+field1(1)-field3(-1)
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
        self::assertEquals('-1',$job->fields['field3'],'field3 must be -1');
        self::assertEquals('125',$job->fields['field4'],'field4 must be 125');
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
    public function testAditionals() {
        // $this->statemachineone->setDebug(true);
        $this->statemachineone->setStates([1,2,3]);
        $this->statemachineone->resetTransition();
        $this->statemachineone->addTransition(1,2,'where 1=1 set v1=1');
        $this->statemachineone->addTransition(2,3,'where 1=1 set v1=2');
        $this->statemachineone->setDefaultInitState(1);
        $this->statemachineone->fieldDefault=['v1'=>0];
        $this->statemachineone->createJob();
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        self::assertEquals(2,$job->fields['v1']);

        $this->statemachineone->removeTransition(0);
        $this->statemachineone->removeTransitions(0,1);
        $this->statemachineone->setDefaultInitState(1);
        $this->statemachineone->fieldDefault=['v1'=>0];
        $this->statemachineone->createJob();
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        self::assertEquals(0,$job->fields['v1']);

    }
    public function notestPdo() {
        $smo=new StateMachineOne(null);
        $pdo=new PdoOne('test','','','');
        $pdo->open();
        $pdo->logLevel=3;
        $smo->setPdoOne($pdo);
        //$smo->loadDBAllJob();
    }
    /**
     * @throws Exception
     */
    public function test2() {
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'counter'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2=200','stay');

        $this->statemachineone->createJob();
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');
        
        self::assertEquals('200',$job->fields['field2'],'field2 must be 200');
        self::assertEquals('STATE1',$this->statemachineone->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }
    public function test4Duration() {
        $this->statemachineone->resetTransition();
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'delta'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2=200');
        $this->statemachineone->addTransition(20,30,'when field1 = 1 set field2=200');
        $this->statemachineone->duringState(30,'set delta=timestate()');
        // jobs
        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        sleep(1);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        self::assertGreaterThanOrEqual(1,$job->fields['delta']);
        sleep(1);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        self::assertGreaterThanOrEqual(2,$job->fields['delta']);

    }
    public function test3duringState() {
        // definitions
        $this->statemachineone->resetTransition();
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'field3'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2=200');
        $this->statemachineone->duringState(20,'set field2=300');
        $this->statemachineone->duringState(20,'when true() set field3=300');
        // jobs
        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        self::assertEquals('300',$job->fields['field2'],'field2 must be 300');
        self::assertEquals('300',$job->fields['field3'],'field3 must be 300');
    }
    public function test2new() {
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>0,'counter'=>0];
        $this->statemachineone->addTransition([10,20],30,'when field1 = 1 set field2=200','stay');

        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');

        self::assertEquals('200',$job->fields['field2'],'field2 must be 200');
        self::assertEquals(10,$this->statemachineone->getJobState($job));
        self::assertEquals('STATE1',$this->statemachineone->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }

    /**
     * @throws Exception
     */
    public function test2doc() {
        $tmpstate=new StateMachineOne(null);
        $tmpstate->setDocDB(__DIR__ . '/tmpdoc');
        //$tmpstate->getDocOne()->collection(dirname(__FILE__). '/tmpdoc',true);
        $tmpstate->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $tmpstate->setDefaultInitState(10);
        $tmpstate->fieldDefault=['field1'=>1,'field2'=>0,'counter'=>0];
        $tmpstate->addTransition(10,20,'when field1 = 1 set field2=200','stay');

        $tmpstate->createJob($tmpstate->fieldDefault);
        $tmpstate->checkAllJobs();
        $tmpstate->saveDBAllJob();
        $tmpstate->setJobQueue([]); // we deleted all the jobs
        $tmpstate->loadDBAllJob();
        $job=$tmpstate->getLastJob();
        // let's check consistency
        self::assertEquals(true,$tmpstate->checkConsistency(false),'consistency must be true');

        self::assertEquals('200',$job->fields['field2'],'field2 must be 200');
        self::assertEquals('STATE1',$tmpstate->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }
    /**
     * @throws Exception
     */
    public function test3() {
        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>new Flags(),'counter'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2.push("msg",2)','stay');

        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');
        /** @see \eftec\statemachineone\Flags::toString */
        self::assertEquals('a:5:{s:5:"stack";a:1:{s:3:"msg";s:1:"2";}s:7:"stackId";'.
            'a:1:{s:3:"msg";i:0;}s:10:"timeExpire";a:1:{s:3:"msg";i:-1;}s:5:'.
            '"level";a:1:{s:3:"msg";i:0;}s:7:"changed";i:1;}'
            ,$job->fields['field2']->toString(),'field2 must be a flag');
        /** @see \eftec\statemachineone\Flags::getFlag */
        self::assertEquals(['flag' => '2','id' => 0,'level' => 0,'time' => -1],$job->fields['field2']->getFlag('msg'),'field2.msg must returns a flag');
        self::assertEquals('STATE1',$this->statemachineone->getJobStateName($job),'current state must be STATE1');
        self::assertEquals('active',$job->getActive(),'active must be stop');

    }
    public $conteo=0;
    public function testFlagMessage() {
        $this->conteo=0;
        $this->statemachineone->customSaveDBJobLog=function ($arg=null,$arg2=null) {
            // here we save in the log
            $this->conteo++;
            var_dump('save in log ');
            var_dump($arg2);
            //var_dump($arg);
        };

        $this->statemachineone->setStates([10=>"STATE1",20=>"STATE2",30=>"STATE3"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>new Flags('myflag',true,$this->statemachineone),'counter'=>0];
        $this->statemachineone->addTransition(10,20,'when field1 = 1 set field2.message("hello world2")','change');
        $this->statemachineone->addTransition(20,30,'when field1 = 1 set field2.message("hello world")','stay');
        $this->statemachineone->addTransition(20,30,'when field1 = 1 set field2.message("hello world")','stay');


        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        //$a1=new Flags();
        //$a1->getStack()
        self::assertEquals(3,$this->conteo,'it must save the log twice');

        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');
        /** @see \eftec\statemachineone\Flags::toString */
        self::assertEquals('a:5:{s:5:"stack";a:1:{s:4:"_msg";s:11:"hello world";}s:7:"stackId";a:1:{s:4:"_msg";i:1;}s:10:"timeExpire";a:1:{s:4:"_msg";i:-1;}s:5:"level";a:1:{s:4:"_msg";i:0;}s:7:"changed";i:1;}'
            ,$job->fields['field2']->toString(),'field2 must be a correct flag');
        /** @see \eftec\statemachineone\Flags::getFlag */
        self::assertEquals(['flag' => 'hello world','id' => 1,'level' => 0,'time' => -1],$job->fields['field2']->getFlag('_msg'),'field2.msg has some wrong values');
        self::assertEquals('STATE2',$this->statemachineone->getJobStateName($job),'current state must be STATE2');
        self::assertEquals('active',$job->getActive(),'it must be active');
        $this->statemachineone->customSaveDBJobLog=null;
    }

    /**
     * @throws Exception
     */
    public function testSC() {
        $this->statemachineone->setStates([10=>"STATE1"]);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>1,'field2'=>new Flags(),'counter'=>0];
        /** @see \eftec\tests\ServiceClass::ping we are calling this method */
        $this->statemachineone->addTransition(10,10,'when field1 = 1 set field2=ping("hello")','stop');

        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $this->statemachineone->checkAllJobs();
        $job=$this->statemachineone->getLastJob();
        // let's check consistency
        self::assertEquals(true,$this->statemachineone->checkConsistency(false),'consistency must be true');

        self::assertEquals('pong hello',$job->fields['field2'],'field2 must returns pong hello');
    }
    /**
     * @throws Exception
     */
    public function testMultipleJobs() {
        $this->statemachineone->setStates([10=>"STATE_START",20=>'STATE_END']);
        $this->statemachineone->setDefaultInitState(10);
        $this->statemachineone->fieldDefault=['field1'=>123,'field2'=>-1,'counter'=>0];
        /** @see \eftec\tests\ServiceClass::ping we are calling this method */
        $this->statemachineone->addTransition(10,20,'when true() set field2=field1','change');
        $this->statemachineone->addTransition(20,20,'when true()','stop');
        $this->statemachineone->createJob($this->statemachineone->fieldDefault);
        $state2=$this->statemachineone->fieldDefault;
        $state2['field1']=456;
        $this->statemachineone->createJob($state2);
        $this->statemachineone->checkAllJobs();
  
        //var_dump($this->statemachineone->getJobQueue());
        
        $job=$this->statemachineone->getJob(1);
        // let's check consistency
        self::assertEquals(123,$job->fields['field2'],'field2 must returns 123');
        self::assertEquals([[10,20],[20,20]],$job->stateFlow,'stateFlow must returns the flow');
        $job=$this->statemachineone->getJob(2);
        // let's check consistency
        self::assertEquals(456,$job->fields['field2'],'field2 must returns 456 for the second job');
        self::assertEquals([[10,20],[20,20]],$job->stateFlow,'stateFlow must returns the flow');
    }
}
