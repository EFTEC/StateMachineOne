<?php /** @noinspection PhpUnused */

namespace eftec\statemachineone;

/**
 * Class Flag
 * It is a container to manage flags of states.<br>
 * It could be used to set some states that could change, for example if some value must be on/off
 * ,or if the system must store or show an error<br>
 * The flag could be defined as an unique value <b>setflag()/setflagmin()/setflagmax()</b><br>
 * or as an collection of associative array <b>pushflag()/pullflag()</b>
 *
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version  1.8 2019-08-03
 * @link     https://github.com/EFTEC/StateMachineOne
 */
class Flag implements StateSerializable
{
    private $value;
    /** @var array|null */
    private $stack = null;
    private $currentLevel;
    private $changed = false;

    /**
     * Flag constructor.
     *
     * @param mixed $value
     * @param int   $currentLevel
     * @param bool  $changed
     */
    public function __construct($value = null, $currentLevel = 0, $changed = true)
    {
        $this->value = $value;
        $this->stack = null;
        $this->currentLevel = $currentLevel;
        $this->changed = $changed;
    }

    public function toString()
    {
        if ($this->stack !== null) {
            return serialize($this->stack) . ';;' . $this->currentLevel . ';;' . ($this->changed ? 1 : 0);
        } else {
            return $this->value . ';;' . $this->currentLevel . ';;' . ($this->changed ? 1 : 0);
        }

    }

    public function fromString($string)
    {
        $arr = explode(';;', $string);
        if (strpos(@$arr[0], 'a:') === 0) {
            $this->stack = unserialize($arr[0]);
        } else {
            $this->value = @$arr[0];
        }
        $this->currentLevel = @$arr[1];
        $this->changed = (@$arr[2] == 1);
    }

    /**
     * It sets a flag, replacing the previous value (if any)
     * <p>setflag('somemessage',2);<p>
     *
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflag($msg = "", $level = 0)
    {
        $this->value = $msg;
        $this->currentLevel = $level;
        $this->changed = true;
        return $this;
    }

    /**
     * It sets a flag only if the level is lower than the current level.
     * <p>setflagMin('somemessage',2);<p>
     *
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflagMin($msg = "", $level = 0)
    {
        if ($level < $this->currentLevel) {
            $this->value = $msg;
            $this->currentLevel = $level;
            $this->changed = true;
        }
        return $this;
    }

    /**
     * It sets a flag only if the level is higher that the current level
     * <p>setflagMax('somemessage',2);<p>
     *
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflagMax($msg = "", $level = 0)
    {
        if ($level > $this->currentLevel) {
            $this->value = $msg;
            $this->currentLevel = $level;
            $this->changed = true;
        }
        return $this;
    }

    /**
     * It push a new flag under a specific identifier.
     * <p>pushflag('somemessage','light',2); // It adds a flag in 'lights' (level 2)</p>
     * <p>pushflag('new message','light',2); // It replaces the other flag (level 2)</p>
     * <p>pushflag('new message','siren',2); // It adds a flag in 'siren'</p>
     *
     * @param string     $msg Message (or value) of the flag.
     * @param string|int $idUnique This value is used to identify each flag
     * @param int        $level The level of the flag. The context of the flag is defined by each application
     *
     * @return $this
     */
    public function pushFlag($msg = "", $idUnique = 0, $level = 0)
    {
        @$this->stack[$idUnique] = $msg;
        $this->currentLevel = $level;
        $this->changed = true;
        return $this;
    }

    /**
     * It pulls or remove a flag identified by an idUnique.
     * <p>pushFlag('somemessage','light',2); // It adds a flag in 'lights'</p>
     * <p>pullFlag('light'); // It removes the flag light</p>
     *
     * @param string $idUnique This value is used to identify each flag
     *
     * @return $this
     */
    public function pullFlag($idUnique = "")
    {
        unset($this->stack[$idUnique]);
        return $this;
    }

    /**
     * It cleans all the flags and reset the level to zero.
     *
     * @return $this
     */
    public function cleanFlag()
    {
        $this->value = "";
        $this->currentLevel = 0;
        $this->changed = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return array|null
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * It returns the current level.
     * 
     * @return int
     */
    public function getCurrentLevel()
    {
        return $this->currentLevel;
    }

    /**
     * @param int $currentLevel
     */
    public function setCurrentLevel($currentLevel)
    {
        $this->currentLevel = $currentLevel;
    }

}