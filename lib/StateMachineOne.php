<?php
namespace eftec\statemachineone;

use eftec\DaoOne;

/**
 * Class StateMachineOne
 * @package eftec\statemachineone
 */
class StateMachineOne {
    var $counter;
    /** @var StateMachineJob[] */
    var $jobs;
    /** @var int  */
    var $defaultInitState=0;
    
    var $dbServer="";
    var $dbUser="";
    var $dbPassword="";
    var $dbSchema="";
    var $tableJobs="stm_jobs";
    var $columnJobs=['idjob','active','state','dateinit','dateexpired','dateend'];
    /** @var string[] it could be changed it is the field of reference (it could be more than one reference) */
    var $idRef=['idref'];
    var $extraColumnJobs=['extra1'];
    
    // callbacks
    /** @var callable */
    var $changeStatefn;
    /** @var callable */
    var $checkStateFn;
    /** @var callable */
    var $startFn;
    /** @var callable This function increased in 1 the next id of the job */
    var $getNumberFn;

    
    /**
     * StateMachineOne constructor.
     */
    public function __construct()
    {
        // reset values
        $this->jobs=[];
        $this->counter=0;

        $this->changeStatefn=function(StateMachineOne $smo,$idJob,$oldState,$newState) {
            return true;
        };
        $this->checkStateFn=function(StateMachineOne $smo,$idJob) {
            return true;
        };
        $this->startFn=function(StateMachineOne $smo,$idJob,$job) {
            return true;
        };
        $this->getNumberFn=function(StateMachineOne $smo) {
            
            // you could use the database if you are pleased to.
            $smo->counter++;
            return $smo->counter;
        };

    }
    /** @var DaoOne */
    var $daoOne=null;
    
    public function getDB() {
        if ($this->daoOne==null) {
            $this->daoOne=new DaoOne($this->dbServer,$this->dbUser,$this->dbPassword,$this->dbSchema);
        }
        return $this->daoOne;
    }

    /**
     * @param $idJob
     * @throws \Exception
     */
    public function loadDBJob($idJob) {
        $row=$this->getDB()->select("*")->from($this->tableJobs)->where("idjob=?",[$idJob])->first();
        $this->jobs[$row['idjob']]=$this->arrayToStateMachineJob($row);
    }

    /**
     * It loads all (active|schedule|standby) jobs.
     * @throws \Exception
     */
    public function loadDBAllJob() {
        $rows=$this->getDB()->select("*")->from($this->tableJobs)->where("active<>0")->toList();
        $this->jobs=[];
        foreach($rows as $row) {
            $this->jobs[$row['idjob']]=$this->arrayToStateMachineJob($row);
        }
    }
    
    private function arrayToStateMachineJob($row) {
        $job=new StateMachineJob();
        $job->setIsUpdate(false)
            ->setIsNew(false)
            ->setActive($row['active'])
            ->setState($row['state'])
            ->setDateInit($row['dateinit'])
            ->setDateExpired($row['dateexpired'])
            ->setDateEnd($row['dateend']);
        $arr=[];
        foreach($this->idRef as $k=>$r) {
            $arr[$k]=$row[$r];
        }
        $job->setIdRef($arr);
        $arr=[];
        foreach($this->extraColumnJobs as $k=>$r) {
            $arr[$k]=$row[$r];
        }
        $job->setFields($arr);
        return $job;
    }

    /**
     * @param int $idJob
     * @param StateMachineJob $job
     * @return mixed
     */
    private function stateMachineJobToArray($idJob, $job) {
        $arr=[];
        $arr['idjob']=$idJob;
        $arr['active']=$job->active;
        $arr['state']=$job->state;
        $arr['dateinit']=$job->dateInit;
        $arr['dateexpired']=$job->dateExpired;
        $arr['dateend']=$job->dateEnd;
        foreach($this->idRef as $k=>$r) {
            $arr[$k]=$job->idRef[$k];
        }
        foreach($this->extraColumnJobs as $k=>$r) {
            $arr[$k]=$job->fields[$k];
        }        
        return $arr;
    }    

    /**
     * @param $idJob
     * @return mixed
     * @throws \Exception
     */
    public function saveDBJob($idJob) {
        if ($this->jobs[$idJob]->isNew) {
            $r=$this->getDB()
                ->from($this->tableJobs);
            $arr=$this->stateMachineJobToArray($idJob,$this->jobs[$idJob]);
            foreach($arr as $k=>$item) {
                $this->getDB()->set($k.'=?',$item);
            }
            $this->getDB()->insert();
            $this->jobs[$idJob]->isNew=false;
            return $r;
        } else if ($this->jobs[$idJob]->isUpdate) {
            $this->getDB()
                ->from($this->tableJobs);
            $arr=$this->stateMachineJobToArray($idJob,$this->jobs[$idJob]);
            foreach($arr as $k=>$item) {
                $this->getDB()->set($k.'=?',$item);
            }
            $this->getDB()->update();
            $this->jobs[$idJob]->isUpdate=false;
            return -1;
        }
    }

    /**
     * It only saves jobs that are new or updated.
     * @throws \Exception
     */
    public function saveDBAllJob() {
        foreach($this->jobs as $idJob=>$job) {
            $this->saveDBJob($idJob);
        }
    }

    /**
     * @param int[] $idRef
     * @param array $fields
     * @param mixed $initState
     * @param int|null $dateStart
     * @param int|null $dateEnd
     * @param int|null $dateExpire
     * @return int
     */
    public function createJob($idRef,$fields,$initState=null,$dateStart=null,$dateEnd=null,$dateExpire=null) {
        $initState=$initState===null?$this->defaultInitState:$initState;
        $dateStart=$dateStart===null?time():$dateStart;
        $dateEnd=$dateEnd===null?PHP_INT_MAX:$dateEnd;
        $dateExpire=$dateExpire===null?PHP_INT_MAX:$dateExpire;
        $idJob=call_user_func($this->getNumberFn,$this);
        $job=new StateMachineJob();
        $job->setIdRef($idRef)
            ->setDateInit($dateStart)
            ->setDateEnd($dateEnd)
            ->setDateExpired($dateExpire)
            ->setState($initState)
            ->setFields($fields)
            ->setIsNew(true)
            ->setIsUpdate(false);
        if ($dateStart<=time()) {
            // it start.
            call_user_func($this->startFn,$this,$idJob,$job);
            $job->setActive(1);
            
        } else {
            // it will start in the future.
            $job->setActive(-1);
        }
        $this->jobs[$idJob]=$job;
        return $this->counter;
    }

    /**
     * @param int $idJob
     * @return StateMachineJob
     */
    public function getJob($idJob) {
        return $this->jobs[$idJob];
    }
    
    public function checkState($idJob) {
        if (call_user_func($this->checkStateFn,$this,$idJob)) {
            return true;
        } else {
            return false;
        }
    }
    public function checkAllState() {
        foreach($this->jobs as $idx=>$job) {
            $this->checkState($idx);
        }
    }
    public function changeState($idJob,$newState) {

        if (call_user_func($this->changeStatefn,$this,$idJob,$this->jobs[$idJob]->state,$newState)) {
            $this->jobs[$idJob]->state = $newState;
            $this->addLog($idJob,'CHANGE',"Change state $idJob");
            return true;
        } else {
            $this->addLog($idJob,'ERROR',"Change state $idJob");
            return false; 
        }
    }
    public function addLog($idJob,$type,$description) {
        $this->jobs[$idJob]->log[]=['type'=>$type,'description'=>$description,'date'=>time()];
    }
}

