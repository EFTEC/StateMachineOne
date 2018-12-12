<?php
namespace eftec\statemachineone;


/**
 * Class Job
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.1 2018-12-09
 * @link https://github.com/EFTEC/StateMachineOne
 */
class Job {
	/** @var int number or position of the job on the queue */
    var $idJob=0;
    /** @var int */
    var $dateInit;
	/** @var int */
	var $dateLastChange;    
    /** @var int */
    var $dateEnd;
    /** @var int */
    var $dateExpired;    
    /** @var mixed */
    var $state;
    /** @var array */
    var $fields;
    /** 
     * none= the job doesn't exist or it's deleted.
     * inactive= the job exists but it hasn't started
     * active = the job is running
     * pause = the job is paused
     * stop = the job has ended (succesfully,cancelled or other)
     * @var string ['none','inactive','active','pause','stop'][$i]
     */
    private $active='none';

    var $isNew=false;
    var $isUpdate=false;

 
    /** @var array */
    var $log;



	public function strToVariable($variable,$setValue,$op) {
		$cField0=substr($variable,0,1);
		if (ctype_alpha($cField0)) {
			if (strpos($variable,'()')!==false) {
				// it's a function. (regardless of the op)
				call_user_func(substr($variable,0,-2),$this,$setValue);
				return;
			} else {
				// it's a field
				switch ($op) {
					case '=':
						if ($setValue==='___FLIP___') {
							$this->fields[$variable]=(!$this->fields[$variable])?1:0;
						} else {
							$this->fields[$variable] = $setValue;
						}
						break;
					case '+': $this->fields[$variable]+=$setValue;break;
					case '-': $this->fields[$variable]-=$setValue;break;
					default: trigger_error('operator ['.$op.'] for set transition not defined');
				}
				return;
			}
		} else {
			if ($cField0=='$') {
				// it's a global variable.
				switch ($op) {
					case '=':
						if ($setValue==='___FLIP___') {
							@$GLOBALS[substr($variable,1)]=(!@$GLOBALS[substr($variable,1)])?1:0;
						} else {
							@$GLOBALS[substr($variable,1)]=$setValue;
						}
						break;
					case '+': @$GLOBALS[substr($variable,1)]+=$setValue;break;
					case '-': @$GLOBALS[substr($variable,1)]-=$setValue;break;
					default: trigger_error('operator ['.$op.'] for set transition not defined');
				}

				return;
			}
		}
		trigger_error("Error, you can't set a literal value [$variable]");
	}

	/**
	 * Set the values of a job based in the operation of $this->set<br>
	 * @param string[] $set
	 */
	public function doSetValues($set) {
		if ($set===null) return;
		if (count($set)) {
			$c=count($set);
			if ($c % 4 !=0) {
				trigger_error("logic incorrect number of operators. Tips: don't forget the spaces");
			}
			//  0     1     2 3 0    1     2 3     
			// set variable = 2 , variable = 5 
			for($i=0;$i<$c;$i=$i+4) {
				$varName=$set[$i+1];
				$op=$set[$i+2]; // = (set), + (add), - (rest)
				$varSet=$this->strToValue($set[$i+3]);
				$this->strToVariable($varName,$varSet,$op);
			}
			$this->setIsUpdate(true);
		}
	}
	
	
	public function strToValue($string) {
		switch ($string) {
			case 'null()':
				return null;
			case 'false()':
				return false;
			case 'true()':
				return true;
			case 'on()':
				return 1;
			case 'off()':
				return 0;
			case 'undef()':
				return -1;
			case 'flip()':
				return "___FLIP___"; // value must be flipped (only for set).
			case 'now()':
			case 'timer()':
				return time();
			case 'interval()':
				return time()- $this->dateLastChange;
			case 'fullinterval()':
				return time()- $this->dateInit;
		}
		$cField0=$string[0];
		if (ctype_alpha($cField0)) {
			if (strpos($string,'()')!==false) {
				// it's a function.
				$string=call_user_func(substr($string,0,-2),$this);
			} else {
				// it's a field
				$string=$this->fields[$string];
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




	/**
     * StateMachineJob constructor.
     */
    public function __construct()
    {
        $this->log=[];
    }

    
    /**
     * @param int $dateInit
     * @return Job
     */
    public function setDateInit($dateInit)
    {
        $this->dateInit = $dateInit;
        return $this;
    }

	/**
	 * @param int $dateLastChange
	 * @return Job
	 */
	public function setDateLastChange($dateLastChange)
	{
		$this->dateLastChange = $dateLastChange;
		return $this;
	}
    /**
     * @param int $dateEnd
     * @return Job
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    /**
     * @param int $dateExpired
     * @return Job
     */
    public function setDateExpired($dateExpired)
    {
        $this->dateExpired = $dateExpired;
        return $this;
    }

    /**
     * @param mixed $state
     * @return Job
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param array $fields
     * @return Job
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param string $active= ['none','inactive','active','pause','stop'][$i]
     * @return Job
     */
    public function setActive($active)
    {
        $this->active=$active;
        return $this;
    }
    public function setActiveNumber($activeNum)
    {
        switch ($activeNum) {
            case 0: $this->active='none'; break;
            case 1: $this->active='inactive'; break;
            case 2: $this->active='active'; break;
            case 3: $this->active='pause'; break;
            case 4: $this->active='stop'; break;
            default: $this->active='none'; break;
        }
        return $this;
    }

    /**
     * @return string= ['none','inactive','active','pause','stop'][$i]
     */
    public function getActive() {
        return $this->active;
    }
    public function getActiveNumber() {
        switch ($this->active) {
            case 'none':
                return 0;
            case 'inactive':
                return 1;
            case 'active':
                return 2;
            case 'pause':
                return 3;
            case 'stop':
                return 4;
            default:
                trigger_error("type active not defined");
                return -1;
        }
    }    

    /**
     * @param bool $isNew
     * @return Job
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
        return $this;
    }

    /**
     * @param bool $isUpdate
     * @return Job
     */
    public function setIsUpdate($isUpdate)
    {
        $this->isUpdate = $isUpdate;
        return $this;
    }
    
    
    
} // end StateMachineJob
