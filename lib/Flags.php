<?php /** @noinspection PhpUnusedParameterInspection */
/** @noinspection TypeUnsafeComparisonInspection */

/** @noinspection PhpUnused */

namespace eftec\statemachineone;

use Exception;

/**
 * Class Flags
 * It is a container to manage flags of states.<br>
 * It could be used to set some states that could change, for example if some value must be on/off
 * ,or if the system must store or show an error<br>
 * It is possible to push (add) a flag or pull (remove) a flag</b>
 *
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  1.9 2019-08-17
 * @link     https://github.com/EFTEC/StateMachineOne
 */
class Flags implements StateSerializable
{
    /** @var string It is the name (or id) of the container. */
    private $name;
    /** @var array|null */
    private $stack = [];
    private $stackId = [];
    /** @var array|null */
    private $level=[];
    /** @var int[] (time of expiration, if -1 then it does not expires) */
    private $timeExpire = [];
    /** @var bool if true then the container has changed */
    private $changed;
    /** @var null|StateMachineOne The statemachine caller. It is used for callbacks */
    private $caller;
    /** @var Job|null The job related with the flag. It is used for callbacks */
    private $parentJob;

    /**
     * Flags constructor.
     *
     * @param mixed                $name Name of the container of flags
     * @param bool                 $changed
     * @param StateMachineOne|null $stateMachine
     */
    public function __construct($name = null, $changed = true, $stateMachine = null)
    {
        $this->name = $name;
        $this->changed = $changed;
        $this->caller = $stateMachine;
    }

    /**
     * It checks if the flags are expired.  If they are expired, then they are pulled out.
     */
    public function check()
    {
        $now = $this->getTime();
        $keys = array_keys($this->stack);
        foreach ($keys as $idx) {
            if ($this->timeExpire[$idx] != -1 && $this->timeExpire[$idx] < $now) {
                $this->pull($idx);
            }
        }
    }

    /**
     * It retruns a serialized string with the information of the flag
     *
     * @return string
     */
    public function toString()
    {
        return serialize($this->__serialize()); //4
    }

    /**
     * It returns an associative array with the information of the flag.
     *
     * @return array
     */
    public function __serialize() {
        return ['stack'=>$this->stack
                ,'stackId'=>$this->stackId
                ,'timeExpire'=>$this->timeExpire
                ,'level'=>$this->level
                ,'changed'=>($this->changed ? 1 : 0)];
    }
    public function __unserialize($arr) {
        $this->stack = $arr['stack'];
        $this->stackId =$arr['stackId'];
        $this->timeExpire = $arr['timeExpire'];
        $this->check(); // check if the flag has expired or not.
        $this->level = $arr['level'];
        $this->changed = (@$arr['changed'] == 1);
    }

    /**
     * Creates a flag from a job and a string
     *
     * @param Job    $job
     * @param String $string a serialized string
     *
     * @return mixed|void
     */
    public function fromString($job,$string)
    {
        try {
            $this->parentJob = $job;
            $arr = @unserialize($string);
            $this->__unserialize($arr);

        } catch(Exception $ex) {
            $this->cleanAllFlag();
        }
    }

    /**
     * Creates a flag from a job and a string
     * @param Job $job
     * @param string $string a serialized string
     *
     * @return Flags
     */
    public static function factory($job,$string) {
        $obj=new Flags();
        $obj->fromString($job,$string);
        return $obj;
    }


    /**
     * It push a new flag under a specific identifier.
     * <p>push('light','somemessage',2); // It adds a flag in 'lights' (level 2)</p>
     * <p>push('light','new message',2); // It replaces the other flag (level 2)</p>
     * <p>push('siren','new message',2); // It adds a flag in 'siren'</p>
     * <p>push('info','work done',0,60); // It adds a message work done, level 0 and last 60 seconds</p>
     * <p>push('info','work done',0,60,23); // Work #23 done</p>
     *
     * @param string|int $idUnique   This value is used to identify each flag
     * @param string     $msg        Message (or value) of the flag.
     * @param int        $level      The level of the flag. The context of the flag
     *                               is defined by each application
     * @param int        $timeExpire (optional) The time (in seconds) this process will expire and
     *                               it will be self deleted. -1 means no expiration.
     * @param int|null   $idRel      (optional) Id of the relation of "to whom is the flag".
     *
     * @return $this
     */
    public function push($idUnique = 0, $msg = '', $level = 0, $timeExpire = -1, $idRel = 0)
    {
        if (isset($this->stack[$idUnique]) && @$this->stackId[$idUnique] == $idRel
            && $this->stack[$idUnique] == $msg
        ) {
            // it's the same state, we do nothing (the time could change)
            return $this;
        }
        @$this->stack[$idUnique] = $msg;
        @$this->stackId[$idUnique] = $idRel;
        if ($timeExpire === -1) {
            @$this->timeExpire[$idUnique] = -1;
        } else {
            @$this->timeExpire[$idUnique] = $this->getTime()+ $timeExpire;
        }
        $this->level[$idUnique] = $level;
        $this->changed = true;
        if ($this->parentJob && $this->caller) {
            $this->caller->addLog($this->parentJob, $this->name,'PULL', "flag,,changed,,$idUnique,,$idRel,,$msg");
        }
        return $this;
    }
    // who (job1), whom (idrel), when (date), result(text),actiondone=push, level=warning, from=value, to=value
    public function getTime($microtime=false) {
        if ($this->caller) {
            return $this->caller->getTime($microtime);
        }

        return $microtime?microtime(true):time();
    }

    /**
     * It pulls or remove a flag identified by an idUnique.
     * <p>pull('somemessage','light',2); // It adds a flag in 'lights'</p>
     * <p>pull('light'); // It removes the flag light</p>
     * <p>pull('light','lights out',2); // It removes the flag light with message light out and level 0</p>
     * <p>pull('light','lights out',2,400); // It removes the flag light #400 with message light out and level 0</p>
     *
     * @param string $idUnique This value is used to identify each flag
     *
     * @param string $msg      used for log
     * @param int    $level    used for log
     * @param int    $idRel    (of the relation) used for log
     *
     * @return $this
     */
    public function pull($idUnique = '',$msg = '', $level = 0, $idRel = 0)
    {
        if (isset($this->stack[$idUnique])) {
            unset($this->stack[$idUnique], $this->stackId[$idUnique], $this->timeExpire[$idUnique]);
            if ($this->parentJob) {
                $this->caller->addLog($this->parentJob,$this->name,'PULL', $msg,$idRel);
            }
        }
        return $this;
    }

    /**
     * It returns true if the flag exists, false if not.
     *
     * @param int $idUnique
     *
     * @return bool
     */
    public function flagexist($idUnique) {
        return isset($this->stack[$idUnique]);
    }

    /**
     * It cleans all the flags and reset the level to zero.
     *
     * @return $this
     */
    public function cleanAllFlag()
    {
        $this->changed = true;
        $this->stack = [];
        $this->stackId= [];
        $this->timeExpire = [];
        $this->level = [];
        return $this;
    }

    /**
     * @return array|null
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * Get a flag as a string (or null if the flag does not exist).
     * @param int $idUnique
     *
     * @return array|null=['flag','id','level','time']
     */
    public function getFlag($idUnique)
    {
        if(isset($this->stack[$idUnique])) {
            return ['flag'=>$this->stack[$idUnique]
                    ,'id'=>$this->stackId[$idUnique]
                    ,'level'=>$this->level[$idUnique]
                    ,'time'=>$this->timeExpire[$idUnique]];
        }
        return null;
    }

    /**
     * It returns the min level of the whole container.
     *
     * @return int
     */
    public function getMinLevel()
    {
        return (count($this->level))? min($this->level) : 0;
    }
    /**
     * It returns the max level of the whole container
     *
     * @return int
     */
    public function getMaxLevel()
    {
        return (count($this->level))? max($this->level) : 0;
    }

    /**
     * It sets the parent
     *
     * @param Job $job
     *
     * @return $this
     */
    public function setParent($job)
    {
        $this->parentJob=$job;
        
        return $this;
    }
    public function setCaller($stateMachineOne)
    {
        $this->caller=$stateMachineOne;
        return $this;
    }
}