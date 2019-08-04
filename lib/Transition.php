<?php

namespace eftec\statemachineone;


use eftec\minilang\MiniLang;

/**
 * Class Transition
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.4 2018-12-26
 * @link https://github.com/EFTEC/StateMachineOne
 */
class Transition
{
    /** @var string */
    var $state0;
    /** @var string */
    var $state1;
    /** @var callable */
    var $function;
    /** @var int|array Maximum duration (in seconds) of this transition. If the time it's up, then the transition is executed */
    private $duration=2000000;
	/** @var int|array Maximum duration (in second) considering the whole job. If the time it's up then this transitin is done */
	private $fullDuration=2000000;
    /** @var string */
    var $txtCondition;
    /** @var callable|mixed  */
    var $conditions=null;
    /** @var string=['change','pause','continue','stop'][$i]  */
    var $result="";
    /** @var MiniLang */
    var $miniLang;
    /** @var StateMachineOne */
    var $caller;
    
    /** @var Job */
    var $currentJob;

    /**
     * Transition constructor.
     *
     * @param StateMachineOne $caller
     * @param string $state0
     * @param string $state1
     * @param mixed  $conditions
     * @param string $result
     */
    public function __construct($caller,$state0, $state1,  $conditions,$result="")
    {
        $this->caller=$caller;
        $this->state0 = $state0;
        $this->state1 = $state1;
        $this->result=$result;

        if (is_callable($conditions)) {
        	$this->txtCondition="custom function()";
            $this->function = $conditions;
        }
        if (is_string($conditions))  {
        	$this->txtCondition=$conditions;
	        $this->caller->miniLang->separate($conditions);
	        if (isset($this->caller->miniLang->areaValue['timeout'])) {
		        $this->duration = $this->caller->miniLang->areaValue['timeout'];
	        }
	        if (isset($this->caller->miniLang->areaValue['fulltimeout'])) {
		        $this->fullDuration = $this->caller->miniLang->areaValue['fulltimeout'];
	        }
        }
    }
    
   

    /**
     * @param StateMachineOne $smo
     * @param Job             $job
     * @param int             $numLogic
     *
     * @return bool
     */
    public function evalLogic(StateMachineOne $smo, Job $job,$numLogic) {
        
	    $r=$this->caller->miniLang->evalLogic($numLogic);
	    if ($r==='wait') return false; // wait
        if ($r) {
            $r2=$this->doTransition($smo,$job,false,$numLogic);
            return $r2;
        } else {
            $this->caller->miniLang->evalSet($numLogic,'else');
        }
        return false;
    }

    /**
     * It does the transition unless it is stopped or the active status is not compatible.
     *
     * @param StateMachineOne $smo
     * @param Job             $job
     * @param bool            $forced If true then the transition is done whatever the active status (unless it is stop)
     * @param int             $numTransaction
     *
     * @return bool True if the transition is done, otherwise false.
     */
    public function doTransition($smo,$job,$forced=false,$numTransaction=0) {
	    if ($job->getActive()=="stop") return false;
	    $this->currentJob=$job;
	    switch ($this->result) {
		    case "change":
		    	if ($job->getActive()=="active" || $forced) { // we only changed if the job is active.
                    
				    $smo->changeState($job, $this->state1);
				    $this->caller->miniLang->evalSet($numTransaction);
				    //if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state <b>changed</b> from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    return true;
			    }
			    break;
            case "stay":
                if ($job->getActive()=="active" || $forced) { // we keep the current state
                    //$smo->changeState($job, $this->state1);
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    //$smo->addLog($job->idJob, "INFO", "state <b>stay</b> in "
                    //    .$smo->getStates()[$this->state0]."({$this->state0}) {$this->result}");
                    return true;
                }
                break;		    	
		    case "pause":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    if ($smo->pauseTriggerWhen==='instead') {
					    return $smo->callPauseTrigger($job);
				    } else {
					    if ($smo->pauseTriggerWhen === 'before') {
						    $smo->callPauseTrigger($job);
					    }
					    $smo->changeState($job, $this->state1);
					    $job->setActive("pause");
					    $this->caller->miniLang->evalSet($numTransaction);
					    //if ($smo->isDbActive()) $smo->saveDBJob($job);
					    $smo->addLog($job->idJob, "INFO", "state <b>changed</b> from "
						    . $smo->getStates()[$this->state0] . "({$this->state0}) to "
						    . $smo->getStates()[$this->state1] . "({$this->state1}) {$this->result}");
					    if ($smo->pauseTriggerWhen === 'after') {
						    $smo->callPauseTrigger($job);
					    }
					    return true;
				    }
			    }
			    break;
		    case "continue":
			    if ($job->getActive()=="pause" || $job->getActive()=="active" || $forced) { // we only changed if the job is active or paused
			    	$smo->changeState($job, $this->state1);
				    $job->setActive("active");
				    $this->caller->miniLang->evalSet($numTransaction);
				    //if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state <b>continue</b> from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    return true;
			    }
			    break;
		    case "stop":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    $smo->changeState($job, $this->state1);
				    $job->setActive("stop");
				    
				    $this->caller->miniLang->evalSet($numTransaction);
				    //if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state <b>stop</b> from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    $smo->callStopTrigger($job);
				    if ($smo->isAutoGarbage()) {
				    	$smo->garbageCollector(); // job done, deleting from the queue.
				    }
				    return true;
			    }
			    break;
		    default:
			    trigger_error("Error: Result of transition {$this->result} not defined");
	    }
	    return false;
    }

	/**
	 * It returns the full duration of the job.
	 * @param Job $job
	 * @return int
	 */
	public function getFullDuration(Job $job)
	{
		if (is_numeric($this->fullDuration)) {
			return $this->fullDuration;
		} else {
			return $this->caller->miniLang->getValue($this->fullDuration[0]
				, $this->fullDuration[1], $this->fullDuration[2]
				, $job, $job->fields);
		}
	}

	/**
	 * if the duration is numeric then it's not calculated. Otherwise, it is calculated using the job.
	 * @param Job $job
	 * @return int
	 */
	public function getDuration(Job $job)
	{
		if (is_numeric($this->duration)) {
			return $this->duration;
		} else {
			return $this->caller->miniLang->getValue($this->duration[0]
				, $this->duration[1], $this->duration[2]
				, $job, $job->fields);
		} 
		
	}


	/**
	 * @return string
	 */
	public function getTxtCondition()
	{
		return $this->txtCondition;
	}


    /**
     * @param string $state0
     * @return Transition
     */
    public function setState0($state0)
    {
        $this->state0 = $state0;
        return $this;
    }

    /**
     * @param string $state1
     * @return Transition
     */
    public function setState1($state1)
    {
        $this->state1 = $state1;
        return $this;
    }

    /**
     * @param callable $function
     * @return Transition
     */
    public function setFunction(callable $function)
    {
        $this->function = $function;
        return $this;
    }

    /**
     * @param int $duration
     * @return Transition
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }


}
