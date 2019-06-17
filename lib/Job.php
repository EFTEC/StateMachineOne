<?php
namespace eftec\statemachineone;


/**
 * Class Job
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.7 2019-06-16
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
    /** @var array */
    var $flags;
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
    
	public function wait($param=null) {
		return false;
	}
	public function always($param=null) {
		return true;
	}
	
	/**
     * Job constructor.
     */
    public function __construct()
    {
        $this->log=[];
        $this->flags=[];
    }

    public function setFlag($msg,$whereId=0,$level=0) {
        $this->flags[$whereId]=[$level,$msg]; 
    }
    public function setFlagMin($msg,$whereId=0,$level=0) {
        if(isset($this->flags[$whereId])) {
            $curLevel=$this->flags[$whereId][0];
            if($level<=$curLevel) {
                $this->flags[$whereId][$level]=$msg;
            }
        } else {
            $this->setFlag($msg,$whereId,$level);
        }
    }
    public function setFlagMax($msg,$whereId=0,$level=0) {
        if(isset($this->flags[$whereId])) {
            $curLevel=$this->flags[$whereId][0];
            if($level>=$curLevel) {
                $this->flags[$whereId][$level]=$msg;
            }
        } else {
            $this->setFlag($msg,$whereId,$level);
        }
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

    public function getCurrentState()
    {
        return $this->state;
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



} // end Job
