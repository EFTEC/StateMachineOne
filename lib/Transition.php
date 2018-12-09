<?php

namespace eftec\statemachineone;

/**
 * Class Transition
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.0 2018-12-08
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
    /** @var int */
    var $duration;
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
     * @param int $duration
     * @param string $result
     */
    public function __construct(string $state0, string $state1,  $conditions, int $duration=null,$result="")
    {
        $this->state0 = $state0;
        $this->state1 = $state1;
        $this->result=$result;
	    $this->duration = $duration;
        if (is_callable($conditions)) {
            $this->function = $conditions;
        }
        if (is_string($conditions))  {
        	$conditions=trim($conditions);
        	$conditions=str_replace('"',"'",$conditions);
	        $conditions=str_replace(["\t","\r\n","\n","  "]," ",$conditions);
	        // we converted 4 spaces,3 spaces and 2 spaces into 1. Why?. let's say that there are 6 spaces, it removes all.
	        $conditions=str_replace(["    ","   ","  "]," ",$conditions);  
        	if (strpos($conditions,'set ')===false) {
        		$this->set=[];
		        $this->logic=explode(' ',trim($conditions));
	        } else {
		        $arrMan=explode('set ',$conditions);
		        $this->set=explode(' ',trim('set '.$arrMan[1])); // we added the 'set ' back before explode
		        $this->logic=explode(' ',trim($arrMan[0]));
	        }
        }
    }

	/**
	 * For use future
	 * @param $arr
	 */
    private function compiler($arr) {
    	$result= <<<'cin'
    	function ff(StateMachineOne $smo,Job $job) {
    		$r=false;
    		if ($job->field['aaa']==2 && $job->field['bbb']==3) $r=true;
    		if ($r) {
    		    $this->doTransition($smo,$job);
    		}    		
    	}
cin;
    }
    
    private function strToValue($job,$string) {
        $cField0=substr($string,0,1);
        if (ctype_alpha($cField0)) {
	        if (strpos($string,'()')!==false) {
	        	// it's a function.
		        $string=call_user_func(substr($string,0,-2),$job);
	        } else {
		        // it's a field
		        $string=$job->fields[$string];
	        }
        } else {
	        if ($cField0=='$') {
	        	// it's a global variable.
		        $string=@$GLOBALS[substr($string,1)];
	        }
            if ($cField0=='"' || $cField0=="'") {
                // its a string
                $string=substr($string,1,-1);
            }
        }
        return $string;
    }
	private function strToVariable($job,$variable,$setValue) {
		$cField0=substr($variable,0,1);
		if (ctype_alpha($cField0)) {
			if (strpos($variable,'()')!==false) {
				// it's a function.
				call_user_func(substr($variable,0,-2),$job,$setValue);
				return;
			} else {
				// it's a field
				$job->fields[$variable]=$setValue;
				return;
			}
		} else {
			if ($cField0=='$') {
				// it's a global variable.
				@$GLOBALS[substr($variable,1)]=$setValue;
				return;
			}
		}
		trigger_error("Error, you can't set a literal value [$variable]");
	}    

    /**
     * @param StateMachineOne $smo
     * @param Job $job
     * @throws \Exception
     */
    public function evalLogic(StateMachineOne $smo, Job $job) {
        
        if (count($this->logic)<=1) return;
        if ($this->logic[1]=="timeout") return;  // the first command is start
        $arr=$this->logic;
        $r = false;
        $prev=false;
        $c=count($arr);
        if ($c % 4 !=0) {
            trigger_error("Error. logic: incorrect number of operators. Tips: don't forget the spaces");
        }
        for($i=0;$i<$c;$i=$i+4) {
            $union=$arr[$i]; // it could be and/or/end
            $field0 = $this->strToValue($job, $arr[$i+1]);
            $field1 = $this->strToValue($job, $arr[$i+3]);
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
                case 'where':
                    break;
                default:
                    trigger_error("union {$union} not defined for transaction.");                    
            }
            $prev=$r;
        }
        if ($r) {
            $this->doTransition($smo,$job);
        }
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
				    $this->doSetValues($job);
				    $job->setIsUpdate(true);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from {$this->state0} to {$this->state1} changed");
				    return true;
			    }
			    break;
		    case "pause":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    $job->setActive("pause");
				    $this->doSetValues($job);
				    $job->setIsUpdate(true);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from {$this->state0} to {$this->state1} paused");
				    return true;
			    }
			    break;
		    case "continue":
			    if ($job->getActive()=="pause" || $job->getActive()=="active" || $forced) { // we only changed if the job is active or paused
				    $job->setActive("active");
				    $this->doSetValues($job);
				    $job->setIsUpdate(true);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from {$this->state0} to {$this->state1} continued");
				    return true;
			    }
			    break;
		    case "stop":
			    if ($job->getActive()=="active" || $job->getActive()=="pause" || $forced) { // we only changed if the job is paused or active.
				    $job->setActive("stop");
				    $this->doSetValues($job);
				    $job->setIsUpdate(true);
				    if ($smo->isDbActive()) $smo->saveDBJob($job);
				    $smo->addLog($job->idJob, "INFO", "state changed from {$this->state0} to {$this->state1} stopped");
				    $smo->removeJob($job); // job done, deleting from the queue.
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
	 */
    public function doSetValues($job) {
    	if (count($this->set)) {
		    $c=count($this->set);
		    if ($c % 4 !=0) {
			    trigger_error("logic {$this->logic} incorrect number of operators. Tips: don't forget the spaces");
		    }
		    //  0     1     2 3 0    1     2 3     
		    // set variable = 2 , variable = 5 
		    for($i=0;$i<$c;$i=$i+4) {
		    	$varName=$this->set[$i+1];
		    	$op=$this->set[$i+2];
		    	$varSet=$this->strToValue($job,$this->set[$i+3]);
		    	echo "setting $varName = $varSet<br>";
		        $this->strToVariable($job,$varName,$varSet);	
		    }
	    }
    }
    /**
     * @param string $state0
     * @return Transition
     */
    public function setState0(string $state0): Transition
    {
        $this->state0 = $state0;
        return $this;
    }

    /**
     * @param string $state1
     * @return Transition
     */
    public function setState1(string $state1): Transition
    {
        $this->state1 = $state1;
        return $this;
    }

    /**
     * @param callable $function
     * @return Transition
     */
    public function setFunction(callable $function): Transition
    {
        $this->function = $function;
        return $this;
    }

    /**
     * @param int $duration
     * @return Transition
     */
    public function setDuration(int $duration): Transition
    {
        $this->duration = $duration;
        return $this;
    }


}