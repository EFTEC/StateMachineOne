<?php
namespace eftec\statemachineone;

use DateTime;
use eftec\DaoOne;


/**
 * Class StateMachineOne
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.4 2018-12-16
 * @link https://github.com/EFTEC/StateMachineOne
 */
class StateMachineOne {

	public $VERSION='1.4';

	private $debug=false;
	/** @var bool  */
	private $autoGarbage=false;

    private $counter=0;
    /** @var Job[] */
    private $jobQueue;
    /** @var int  */
    private $defaultInitState=0;

    private $states=[];
    /** @var Transition[] */
    private $transitions=[];

	private $events=[];

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

    /** @var array It indicates extra fields/states */
    var $fieldDefault=[''];

    private $changed=false;

    // callbacks
    /** @var callable it's called when we change state (by default it returns true)  */
    private $changeStateTrigger;
	/** @var string =['after','before','instead'][$i] */
	private $changeStateTriggerWhen;
    /** @var callable it's called when we start the job (by default it returns true) */
    private $startTrigger;
	/** @var string =['after','before','instead'][$i] */
	private $startTriggerWhen;
    /** @var callable it's called when we pause the job (by default it returns true) */
    private $pauseTrigger;
    /** @var string =['after','before','instead'][$i] */
    public $pauseTriggerWhen;
    /** @var callable it's called when we stop the job (by default it returns true) */
    private $stopTrigger;
	/** @var string =['after','before','instead'][$i] */
	private $stopTriggerWhen;
    /** @var callable This function increased in 1 the next id of the job. It is only called if we are not using a database */
    private $getNumberTrigger;


	/**
	 * add a new transition
	 * @param string $state0 Initial state
	 * @param string $state1 Ending state
	 * @param mixed $conditions Conditions, it could be a function or a string 'instock = "hello"'
	 * @param string $result =['change','pause','continue','stop'][$i]
	 */
    public function addTransition($state0, $state1,  $conditions, $result="change") {
        $this->transitions[]=new Transition($state0,$state1,$conditions,$result);
    }

	/**
	 * It adds an event with a name
	 * @param int|string $name name of the event
	 * @param string $conditions Example: 'set field = field2 , field = 0 , field = function()
	 */
    public function addEvent($name,$conditions) {
    	$conditions=$this->cleanConditions($conditions);
	    $this->events[$name]=explode(' ',$conditions);
    }

    public function callEvent($name,$job=null) {
    	if (!isset($this->events[$name])) {
    		trigger_error('event [$name] not defined');
	    }
    	if ($job===null) {
    		$jobExec=$this->getLastJob();
	    } else {
    		$jobExec=$job;
	    }
    	$jobExec->doSetValues($this->events[$name]);
    	$this->checkJob($jobExec);
    	if ($this->dbActive) $this->saveDBJob($jobExec);
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
	 * We clear all transitions.
	 */
    public function resetTransition() {
    	$this->transitions=[];
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

        $this->changeStateTrigger=function(StateMachineOne $smo, Job $job, $newState) {
            return true;
        };
	    $this->startTrigger=function(StateMachineOne $smo, Job $job) {
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
        $row=$this->getDB()->select("*")->from($this->tableJobs)->where("idactive<>0 and idjob=?",[$idJob])->first();
        $this->jobQueue[$row['idjob']]=$this->arrayToJob($row);
    }

    /**
     * It loads all jobs from the database with all active state but none(0) and stopped(4).
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
        foreach($this->fieldDefault as $k=>$v) {
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
        foreach($this->fieldDefault as $k=>$v) {
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
	    $exist=$this->getDB()->select(1)->from('information_schema.tables')
		    ->where('table_schema=?',[$this->dbSchema])
		    ->where('table_name=? ',[$this->tableJobs])
		    ->limit('1')->firstScalar();


	    $sql="CREATE TABLE IF NOT EXISTS `".$this->tableJobs."` (
                  `idjob` INT NOT NULL AUTO_INCREMENT,
                  `idactive` int,
                  `idstate` int,
                  `dateinit` timestamp,
                  `datelastchange` timestamp,
                  `dateexpired` timestamp,
                  `dateend` timestamp,";
        foreach($this->fieldDefault as $k=>$v) {
        	$sql.=$this->createColTable($k,$v);
        }
        $sql.="PRIMARY KEY (`idjob`));";
        $this->getDB()->runRawQuery($sql);
	    if ($exist!=1) {
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
    }
    private function createColTable($k,$v) {
    	$sql="";
	    switch (1==1) {
		    case is_string($v):
			    $sql = " `$k` varchar(50),";
			    break;
		    case is_float($v):
		    case is_double($v):
			    $sql = " `$k` decimal(10,2),";
			    break;
		    case is_null($v):
		    case is_numeric($v):
		    case is_bool($v):
			    $sql = " `$k` int,";
			    break;
	    }
	    return $sql;
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
     * @param array $fields
     * @param string $active=['none','inactive','active','pause','stop'][$i]
     * @param mixed $initState
     * @param int|null $dateStart
     * @param int|null $durationSec Duration (maximum) in seconds of the event
     * @param int|null $expireSec
     * @return Job
     */
    public function createJob($fields, $active='active', $initState=null, $dateStart=null, $durationSec=null, $expireSec=null) {
        $initState=$initState===null?$this->defaultInitState:$initState;
        $dateStart=$dateStart===null?time():$dateStart;
        $dateEnd=$durationSec===null?2147483640:$dateStart+$durationSec;
        $dateExpire=$expireSec===null?2147483640:$dateStart+$expireSec;
        $job=new Job();
        $job->setDateInit($dateStart)
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
            $this->saveDBJob($job); 
        }
        if ($dateStart<=time() || $active=='active') {
            // it start.
	        $this->callStartTrigger($job);
            $job->setActive($active);
            if($this->dbActive)  {
                $this->saveDBJob($job); 
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
	 * @return Job|mixed|null
	 */
    public function getLastJob() {
    	if (count($this->jobQueue)===0) return null;
    	return end($this->jobQueue);
    }

    /**
     * It checks a specific job and proceed to change state.
     * We check a job and we change the state
     * @param Job $job
     * @throws \Exception
     */
    public function checkJob($job) {
        if ($job->dateInit<=time() && $job->getActive()=='inactive') {
            // it starts the job.
	        $this->callStartTrigger($job);
            $job->setActive('active');
            $job->setIsUpdate(true);
        }
        foreach($this->transitions as $trn) {
        	   if (isset($job)) { // the isset it is because the job could be deleted from the queue.
	            if ($trn->state0 == $job->state) {
		            if (time()-$job->dateLastChange >= $trn->getDuration($job) ||
			            time()-$job->dateInit >= $trn->getFullDuration($job) ) {
			            // timeout time is up, we will do the transition anyways
			            if ($trn->doTransition($this,$job,true)) {
				            $this->changed = true;
			            }
		            } else {
			            if (count($trn->logic)) {
				            // we check the transition based on table
				            if ($trn->evalLogic($this, $job)) {
				            	$this->changed=true;
				            }
			            } else if (is_callable($trn->function)) {
				            // we check the transition based on function
				            if (call_user_func($trn->function, $this, $job)) {
					            $this->changed=true;
				            }
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
    	for($iteraction=0;$iteraction<10;$iteraction++) {
		    $this->changed=false;
		    foreach ($this->jobQueue as $idx => &$job) {
			    if (get_class($job) == "eftec\statemachineone\Job") { // why?, because we use foreach
				    if ($job->getActive() != "none" && $job->getActive() != "stop") {
					    try {
						    $this->checkJob($job);
					    } catch (\Exception $e) {
						    $this->addLog($idx, "ERROR", "State error " . $e->getMessage());
						    return false;
					    }
				    }
			    }
		    }
		    if (!$this->changed) {
			    break;
		    } // we don't test it again if we changed of state.
	    }
        return true;
    }

	/**
	 * Delete the none/stop jobs of the queue.
	 */
    public function garbageCollector() {
	    foreach($this->jobQueue as $idx=> &$job) {
		    if (get_class($job)=="eftec\statemachineone\Job") {
		    	if ($job->getActive()=='none' || $job->getActive()=='stop') {
		    		$this->removeJob($job);
			    }
		    }
	    }
    }

    /**
     * It changes the state of a job manually.
     * It changes the state manually.
     * @param Job $job
     * @param mixed $newState
     * @return bool true if the operation was succesful, otherwise (error) it returns false
     */
    public function changeState(Job $job,$newState) {
        if ($this->callChangeStateTrigger($job,$newState)) {
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
	 * @param Job $job
	 * @throws \Exception
	 */
	public function deleteJobDB(Job $job) {
    	$this->getDB()
		    ->from($this->tableJobs)
		    ->where('idjob=?',[$job->idJob])
		    ->delete();
    }


	/**
	 * We check if the states are consistency. It is only for testing.
	 * @test void this()
	 * @param bool $output if true then it echo the result
	 * @return bool
	 */
    public function checkConsistency($output=true) {
        $arr=array_keys($this->states);
        $arrCopy=$arr;
        if($output) echo "<hr>checking:<hr>";
        $result=true;
        foreach($this->transitions as $trId=>$trans) {
        	$name0=$this->states[$trans->state0];
	        $name1=$this->states[$trans->state1];
	        if($output) echo "CHECKING: <b>{$name0}</b>-><b>{$name1}</b>: ";
            $fail=false;
            if (!in_array($trans->state0,$arr)) {
                $fail=true;
	            $result=false;
                if($output) echo "ERROR: Transition <b>{$name0}</b> -> <b>{$name1}</b> with missing initial state<br>";
            } else {
                $arrCopy[]=$trans->state0;
            }
            if (!in_array($trans->state1,$arr)) {
                $fail=true;
	            $result=false;
	            if($output) echo "ERROR: Transition <b>{$name0}</b> -> <b>{$name1}</b> with missing ending state<br>";
            } else {
                $arrCopy[]=$trans->state1;
            }
            // checking if the fields exists
	        for($e=0;$e<count($trans->logic);$e+=4) {
	        	$logic=$trans->logic[$e+1];
	        	if ($logic==='wait') break;
		        $logic2=$trans->logic[$e+3];
	        	if((ctype_alpha($logic[0]) && strpos($logic,'()')===false)) {
	        		if(!array_key_exists($logic,$this->fieldDefault)) {
				        $fail=true;
				        $result=false;
				        if($output) echo "ERROR: field [{$logic}] in transaction #{$trId} doesn't exist<br>";
			        }
		        }
		        if((ctype_alpha($logic2[0]) && strpos($logic2,'()')===false)) {
			        if(!array_key_exists($logic2,$this->fieldDefault)) {
				        $fail=true;
				        $result=false;
				        if($output) echo "ERROR: second field [{$logic2}] in transaction #{$trId} doesn't exist<br>";
			        }
		        }
	        }
	        if ($trans->set!==null) {
		        for ($e = 0; $e < count($trans->set); $e += 4) {
			        $logic = $trans->set[$e + 1];
			        $logic2 = $trans->set[$e + 3];
			        if ((ctype_alpha($logic[0]) && strpos($logic, '()') === false)) {
				        if (!array_key_exists($logic, $this->fieldDefault)) {
					        $fail = true;
					        $result = false;
					        if ($output) echo "ERROR: field [{$logic}] in transaction #{$trId} doesn't exist<br>";
				        }
			        }
			        if ((ctype_alpha($logic2[0]) && strpos($logic2, '()') === false)) {
				        if (!array_key_exists($logic2, $this->fieldDefault)) {
					        $fail = true;
					        $result = false;
					        if ($output) echo "ERROR: second field [{$logic2}] in transaction #{$trId} doesn't exist<br>";
				        }
			        }
		        }
	        }
            if (!$fail) {
	            if($output) echo "OK<br>";
            }
        }
        foreach($arr as $missing) {
            if (!in_array($missing,$arrCopy)) {
	            $result=false;
	            if($output) echo "State: {$missing} not used<br>";
            }
        }

        return $result;
    }

	//<editor-fold desc="UI">

	public function fetchUI() {
		$job=$this->getLastJob();


		// fetch values
		$button=@$_REQUEST['frm_button'];
		$buttonEvent=@$_REQUEST['frm_button_event'];
		$new_state=@$_REQUEST['frm_new_state'];
		$msg="";
		$fetchField=$this->fieldDefault;
		foreach ($this->fieldDefault as $colFields=>$value) {
			if (isset($_REQUEST['frm_' . $colFields])) {
				$fetchField[$colFields] = @$_REQUEST['frm_' . $colFields];
				$fetchField[$colFields] =($fetchField[$colFields]==="")?null:$fetchField[$colFields];
			}
		}
		if ($buttonEvent) {
			$this->callEvent($buttonEvent);
			$msg="Event $buttonEvent called";
		}

		switch ($button) {
			case 'create':
				$this->createJob($fetchField);
				$msg="Job created";
				break;
			case 'delete':
				if ($job!=null) {
					$job->setActive('none');
					$job->isUpdate=true;
					//$this->saveDBJob($job);
					try {
						$this->deleteJobDB($job);
						$msg="Job deleted";
					} catch (\Exception $e) {
						$msg="Error deleting the job ".$e->getMessage();
					}
					$this->removeJob($job);
				}

				break;
			case 'change':
				$this->changeState($job,$new_state);
				if ($job->getActive()=="none" || $job->getActive()=="stop") {
					$job->setActive('active'); // we change the state to active.
				}
				$this->saveDBJob($job);
				$msg="State changed";
				break;
			case 'setfield':
				if ($job!==null) {
					$job->fields=$fetchField;
					$job->isUpdate=true;
					$this->saveDBJob($job);
					$msg="Job updated";

				}
				break;
			case 'check':
				$this->checkConsistency();
				break;
		}
		return $msg;
	}

	/**
	 * View UI (for testing). It is based on ChopSuey.
	 * @param Job $job
	 * @param string $msg
	 */
	public function viewUI($job=null,$msg="") {
		$job=($job===null)?$this->getLastJob():$job;
		$idJob=($job===null)?"??":$job->idJob;

		echo "<!doctype html>";
		echo "<html lang='en'>";
		echo "<head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>";
		echo '<link rel="stylesheet" href="http://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">';
		echo "<title>StateMachineOne Version ".$this->VERSION."</title>";
		echo "<style>html { font-size: 14px; }</style>";
		echo "</head><body>";

		echo "<div class='container-fluid'><div class='row'><div class='col'><br>";
		echo '<div class="card">';
		echo '<h5 class="card-header bg-primary text-white">';
		echo 'StateMachineOne Version '.$this->VERSION.' Job #'.$idJob.' Jobs in queue: '.count($this->getJobQueue()).'</h5>';
		echo '<div class="card-body">';
		echo "<form method='post'>";

		if ($msg!="") {
			echo '<div class="alert alert-primary" role="alert">'.$msg.'</div>';
		}

		if ($job===null) {
			echo "<h2>There is not a job active</h2><br>";
			$job=new Job();
			$job->fields=$this->fieldDefault;

		}
		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Job #</label>";
		echo "<div class='col-sm-10'><span>".$job->idJob."</span></br>";
		echo "</div></div>";

		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Current State</label>";
		echo "<div class='col-sm-10'><span class='badge badge-primary'>".@$this->getStates()[$job->state]." (".$job->state.")</span></br>";
		echo "</div></div>";

		$tr=[];
		foreach($this->transitions as $tran) {
			if ($tran->state0==$job->state) {
				$tr[]="<span class='badge badge-primary' title='{$tran->txtCondition}'>".@$this->getStates()[$tran->state1]." (".$tran->state1.")</span>";
			}
		}

		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Possible next states</label>";
		echo "<div class='col-sm-10'><span >".implode(', ',$tr)."</span></br>";
		echo "</div></div>";


		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Current Active state</label>";
		echo "<div class='col-sm-10'><span class='badge badge-primary'>".$job->getActive()." (".$job->getActiveNumber().")"."</span></br>";
		echo "</div></div>";


		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Elapsed full (sec)</label>";
		echo "<div class='col-sm-10'><span>".gmdate("H:i:s",(time()-$job->dateInit))."</span></br>";
		echo "</div></div>";

		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Elapsed last state (sec)</label>";
		echo "<div class='col-sm-10'><span>".gmdate("H:i:s",(time()-$job->dateLastChange))."</span></br>";
		echo "</div></div>";

		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Change State</label>";
		echo "<div class='col-sm-8'><select class='form-control' name='frm_new_state'>";
		foreach($this->states as $k=>$s) {
			if ($job->state==$k) {
				echo "<option value='$k' selected>$s</option>\n";
			} else {
				echo "<option value='$k'>$s</option>\n";
			}
		}
		echo "</select></div>";
		echo "<div class='col-sm-2'><button class='btn btn-success' name='frm_button' type='submit' value='change'>Change State</button></div>";
		echo "</div>";

		echo "<div class='form-group'>";
		echo "<button class='btn btn-primary' name='frm_button' type='submit' value='refresh'>Refresh</button>&nbsp;&nbsp;&nbsp;";
		echo "<button class='btn btn-primary' name='frm_button' type='submit' value='setfield'>Set field values</button>&nbsp;&nbsp;&nbsp;";
		echo "<button class='btn btn-success' name='frm_button' type='submit' value='create'>Create a new Job</button>&nbsp;&nbsp;&nbsp;";


		echo "<button class='btn btn-warning' name='frm_button' type='submit' value='check'>Check consistency</button>&nbsp;&nbsp;&nbsp;";
		echo "<button class='btn btn-danger' name='frm_button' type='submit' value='delete'>Delete this job</button>&nbsp;&nbsp;&nbsp;";
		echo "</div>";

		echo "<div class='form-group row'>";
		echo "<label class='col-sm-2 col-form-label'>Events</label>";
		echo "<div class='col-sm-10'><span>";
		foreach($this->events as $k=>$v) {
			echo "<button class='btn btn-primary' name='frm_button_event' type='submit' value='$k' title='".implode(' ',$v)."'>$k</button>&nbsp;&nbsp;&nbsp;";
		}
		echo "</span></br>";
		echo "</div></div>";


		foreach ($this->fieldDefault as $colFields=>$value) {
			echo "<div class='form-group row'>";
			echo "<label class='col-sm-2 col-form-label'>$colFields</label>";
			echo "<div class='col-sm-10'>";
			echo "<input class='form-control' autocomplete='off' type='text'
			name='frm_$colFields' value='" .htmlentities($job->fields[$colFields]) . "' /></br>";
			echo "</div>";
			echo "</div>";
		}


		echo "</form>";
		echo "</div>";
		echo "</div></div>"; //card
		echo "</div><!-- col --></div><!-- row -->";
		echo "</body></html>";
	}

	//</editor-fold>

	//<editor-fold desc="setter and getters">

	/**
	 * if true then the jobs are cleaned out of the queue when they are stopped.
	 * @return bool
	 */
	public function isAutoGarbage()
	{
		return $this->autoGarbage;
	}

	/**
	 * It sets if the jobs must be clean automatically each time the job is stopped
	 * @param bool $autoGarbage
	 */
	public function setAutoGarbage($autoGarbage)
	{
		$this->autoGarbage = $autoGarbage;
	}

	/**
	 * Returns true if the database is active
	 * @return bool
	 */
	public function isDbActive()
	{
		return $this->dbActive;
	}

	/**
	 * It sets the database as active. When we call setDb() then it is set as true automatically.
	 * @param bool $dbActive
	 */
	public function setDbActive($dbActive)
	{
		$this->dbActive = $dbActive;
	}

	/**
	 * Returns true if is in debug mode.
	 * @return bool
	 */
	public function isDebug()
	{
		return $this->debug;
	}

	/**
	 * Set the debug mode. By default the debug mode is false.
	 * @param bool $debug
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;
	}



	/**
	 * Returns the job queue.
	 * @return Job[]
	 */
	public function getJobQueue()
	{
		return $this->jobQueue;
	}

	/**
	 * Set the job queue
	 * @param Job[] $jobQueue
	 */
	public function setJobQueue(array $jobQueue)
	{
		$this->jobQueue = $jobQueue;
	}

	/**
	 * @param int $defaultInitState
	 */
	public function setDefaultInitState($defaultInitState)
	{
		$this->defaultInitState = $defaultInitState;
	}

	/**
	 * Gets an array with the states
	 * @return array
	 */
	public function getStates()
	{
		return $this->states;
	}

	/**
	 * Set the array with the states.
	 * @param array $states  It could be an associative array (1=>'state name',2=>'state') or a numeric array (1,2)
	 */
	public function setStates(array $states)
	{
		if ($this->isAssoc($states)) {
			$this->states = $states;
		} else {
			// it converts into an associative array
			$this->states = array_combine($states,$states);
		}
	}
	private function isAssoc(array $arr)
	{
		if (array() === $arr) return false;
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	/**
	 * It sets the method called when the job change state
	 * @param callable $changeStateTrigger
	 * @param string $when=['after','before','instead'][$i]
	 */
	public function setChangeStateTrigger(callable $changeStateTrigger,$when='after')
	{
		$this->changeStateTrigger = $changeStateTrigger;
		$this->changeStateTriggerWhen=$when;
	}
	public function callChangeStateTrigger(Job $job,$newState) {
		return call_user_func($this->changeStateTrigger,$this,$job,$newState);
	}
	/**
	 * It sets the method called when the job starts
	 * @param string $when=['after','before','instead'][$i]
	 * @param callable $startTrigger
	 */
	public function setStartTrigger(callable $startTrigger,$when='after')
	{
		$this->startTrigger = $startTrigger;
		$this->startTriggerWhen=$when;
	}
	public function callStartTrigger($job) {
		return call_user_func($this->startTrigger,$this,$job);
	}

	/**
	 * It sets the method called when job is paused
	 * @param callable $pauseTrigger
	 * @param string $when=['after','before','instead'][$i]
	 */
	public function setPauseTrigger(callable $pauseTrigger,$when='after')
	{
		$this->pauseTrigger = $pauseTrigger;
		$this->pauseTriggerWhen=$when;
	}
	public function callPauseTrigger($job) {
		return call_user_func($this->pauseTrigger,$this,$job);
	}
	/**
	 * It sets the method called when the job stop
	 * @param callable $stopTrigger
	 * @param string $when=['after','before','instead'][$i]
	 * @test void this(),'it must returns nothing'
	 */
	public function setStopTrigger(callable $stopTrigger,$when='after')
	{
		//function(StateMachineOne $smo,Job $job) { return true; }
		$this->stopTrigger = $stopTrigger;
		$this->stopTriggerWhen=$when;
	}
	public function callStopTrigger($job) {
		return call_user_func($this->stopTrigger,$this,$job);
	}

	/**
	 * It sets a function to returns the number of the process. By default, it is obtained by the database
	 * or via an internal counter.
	 * @param callable $getNumberTrigger
	 */
	public function setGetNumberTrigger(callable $getNumberTrigger)
	{
		$this->getNumberTrigger = $getNumberTrigger;
	}

	/**
	 * @return Transition[]
	 */
	public function getTransitions()
	{
		return $this->transitions;
	}

	//</editor-fold>


}

