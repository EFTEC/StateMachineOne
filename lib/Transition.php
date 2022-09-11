<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */

namespace eftec\statemachineone;

use eftec\minilang\MiniLang;

/**
 * Class Transition
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  1.5 2021-07-03
 * @link     https://github.com/EFTEC/StateMachineOne
 */
class Transition
{
    /** @var string|int|null */
    public $state0;
    /** @var string|int|null */
    public $state1;
    /** @var callable */
    public $function;
    /** @var string */
    public $txtCondition;
    /** @var callable|mixed */
    public $conditions;
    /** @var string=['change','pause','continue','stop','stay','stayonce'][$i] */
    public $result = '';
    /** @var MiniLang */
    public $miniLang;
    /** @var StateMachineOne */
    public $caller;
    /** @var Job used to determine the current time */
    public $currentJob;
    /**
     * @var int|array Maximum duration (in seconds) of this transition. If the time it's up, then the transition is
     *      executed
     */
    private $duration = 2000000;
    /**
     * @var int|array Maximum duration (in second) considering the whole job. If the time it's up then this transitin
     *      is done
     */
    private $fullDuration = 2000000;

    /**
     * Transition constructor.<br>
     * The transition is a definition of a state, but it also allows evaluating and execute a swith of states<br>
     * The transition allows moving one job from one state to another if happens some conditions.<br>
     * It also allows to do some operation such as to pause, stay and stop (end of the workflow)<br>
     * <b>Example:</b><br>
     * <pre>
     * $a1=new Transition($caller,'initialstate','endstate','if condition=1','change')
     * </pre>
     *
     * @param StateMachineOne $caller     The caller (usually, a statemachine instance).
     * @param string|int|null $state0     The initial state
     * @param string|int|null $state1     The end state. It could be the same initial state.
     * @param mixed           $conditions The code with the conditions. The conditions are expressed by MiniLang.
     * @param string          $result     =['change','pause','continue','stop','stay'][$i]
     * @param bool            $storeClass If true, then it doesn't change the state, but it stores the operation in
     *                                    MiniLang. It is useful if you want to create a MiniLang class.
     * @see https://github.com/EFTEC/MiniLang for more information about conditions.
     */
    public function __construct(StateMachineOne $caller, $state0, $state1, $conditions
        , string                                $result = '', bool $storeClass = false)
    {
        $this->caller = $caller;
        $this->state0 = $state0;
        $this->state1 = $state1;
        $this->result = $result;
        if (is_callable($conditions)) {
            $this->txtCondition = 'custom function()';
            $this->function = $conditions;
        }
        if (is_string($conditions)) {
            $this->txtCondition = $conditions;
            if (!$this->caller->miniLang->usingClass) {
                // we need to evaluate the code (otherwise, we do nothing)
                if ($storeClass) {
                    // we generate php code
                    $this->caller->miniLang->separate2($conditions); // this process could be a bit expensive.
                } else {
                    // we parse the code in runtime.
                    $this->caller->miniLang->separate($conditions); // this process could be a bit expensive.
                }
            }
            if (isset($this->caller->miniLang->areaValue['timeout'])) {
                $this->duration = $this->caller->miniLang->areaValue['timeout'];
            }
            if (isset($this->caller->miniLang->areaValue['fulltimeout'])) {
                $this->fullDuration = $this->caller->miniLang->areaValue['fulltimeout'];
            }
        }
    }


    /**
     * @param StateMachineOne $smo
     * @param Job             $job
     * @param int             $idTransition number of the transition.
     *
     * @return bool
     */
    public function evalLogic(StateMachineOne $smo, Job $job, int $idTransition): bool
    {
        $this->caller->miniLang->setDictEntry('_idjob',$job->idJob);
        $this->caller->miniLang->setDictEntry('_time',$this->caller->getTime());
        $r = $this->caller->miniLang->evalLogic($idTransition);
        //echo "<br>eval:<br>";
        //var_dump($this->caller->miniLang->errorLog);
        if ($r === 'wait') {
            return false;
        } // wait
        if ($this->result === 'stayonce' && isset($job->transitions[$idTransition])) {
            return false; // transition was already done.
        }
        if ($r) {
            return $this->doTransition($smo, $job, false, $idTransition);
        }
        $this->caller->miniLang->evalSet($idTransition, 'else');
        return false;
    }

    /**
     * It does the transition unless it is stopped or the active status is not compatible.
     *
     * @param StateMachineOne $smo
     * @param Job             $job
     * @param bool            $forced If true then the transition is done whatever the active status (unless it is stop)
     * @param int             $numTransaction
     *
     * @return bool True if the transition is done, otherwise false.
     */
    public function doTransition(StateMachineOne $smo, Job $job, bool $forced = false, int $numTransaction = 0): bool
    {
        $ga = $job->getActive();
        if ($ga === 'stop') {
            return false;
        }
        $this->currentJob = $job;
        $job->transitions[$numTransaction] = true;
        // we set some variables that we could use in our code minilang.
        $this->caller->miniLang->setDictEntry('_result',$this->result);
        $this->caller->miniLang->setDictEntry('_state0',$this->state0);
        $this->caller->miniLang->setDictEntry('_state1',$this->state1);

        switch ($this->result) {
            case 'change':
                if ($forced || $ga === 'active') { // we only changed if the job is active.
                    $smo->changeState($job, $this->state1);
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    $smo->addLog($job, 'INFO', 'TRANSITION', 'state,,changed,,'
                        . $smo->getStates()[$this->state0] . ",,$this->state0,,"
                        . $smo->getStates()[$this->state1] . ",,$this->state1,,$numTransaction,,$this->result");
                    return true;
                }
                break;
            case 'stay':
                if ($forced || $ga === 'active') { // we keep the current state
                    //$smo->changeState($job, $this->state1);
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    //$smo->addLog($job, "INFO", "state <b>stay</b> in "
                    //    .$smo->getStates()[$this->state0]."($this->state0) $this->result");
                    return true;
                }
                break;
            case 'pause':
                if ($ga === 'active' || $ga === 'pause' || $forced) { // we only changed if the job is paused or active.
                    if ($smo->pauseTriggerWhen === 'instead') {
                        return $smo->callPauseTrigger($job);
                    }
                    if ($smo->pauseTriggerWhen === 'before') {
                        $smo->callPauseTrigger($job);
                    }
                    $smo->changeState($job, $this->state1);
                    $job->setActive('pause');
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    $smo->addLog($job, 'INFO', 'TRANSITION', 'state,,changed,,'
                        . $smo->getStates()[$this->state0] . ",,$this->state0,,"
                        . $smo->getStates()[$this->state1] . ",,$this->state1,,$numTransaction,,$this->result");
                    if ($smo->pauseTriggerWhen === 'after') {
                        $smo->callPauseTrigger($job);
                    }
                    return true;
                }
                break;
            case 'continue':
                if ($ga === 'pause' || $ga === 'active' || $forced) { // we only changed if the job is active or paused
                    $smo->changeState($job, $this->state1);
                    $job->setActive();
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    $smo->addLog($job, 'INFO', 'TRANSITION', 'state,,continue,,'
                        . $smo->getStates()[$this->state0] . ",,$this->state0,,"
                        . $smo->getStates()[$this->state1] . ",,$this->state1,,$numTransaction,,$this->result");
                    return true;
                }
                break;
            case 'stop':
                if ($ga === 'active' || $ga === 'pause' || $forced) { // we only changed if the job is paused or active.
                    $smo->changeState($job, $this->state1);
                    $job->setActive('stop');
                    $this->caller->miniLang->evalSet($numTransaction);
                    //if ($smo->isDbActive()) $smo->saveDBJob($job);
                    $smo->addLog($job, 'INFO', 'TRANSITION', 'state,,stop,,'
                        . $smo->getStates()[$this->state0] . ",,$this->state0,,"
                        . $smo->getStates()[$this->state1] . ",,$this->state1,,$numTransaction,,$this->result");
                    $smo->callStopTrigger($job);
                    if ($smo->isAutoGarbage()) {
                        $smo->garbageCollector(); // job done, deleting from the queue.
                    }
                    return true;
                }
                break;
            default:
                trigger_error("Error: Result of transition $this->result not defined");
        }
        return false;
    }

    /**
     * It returns the full duration of the job.
     * @param Job $job
     * @return int|null
     */
    public function getFullDuration(Job $job)
    {
        if (is_numeric($this->fullDuration)) {
            return $this->fullDuration;
        }
        //return $this->caller->miniLang->getValue($this->fullDuration[0]
        //    , $this->fullDuration[1], $this->fullDuration[2]
        //    , $job, $job->fields);
        return null;
    }

    /**
     * if the value of duration is numeric then it's not calculated. Otherwise, it is calculated using the job.
     * @param Job $job
     * @return int|null
     */
    public function getDuration(Job $job)
    {
        if (is_numeric($this->duration)) {
            return $this->duration;
        }
        //return $this->caller->miniLang->getValue($this->duration[0]
        //    , $this->duration[1], $this->duration[2]
        //    , $job, $job->fields);
        return null;
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

    /**
     * @return string
     */
    public function getTxtCondition(): string
    {
        return $this->txtCondition;
    }

    /**
     * @param string|int|null $state0
     * @return Transition
     */
    public function setState0($state0): Transition
    {
        $this->state0 = $state0;
        return $this;
    }

    /**
     * @param string|int|null $state1
     * @return Transition
     */
    public function setState1($state1): Transition
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


}
