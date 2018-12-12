<?php

namespace eftec\statemachineone;

/**
 * Class Transition
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.3 2018-12-11
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
    /** @var int Maximum duration (in seconds) of this transition. If the time it's up, then the transition is executed */
    private $duration=2000000;
	/** @var int Maximum duration (in second) considering the whole job. If the time it's up then this transitin is done */
	private $fullDuration=2000000;    
    /** @var string */
    var $txtCondition;
	/** @var array */
	var $set;    
    /** @var callable|mixed  */
    var $conditions=null;
    /** @var string[]  */
    var $logic=[];
    /** @var string=['change','pause','continue','stop'][$i]  */
    var $result="";

    /**
     * Transition constructor.
     * @param string $state0
     * @param string $state1
     * @param mixed $conditions
     * @param string $result
     */
    public function __construct($state0, $state1,  $conditions,$result="")
    {
        $this->state0 = $state0;
        $this->state1 = $state1;
        $this->result=$result;
        
        if (is_callable($conditions)) {
        	$this->txtCondition="custom function()";
            $this->function = $conditions;
        }
        if (is_string($conditions))  {
        	$this->txtCondition=$conditions;
	        $this->splitConditions($conditions);
	        /*
	        echo "<pre>";
	        var_dump($this->logic);
	        var_dump($this->set);
	        var_dump($this->duration);
	        var_dump($this->fullDuration);
	        var_dump($this->getDuration(new Job()));
	        var_dump($this->getFullDuration(new Job()));	        
	        echo "</pre>";
	        */
	        
        }
    }

	/**
	 * @param string $conditions
	 */
    private function splitConditions($conditions) {
	    $conditions=$this->cleanConditions($conditions);
    	$tmp=str_replace(' set ','||set ',$conditions); // the space it's because the command could be "set ..."
	    $tmp=str_replace(' timeout ','||timeout ',$tmp);
	    $tmp=str_replace(' fulltimeout ','||fulltimeout ',$tmp);
	    $arr=explode('||',$tmp);
	    foreach($arr as $item) {
	    	$subArray=explode(' ',$item);
	    	switch ($subArray[0]) {
			    case 'when':
			    	$this->logic=$subArray;
			    	break;
			    case 'set':
				    //array_shift($subArray); // remove the first element.
				    $this->set=$subArray;
				    break;
			    case 'timeout':
				    $this->duration=trim($subArray[1]);
				    break;
			    case 'fulltimeout':
				    $this->fullDuration=trim($subArray[1]);
				    break;
			    default:
			    	trigger_error('malformed condition ['.$subArray[0].']');
		    }
	    }
	    
    }
    
    private function cleanConditions($conditions) {
	    $conditions=trim($conditions);
	    $conditions=str_replace('"',"'",$conditions);
	    $conditions=str_replace(["\t","\r\n","\n","  "]," ",$conditions);
	    // we converted 4 spaces,3 spaces and 2 spaces into 1. Why?. let's say that there are 6 spaces, it removes all.
	    $conditions=str_replace(["    ","   ","  "]," ",$conditions);
	    return $conditions;
    }



	/**
	 * @param StateMachineOne $smo
	 * @param Job $job
	 * @return bool
	 * @throws \Exception
	 */
    public function evalLogic(StateMachineOne $smo, Job $job) {
        if (count($this->logic)<=1) return false;
        if ($this->logic[1]=="wait") return false;  // the first command is "when"
        $arr=$this->logic;
        $r = false;
        $prev=false;
        $c=count($arr);
        if ($c % 4 !=0) {
            trigger_error("Error. logic: incorrect number of operators. Tips: don't forget the spaces");
        }
        for($i=0;$i<$c;$i=$i+4) {
            $union=$arr[$i]; // it could be and/or/end
            $field0 = $job->strToValue( $arr[$i+1]);
            $field1 = $job->strToValue($arr[$i+3]);
            switch ($arr[$i+2]) {
                case '=':
                    $r = ($field0 == $field1);
                    break;
                case '<>':
                    $r = ($field0 != $field1);
                    break;
                case '<':
                    $r = ($field0 < $field1);
                    break;
                case '<=':
                    $r = ($field0 <= $field1);
                    break;
                case '>':
                    $r = ($field0 > $field1);
                    break;
                case '>=':
                    $r = ($field0 >= $field1);
                    break;
                case 'contain':
                    $r = (strpos($field0, $field1) !== false);
                    break;
                default:
                    trigger_error("comparison {$arr[$i+2]} not defined for transaction.");
            }
            switch ($union) {
                case 'and':
                    $r=$prev && $r;
                    break;
                case 'or':
                    $r=$prev || $r;
                    break;
                case 'when':
                    break;
                default:
                    trigger_error("union {$union} not defined for transaction.");                    
            }
            $prev=$r;
        }
        if ($r) {
            return $this->doTransition($smo,$job);
        }
        return false;
    }

	/**
	 * It does the transition unless it is stopped or the active status is not compatible.
	 * @param StateMachineOne $smo
	 * @param Job $job
	 * @param bool $forced If true then the transition is done whatever the active status (unless it is stop)
	 * @return bool True if the transition is done, otherwise false.
	 */
    public function doTransition($smo,$job,$forced=false) {
	    if ($job->getActive()=="stop") return false;
	    switch ($this->result) {
		    case "change":
		    	if ($job->getActive()=="active" || $forced) { // we only changed if the job is active.
				    $smo->changeState($job, $this->state1);
				    $job->doSetValues($this->set);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    return true;
			    }
			    break;
		    case "pause":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    $smo->changeState($job, $this->state1);
				    $job->setActive("pause");
				    $job->doSetValues($this->set);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    return true;
			    }
			    break;
		    case "continue":
			    if ($job->getActive()=="pause" || $job->getActive()=="active" || $forced) { // we only changed if the job is active or paused
				    $smo->changeState($job, $this->state1);
				    $job->setActive("active");
				    $job->doSetValues($this->set);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from "
					    .$smo->getStates()[$this->state0]."({$this->state0}) to "
					    .$smo->getStates()[$this->state1]."({$this->state1}) {$this->result}");
				    return true;
			    }
			    break;
		    case "stop":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    $smo->changeState($job, $this->state1);
				    $job->setActive("stop");
				    $job->doSetValues($this->set);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from "
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
	 * @param Job $job
	 * @return int
	 */
	public function getFullDuration(Job $job)
	{
		if (is_numeric($this->fullDuration)) {
			return $this->fullDuration;
		} else {
			return $job->strToValue($this->fullDuration);
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
			return $job->strToValue($this->duration);
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