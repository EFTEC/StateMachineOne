<?php /** @noinspection PhpUnused */

/** @noinspection PhpUnusedParameterInspection */

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
    public $idJob=0;
    /** @var int|null the number of the parent job  */
    public $idParentJob=null;
    /** @var int initial date (timestamp) */
    public $dateInit;
	/** @var int date of the last change (timestamp)*/
	public $dateLastChange;
    /** @var int date of end (timestamp) */
    public $dateEnd;
    /** @var int date of expiration (timestamp) */
    public $dateExpired;
    /** @var string|int the id of the current state  */
    public $state;
    /** @var array fields or values per job. It must be an associative array */
    public $fields;
    /** @var array indicates the flow of states */
    public $stateFlow=[];

    /** @var bool[] it is used to determine if transition was already executed */
    public $transitions=[];
    /**
     * none= the job doesn't exist or it's deleted.
     * inactive= the job exists but it hasn't started
     * active = the job is running
     * pause = the job is paused
     * stop = the job has ended (succesfully,cancelled or other)
     * @var string ['none','inactive','active','pause','stop'][$i]
     */
    private $active='none';
    /** @var bool If the job is new or not. It is used to store into the database (insert) */
    public $isNew=false;
    /** @var bool If the job is updated. It is used to store into the database (update) */
    public $isUpdate=false;
    /** @var string[]  */
    public $log;
    
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
        $this->transitions=[];
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
     * @param string|int $state The id of the state.
     * @return Job
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * It returns the current id of the state.
     * 
     * @return string|int
     */
    public function getCurrentState()
    {
        return $this->state;
    }
    /**
     * It sets the fields of the job.
     * 
     * @param array $fields An associative array.
     * @return Job
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        $this->setParentFields();
        return $this;
    }

    /**
     * It refresh the fields. If the fields are implementation of StateSerializable, then it sets the parent.
     */
    public function setParentFields() {
        foreach($this->fields as $item) {
            if ($item instanceof StateSerializable) {
                $item->setParent($this);
            }
        }
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
                trigger_error('type active not defined');
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
