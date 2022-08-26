<?php /** @noinspection UnknownInspectionInspection */
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
 * The flags are useful when we want to execute something only once<br>
 *
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  2.15 2021-09-18
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
    private $level = [];
    /** @var int[] (time of expiration, if -1 then it does not expire) */
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
     * @param bool                 $changed true if the container has changed.
     * @param StateMachineOne|null $stateMachine The state machine related with the flag.
     */
    public function __construct($name = null, $changed = true, $stateMachine = null)
    {
        $this->name = $name;
        $this->changed = $changed;
        $this->caller = $stateMachine;
    }

    /**
     * Creates a flag from a job and a string
     * @param Job    $job
     * @param string $string a serialized string
     *
     * @return Flags
     */
    public static function factory($job, $string): Flags
    {
        $obj = new Flags();
        $obj->fromString($job, $string);
        return $obj;
    }

    /**
     * Creates a flag from a job and a string
     *
     * @param Job    $job
     * @param String $string a serialized string containing the information of the flag.
     *
     * @return void
     */
    public function fromString($job, $string):void
    {
        try {
            $this->parentJob = $job;
            $arr = @unserialize($string,['allowed_classes'=>true]);
            $this->__unserialize($arr);

        } catch (Exception $ex) {
            $this->cleanAllFlag();
        }
    }

    public function __unserialize($arr):void
    {
        $this->stack = $arr['stack'];
        $this->stackId = $arr['stackId'];
        $this->timeExpire = $arr['timeExpire'];
        $this->check(); // check if the flag has expired or not.
        $this->level = $arr['level'];
        $this->changed = (@$arr['changed'] == 1);
    }

    /**
     * It checks if the flags are expired.  If they are expired, then they are pulled out.
     */
    public function check(): void
    {
        $now = $this->getTime();
        $keys = array_keys($this->stack);
        foreach ($keys as $idx) {
            if ($this->timeExpire[$idx] != -1 && $this->timeExpire[$idx] < $now) {
                $this->pull($idx);
            }
        }
    }

    public function getTime($microtime = false)
    {
        if ($this->caller) {
            return $this->caller->getTime($microtime);
        }
        return $microtime ? microtime(true) : time();
    }

    /**
     * It pulls or remove a flag identified by an idUnique.<br>
     * <b>Example:</b><br>
     * <pre>
     * pull('light'); // It removes the flag light
     * pull('light','lights out',2); // It removes the flag light with message light out and level 0
     * pull('light','lights out',2,400); // It removes the flag light #400 with message light out and level 0
     * </pre>
     * @param string $idUnique This value is used to identify each flag
     *
     * @param string $msg      (optional) used for log
     * @param int    $level    (optional) used for log
     * @param int    $idRel    (optional) ID related (for example, the id of the job) used for log
     *
     * @return $this
     */
    public function pull($idUnique = '', $msg = '', $level = 0, $idRel = 0): self
    {
        if (isset($this->stack[$idUnique])) {
            unset($this->stack[$idUnique], $this->stackId[$idUnique], $this->timeExpire[$idUnique]);
            if ($this->parentJob) {
                $this->caller->addLog($this->parentJob, $this->name, 'PULL'
                    , "flag,,changed,,$idUnique,,$idRel,,$level,,$msg", $idRel);
            }
        }
        return $this;
    }

    /**
     * It cleans all the flags and reset the level to zero.
     *
     * @return $this
     */
    public function cleanAllFlag(): self
    {
        $this->changed = true;
        $this->stack = [];
        $this->stackId = [];
        $this->timeExpire = [];
        $this->level = [];
        return $this;
    }

    // who (job1), whom (idrel), when (date), result(text),actiondone=push, level=warning, from=value, to=value

    /**
     * It retruns a serialized string with the information of the flag
     *
     * @return string
     */
    public function toString():string
    {
        return serialize($this->__serialize()); //4
    }

    /**
     * It returns an associative array with the information of the flag.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return ['stack' => $this->stack
            , 'stackId' => $this->stackId
            , 'timeExpire' => $this->timeExpire
            , 'level' => $this->level
            , 'changed' => ($this->changed ? 1 : 0)];
    }

    /**
     * It pushes a new flag under a specific identifier.<br>
     * <b>Example:</b><br>
     * <pre>
     * push('light','ON',2); // It adds a flag in 'lights' (level 2)
     * push('light','ON',2); // It does nothing because the id/message/level are the same.
     * push('light','OFF',2); // It replaces the other flag (level 2)
     * push('siren','new message',2); // It adds a flag in 'siren'
     * push('info','work done',0,60); // It adds a message work done, level 0 and last 60 seconds
     * push('info','work done',0,60,23); // Work #23 done
     * </pre>
     *
     * @param string|int $idUnique   This value is used to identify each flag
     * @param string     $msg        Message (or value) of the flag.
     * @param int        $level      The level of the flag. The context of the flag
     *                               is defined by each application
     * @param int        $timeExpire (optional) The time (in seconds) this process will expire and
     *                               it will be self deleted. -1 means no expiration.
     * @param int|null   $idRel      (optional) The ID of the relation of "to whom is the flag".
     *
     * @return $this
     */
    public function push($idUnique = 0, $msg = '', $level = 0, $timeExpire = -1, $idRel = 0): self
    {
        if (isset($this->stack[$idUnique]) && @$this->stackId[$idUnique] == $idRel
            && $this->stack[$idUnique] == $msg && $this->level[$idUnique] == $level
        ) {
            // it's the same state, we do nothing (the time could change)
            return $this;
        }
        @$this->stack[$idUnique] = $msg;
        @$this->stackId[$idUnique] = $idRel;
        if ($timeExpire === -1) {
            @$this->timeExpire[$idUnique] = -1;
        } else {
            @$this->timeExpire[$idUnique] = $this->getTime() + $timeExpire;
        }
        $this->level[$idUnique] = $level;
        $this->changed = true;
        if ($this->parentJob && $this->caller) {
            $this->caller->addLog($this->parentJob, $this->name, 'PUSH', "flag,,changed,,$idUnique,,$level,,$idRel,,$msg",$idRel);
        }
        return $this;
    }
    public function message($msg = ''): Flags
    {
        if (isset($this->stack['_msg']) && $this->stack['_msg'] == $msg
        ) {
            // it avoids adding the same message if the last one has the same message.
            return $this;
        }
        $this->stack['_msg'] = $msg;
        $this->stackId['_msg'] = ($this->parentJob!==null) ? $this->parentJob->idJob : 0;
        $this->timeExpire['_msg']=-1; // messages never expires.
        $this->level['_msg'] = 0;
        $this->changed = true;
        if ($this->parentJob && $this->caller) {
            $this->caller->addLog($this->parentJob, $this->name, 'MESSAGE', $msg,$this->stackId['_msg']);
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
    public function flagexist($idUnique): bool
    {
        return isset($this->stack[$idUnique]);
    }

    /**
     * It returns the current stack of flags. It returns all the flags inside the container.
     *
     * @return array|null
     */
    public function getStack(): ?array
    {
        return $this->stack;
    }

    /**
     * Get a flag as an associative array (or null if the flag does not exist).
     * @param int $idUnique
     *
     * @return array|null=['flag','id','level','time']
     */
    public function getFlag($idUnique): ?array
    {
        if (isset($this->stack[$idUnique])) {
            return ['flag' => $this->stack[$idUnique]
                , 'id' => $this->stackId[$idUnique]
                , 'level' => $this->level[$idUnique]
                , 'time' => $this->timeExpire[$idUnique]];
        }
        return null;
    }

    /**
     * It returns the min level of the whole container.
     *
     * @return int
     */
    public function getMinLevel(): int
    {
        return (count($this->level)) ? min($this->level) : 0;
    }

    /**
     * It returns the max level of the whole container
     *
     * @return int
     */
    public function getMaxLevel(): int
    {
        return (count($this->level)) ? max($this->level) : 0;
    }

    /**
     * It sets the parent job.
     *
     * @param Job $job
     *
     * @return $this
     */
    public function setParent($job):self
    {
        $this->parentJob = $job;

        return $this;
    }

    /**
     * It sets the caller. Usually it is the state machine.
     *
     * @param $stateMachineOne
     * @return $this
     */
    public function setCaller($stateMachineOne): self
    {
        $this->caller = $stateMachineOne;
        return $this;
    }
}
