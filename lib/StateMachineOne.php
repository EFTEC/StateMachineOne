<?php
namespace eftec\statemachineone;

use DateTime;
use eftec\DaoOne;


/**
 * Class StateMachineOne
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.0 2018-12-08
 * @link https://github.com/EFTEC/StateMachineOne
 */
class StateMachineOne {
	private $debug=false;
	
    private $counter=0;
    /** @var Job[] */
    private $jobQueue;
    /** @var int  */
    private $defaultInitState=0;
    
    private $states=[];
    /** @var Transition[] */
    private $transitions=[];
    /** @var bool If the database must be used. It is marked true every automatically when we set the database. */
    private $dbActive=false;
    private $dbServer="";
    private $dbUser="";
    private $dbPassword="";
    private $dbSchema="";
    /** @var DaoOne */
    private $daoOne=null;
    /** @var string The name of the table to store the jobs */
    var $tableJobs="stm_jobs";
    /** @var string The name of the table to store the logs per job. If it's empty then it is not used */
	var $tableJobLogs="";
	/** @var array The list of database columns used by the job */
    var $columnJobs=['idjob','idactive','idstate','dateinit','datelastchange','dateexpired','dateend'];
    /** @var array The List of database columns used by the log of the job  */
	var $columnJobLogs=['idjoblog','idjob','type','description','date'];
  
    /** @var string[] It indicates a special field to set the reference of the job. */
    var $idRef=['idref'];
    /** @var array It indicates extra fields/states */
    var $extraColumnJobs=[''];
    
    
    
    // callbacks
    /** @var callable it's called when we change state (by default it returns true)  */
    private $changeStateTrigger;
    /** @var callable it's called when we start the job (by default it returns true) */
    private $startTrigger;
    /** @var callable it's called when we pause the job (by default it returns true) */
    private $pauseTrigger;
    /** @var callable it's called when we stop the job (by default it returns true) */
    private $stopTrigger;
    /** @var callable This function increased in 1 the next id of the job. It is only called if we are not using a database */
    private $getNumberTrigger;

    /**
     * It sets the method called when the job change state
     * @param callable $changeStateTrigger
     */
    public function setChangeStateTrigger(callable $changeStateTrigger): void
    {
        $this->changeStateTrigger = $changeStateTrigger;
    }
	public function callChangeStateTrigger($job) {
		return call_user_func($this->changeStateTrigger,$this,$job);
	}
    /**
     * It sets the method called when the job starts
     * @param callable $startTrigger
     */
    public function setStartTrigger(callable $startTrigger): void
    {
        $this->startTrigger = $startTrigger;
    }
	public function callStartTrigger($job) {
		return call_user_func($this->startTrigger,$this,$job);
	}
    /**
     * It sets the method called when job is paused
     * @param callable $pauseTrigger
     */
    public function setPauseTrigger(callable $pauseTrigger): void
    {
        $this->pauseTrigger = $pauseTrigger;
    }
	public function callPauseTrigger($job) {
		return call_user_func($this->pauseTrigger,$this,$job);
	}
    /**
     * It sets the method called when the job stop
     * @param callable $stopTrigger
     * @test void this(),'it must returns nothing'
     */
    public function setStopTrigger(callable $stopTrigger): void
    {
    	//function(StateMachineOne $smo,Job $job) { return true; }
        $this->stopTrigger = $stopTrigger;
    }
    public function callStopTrigger($job) {
	    return call_user_func($this->stopTrigger,$this,$job);
    }

    /**
     * It sets a function to returns the number of the process. By default, it is obtained by the database
     * or via an internal counter.
     * @param callable $getNumberTrigger
     */
    public function setGetNumberTrigger(callable $getNumberTrigger): void
    {
        $this->getNumberTrigger = $getNumberTrigger;
    }


	/**
	 * add a new transition
	 * @param string $state0 Initial state
	 * @param string $state1 Ending state
	 * @param mixed $conditions Conditions, it could be a function or a string 'instock = "hello"'
	 * @param int $duration Duration of the transition in seconds.
	 * @param string $result =['change','pause','continue','stop'][$i]
	 */
    public function addTransition(string $state0, string $state1,  $conditions, int $duration=null,$result="change") {
        $this->transitions[]=new Transition($state0,$state1,$conditions,$duration,$result);
    }

	/**
	 * We clear all transitions.
	 */
    public function resetTransition() {
    	$this->transitions=[];
    }

    /**
     * Returns true if the database is active
     * @return bool
     */
    public function isDbActive(): bool
    {
        return $this->dbActive;
    }

    /**
     * It sets the database as active. When we call setDb() then it is set as true automatically.
     * @param bool $dbActive
     */
    public function setDbActive(bool $dbActive): void
    {
        $this->dbActive = $dbActive;
    }

	/**
	 * Returns true if is in debug mode.
	 * @return bool
	 */
	public function isDebug(): bool
	{
		return $this->debug;
	}

	/**
	 * Set the debug mode. By default the debug mode is false.
	 * @param bool $debug
	 */
	public function setDebug(bool $debug): void
	{
		$this->debug = $debug;
	}
    
    
    
    /**
     * Returns the job queue.
     * @return Job[]
     */
    public function getJobQueue(): array
    {
        return $this->jobQueue;
    }

    /**
     * Set the job queue
     * @param Job[] $jobQueue
     */
    public function setJobQueue(array $jobQueue): void
    {
        $this->jobQueue = $jobQueue;
    }

    /**
     * @param int $defaultInitState
     */
    public function setDefaultInitState(int $defaultInitState): void
    {
        $this->defaultInitState = $defaultInitState;
    }

    /**
     * Gets an array with the states
     * @return array
     */
    public function getStates(): array
    {
        return $this->states;
    }

    /**
     * Set the array with the states
     * @param array $states
     */
    public function setStates(array $states): void
    {
        $this->states = $states;
    }

    
    /**
     * Constructor of the class. By default, the construct set default triggers.
     * StateMachineOne constructor.
     */
    public function __construct()
    {
        // reset values
        $this->jobQueue=[];
        $this->counter=0;

        $this->changeStateTrigger=function(StateMachineOne $smo, $oldState, $newState) {
            return true;
        };
        $this->startTrigger=function(StateMachineOne $smo,Job $job) {
            return true;
        };
        $this->pauseTrigger=function(StateMachineOne $smo,Job $job) {
            return true;
        };
        $this->stopTrigger=function(StateMachineOne $smo,Job $job) {
            return true;
        };        
        $this->getNumberTrigger=function(StateMachineOne $smo) {
            
            // you could use the database if you are pleased to.
            $smo->counter++;
            return $smo->counter;
        };

    }

    /**
     * It sets the database
     * @param string $server    server ip, example "localhost"
     * @param string $user      user of the database, example "root"
     * @param string $pwd       password of the database, example "123456"
     * @param string $db        database(schema), example "sakila"
     * @return bool true if the database is open
     */
    public function setDB($server, $user, $pwd, $db) {
        $this->dbActive=true;
        $this->dbServer=$server;
        $this->dbUser=$user;
        $this->dbPassword=$pwd;
        $this->dbSchema=$db;
        try {
            $this->getDB();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * It returns the current connection. If there is not a connection then it generates a new one.
     * @return DaoOne
     * @throws \Exception
     */
    public function getDB() {
        if ($this->daoOne==null) {
            $this->daoOne=new DaoOne($this->dbServer,$this->dbUser,$this->dbPassword,$this->dbSchema);
            $this->daoOne->open();
        }
        return $this->daoOne;
    }

    /**
     * Loads a job from the database
     * @param $idJob
     * @throws \Exception
     */
    public function loadDBJob($idJob) {
        $row=$this->getDB()->select("*")->from($this->tableJobs)->where("idjob=?",[$idJob])->first();
        $this->jobQueue[$row['idjob']]=$this->arrayToJob($row);
    }

    /**
     * It loads all jobs from the database with all active state but none and stopped.
     * @throws \Exception
     */
    public function loadDBActiveJobs() {
        $rows=$this->getDB()->select("*")->from($this->tableJobs)->where("idactive not in (0,4)")->order('dateinit')->toList(); 
        $this->jobQueue=[];
        foreach($rows as $row) {
            $this->jobQueue[$row['idjob']]=$this->arrayToJob($row);
        }
    }
	/**
	 * It loads all jobs from the database regardless its active state.
	 * @throws \Exception
	 */
	public function loadDBAllJob() {
		$rows=$this->getDB()->select("*")->from($this->tableJobs)->order('dateinit')->toList();
		$this->jobQueue=[];
		foreach($rows as $row) {
			$this->jobQueue[$row['idjob']]=$this->arrayToJob($row);
		}
	}    
    
    private function arrayToJob($row) {
        $job=new Job();
        $job->idJob=$row['idjob'];
        $job->setIsUpdate(false)
            ->setIsNew(false)
            ->setActiveNumber($row['idactive'])
            ->setState($row['idstate'])
            ->setDateInit(strtotime($row['dateinit']))
	        ->setDateLastChange(strtotime($row['datelastchange']))
            ->setDateExpired(strtotime($row['dateexpired']))
            ->setDateEnd(strtotime($row['dateend']));
        $arr=[];
        foreach($this->idRef as $k) {
            $arr[$k]=$row[$k];
        }
        $job->setIdRef($arr);
        $arr=[];
        foreach($this->extraColumnJobs as $k) {
            $arr[$k]=$row[$k];
        }
        $job->setFields($arr);
        return $job;
    }

    /**
     * @param Job $job
     * @return array
     */
    private function jobToArray($job) {
        $arr=[];
        $arr['idjob']=$job->idJob;
        $arr['idactive']=$job->getActiveNumber();
        $arr['idstate']=$job->state;
        $arr['dateinit']=date("Y-m-d H:i:s",$job->dateInit);
	    $arr['datelastchange']=date("Y-m-d H:i:s",$job->dateLastChange);
        $arr['dateexpired']=date("Y-m-d H:i:s",$job->dateExpired);
        $arr['dateend']=date("Y-m-d H:i:s",$job->dateEnd);
        foreach($this->idRef as $k) {
            $arr[$k]=$job->idRef[$k];
        }
        foreach($this->extraColumnJobs as $k) {
            $arr[$k]=$job->fields[$k];
        }        
        return $arr;
    }

	/**
	 * (optional), it creates a database table, including indexes.
	 * @param bool $drop if true, then the table will be dropped.
	 * @throws \Exception
	 */
    public function createDbTable($drop=false) {
        if ($drop) {
            $sql='DROP TABLE IF EXISTS `'.$this->tableJobs.'`';
            $this->getDB()->runRawQuery($sql);
	        $sql='DROP TABLE IF EXISTS `'.$this->tableJobLogs.'`';
	        $this->getDB()->runRawQuery($sql);            
        }
        $sql="CREATE TABLE IF NOT EXISTS `".$this->tableJobs."` (
                  `idjob` INT NOT NULL AUTO_INCREMENT,
                  `idactive` int,
                  `idstate` int,
                  `dateinit` timestamp,
                  `datelastchange` timestamp,
                  `dateexpired` timestamp,
                  `dateend` timestamp,";
        foreach($this->idRef as $k) {
            $sql.=" `$k` varchar(50),";
        }
        foreach($this->extraColumnJobs as $k) {
            $sql.=" `$k` varchar(50),";
        }
        $sql.="PRIMARY KEY (`idjob`));";
        $this->getDB()->runRawQuery($sql);
        // We created index.
        $sql="ALTER TABLE `".$this->tableJobs."`
			ADD INDEX `".$this->tableJobs."_key1` (`idactive` ASC),
			ADD INDEX `".$this->tableJobs."_key2` (`idstate` ASC),
			ADD INDEX `".$this->tableJobs."_key3` (`dateinit` ASC)";
	    $this->getDB()->runRawQuery($sql);
        if ($this->tableJobLogs) {
	        $sql = "CREATE TABLE IF NOT EXISTS `" . $this->tableJobLogs . "` (
                  `idjoblog` INT NOT NULL AUTO_INCREMENT,
                  `idjob` int,
                  `type` varchar(50),
                  `description` varchar(2000),
                  `date` timestamp,
                  PRIMARY KEY (`idjoblog`));";
	        $this->getDB()->runRawQuery($sql);
        }
    }

    /**
     * It saves a job in the database. It only saves a job that is marked as new or updated 
     * @param Job $job
     * @return int Returns the id of the new job, 0 if not saved or -1 if error.
     */
    public function saveDBJob($job) {
    	
        try {
        if ($job->isNew) {
            $r = $this->getDB()
                ->from($this->tableJobs);
            $arr=$this->jobToArray($job);
            foreach($arr as $k=>$item) {
                $this->getDB()->set($k.'=?',$item);
            }
            $job->idJob=$this->getDB()->insert();
            $job->isNew=false;
            //$this->jobQueue[$job->idJob]=$job;
            return $job->idJob;
        } else if ($job->isUpdate) {
            $this->getDB()
                ->from($this->tableJobs);
            $arr=$this->jobToArray($job);
            foreach($arr as $k=>$item) {
                $this->getDB()->set($k.'=?',$item);
            }
	        $this->getDB()->where('idjob=?',$job->idJob);
            $this->getDB()->update();
            $job->isUpdate=false;
            //$this->jobQueue[$job->idJob]=$job;
            return $job->idJob;
        }
        } catch (\Exception $e) {
            $this->addLog($job->idJob,"ERROR","Saving the job ".$e->getMessage());
        }
        return 0;
    }

	/**
	 * Insert a new job log into the database.
	 * @param $idJob
	 * @param $arr
	 * @return bool
	 */
	public function saveDBJobLog($idJob,$arr) {
    	if (!$this->tableJobLogs) return true; // it doesn't save if the table is not set.
		try {
			$this->getDB()
				->from($this->tableJobLogs);
			$this->getDB()->set('idjob=?',$idJob);
			$this->getDB()->set('type=?',$arr['type']);
			$this->getDB()->set('description=?',$arr['description']);
			$this->getDB()->set('date=?',date("Y-m-d H:i:s",$arr['date']));
			$this->getDB()->insert();
			return true;	
		} catch (\Exception $e) {
			echo "error ".$e->getMessage();
			return false;
			//$this->addLog(0,"ERROR","Saving the joblog ".$e->getMessage());
		}
		
	}

    /**
     * It saves all jobs in the database that are marked as new or updated.
     * @return bool
     */
    public function saveDBAllJob() {
        foreach($this->jobQueue as $idJob=> $job) {
            if ($this->saveDBJob($job)===-1) return false;
        }
        return true;
    }

    /**
     * It creates a new job.
     * @param int[] $idRef  Every job must refence some object/operation/entity/individual.
     * @param array $fields
     * @param string $active=['none','inactive','active','pause','stop'][$i]
     * @param mixed $initState
     * @param int|null $dateStart
     * @param int|null $durationSec Duration (maximum) in seconds of the event
     * @param int|null $expireSec
     * @return Job
     */
    public function createJob($idRef, $fields, $active='active', $initState=null, $dateStart=null, $durationSec=null, $expireSec=null) {
        $initState=$initState===null?$this->defaultInitState:$initState;
        $dateStart=$dateStart===null?time():$dateStart;
        $dateEnd=$durationSec===null?2147483640:$dateStart+$durationSec;
        $dateExpire=$expireSec===null?2147483640:$dateStart+$expireSec;
        $job=new Job();
        $job->setIdRef($idRef)
            ->setDateInit($dateStart)
	        ->setDateLastChange(time()) // now.
            ->setDateEnd($dateEnd)
            ->setDateExpired($dateExpire)
            ->setState($initState)
            ->setFields($fields)
            ->setActive($active)
            ->setIsNew(true)
            ->setIsUpdate(false);

        if(!$this->dbActive) {
            $idJob=call_user_func($this->getNumberTrigger,$this);
            $job->idJob=$idJob;
        } else {
            $idJob=$this->saveDBJob($job);
        }
        if ($dateStart<=time() || $active=='active') {
            // it start.
	        $this->callStartTrigger($job);
            $job->setActive($active);
            if($this->dbActive)  {
                $idJob=$this->saveDBJob($job); // we update the job  
            } 
        }
        $this->jobQueue[$job->idJob]=$job; // we store the job created in the list of jobs
        return $job;
    }

    /**
     * It gets a job by id.
     * @param int $idJob
     * @return Job|null returns null if the job doesn't exist.
     */
    public function getJob($idJob) {
        return !isset($this->jobQueue[$idJob])?null:$this->jobQueue[$idJob];
    }

    /**
     * It checks a specific job and proceed to change state.
     * We check a job and we change the state
     * @param $idJob
     * @throws \Exception
     */
    public function checkJob($idJob) {
    	$job=$this->jobQueue[$idJob]; // $job is an instance, not a copy!.
        if ($job->dateInit<=time() && $job->getActive()=='inactive') {
            // it starts the job.
	        $this->callStartTrigger($job);
            $job->setActive('active');
            $job->setIsUpdate(true);
        }
        foreach($this->transitions as $trn) {
        	   if (isset($job)) { // the isset it is because the job could be deleted from the queue.
	            if ($trn->state0 == $job->state) {
		            if (time()-$job->dateLastChange >$trn->duration) {
			            // time is up, we will do the transition anyways
			            $trn->doTransition($this,$job,true);
		            } else {
			            if (count($trn->logic)) {
				            // we check the transition based on table
				            $trn->evalLogic($this, $job);
			            } else if (is_callable($trn->function)) {
				            // we check the transition based on function
				            call_user_func($trn->function, $this, $job);
			            }
		            }

	            }
            }
        }
    }

    /**
     * It checks all jobs available (if the active state of the job is any but none or stop)
     * @return bool true if the operation was successful, false if error.
     */
    public function checkAllJobs() {
        foreach($this->jobQueue as $idx=> &$job) {
	        if (get_class($job)=="eftec\statemachineone\Job") { // why?, because we use foreach
	        	if ($job->getActive()!="none" && $job->getActive()!="stop") {
			        try {
				        $this->checkJob($idx);
			        } catch (\Exception $e) {
				        $this->addLog($idx, "ERROR", "State error " . $e->getMessage());
				        return false;
			        }
		        }
	        }
        }
        return true;
    }

    /**
     * It changes the state of a job manually.
     * It changes the state manually.
     * @param Job $job
     * @param mixed $newState
     * @return bool true if the operation was succesful, otherwise (error) it returns false
     */
    public function changeState(Job $job,$newState) {
        if ($this->callChangeStateTrigger($job)) {
            //$this->addLog($job->idJob,'CHANGE',"Change state #{$job->idJob} from {$job->state }->{$newState}");
            $job->state = $newState;
	        $job->isUpdate=true;
	        $job->dateLastChange=time();
            return true;
        } else {
            $this->addLog($job->idJob,'ERROR',"Change state #{$job->idJob} from {$job->state }->{$newState} failed");
            return false; 
        }
    }

	/**
	 * @param int|null $time timestamp with microseconds
	 * @return string
	 */
    private function dateToString($time=null) {
    	if ($time==='now') {
		    $d = new DateTime($time);
	    } else {
		    $d= DateTime::createFromFormat('U.u', $time);
	    }
	    return $d->format("Y-m-d H:i:s.u");
    }

    /**
     * It adds a log of the job.
     * @param int $idJob
     * @param string $type=['ERROR','WARNING','INFO','DEBUG'][$i]
     * @param string $description
     */
    public function addLog($idJob,$type,$description) {
    	$arr=['type'=>$type,'description'=>$description,'date'=>microtime(true)];
        $this->jobQueue[$idJob]->log[]=$arr;
	    if ($this->debug) {
	    	echo "<b>Job #{$idJob}</b> ".$this->dateToString(microtime(true))." [$type]:  $description<br>";
	    }
        
        
        if ($this->dbActive) {
	         
        	$this->saveDBJobLog($idJob,$arr);
        }
    }

    /**
     * It removes a jobs of the queue.
     * @param Job $job
     * @test void removeJob(null)
     */
    public function removeJob($job) {
    	if ($job===null) return;
    	$id=$job->idJob;
    	$job=null;
	    $this->jobQueue[$id]=null;
        unset($this->jobQueue[$id]);
    }

	/**
	 * We check if the states are consistents. It is only for testing.
	 * @test void this()
	 */
    public function checkConsistence() {
        $arr=$this->states;
        $arrCopy=$arr;
        echo "<hr>checking:<hr>";
        foreach($this->transitions as $trans) {
            echo "CHECKING: {$trans->state0}->{$trans->state1} ";
            $fail=false;
            if (!in_array($trans->state0,$arr)) {
                $fail=true;
                echo "ERROR: Transition {$trans->state0} -> {$trans->state1} with missing initial state<br>";
            } else {
                $arrCopy[]=$trans->state0;
            }
            if (!in_array($trans->state1,$arr)) {
                $fail=true;
                echo "ERROR: Transition {$trans->state0} -> {$trans->state1} with missing ending state<br>";
            } else {
                $arrCopy[]=$trans->state1;
            }         
            if (!$fail) {
                echo "OK<br>";
            }
        }
        foreach($arr as $missing) {
            if (!in_array($missing,$arrCopy)) {
                echo "State: {$missing} not used<br>";
            }
        }
    }
}

