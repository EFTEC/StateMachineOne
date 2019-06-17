<?php

namespace eftec\statemachineone;

/**
 * Class StateSerializable
 * @package  eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.7 2019-06-16
 * @link https://github.com/EFTEC/StateMachineOne
 */
class Flag implements StateSerializable
{
    private $value;
    /** @var array|null  */
    private $stack=null;
    private $currentLevel;
    private $changed=false;

    /**
     * Flag constructor.
     *
     * @param mixed $value
     * @param int   $currentLevel
     * @param bool  $changed
     */
    public function __construct($value=null, $currentLevel=0,$changed=true)
    {
        $this->value = $value;
        $this->stack =null;
        $this->currentLevel = $currentLevel;
        $this->changed=$changed;
    }
    
    public function toString() {
        if ($this->stack!==null) {
            return serialize($this->stack).';;'.$this->currentLevel.';;'.($this->changed?1:0);    
        } else {
            return $this->value.';;'.$this->currentLevel.';;'.($this->changed?1:0);
        }
        
    }
    public function fromString($string) {
        $arr=explode(';;',$string);
        if (strpos(@$arr[0],'a:')===0) {
            $this->stack=unserialize($arr[0]);
        } else {
            $this->value=@$arr[0];    
        }
        $this->currentLevel=@$arr[1];
        $this->changed=(@$arr[2]==1);
    }

    /**
     * It sets a flag, replacing the previous value (if any)
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflag($msg="",$level=0) {
        $this->value=$msg;
        $this->currentLevel=$level;
        $this->changed=true;
        return $this;
    }

    /**
     * It sets a flag only if the level is lower than the current level.
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflagMin($msg="",$level=0) {
        if($level<$this->currentLevel) {
            $this->value = $msg;
            $this->currentLevel = $level;
            $this->changed=true;
        }
        return $this;
    }

    /**
     * It sets a flag only if the level is higher that the current level
     * @param string $msg
     * @param int    $level
     *
     * @return $this
     */
    public function setflagMax($msg="",$level=0) {
        if($level>$this->currentLevel) {
            $this->value = $msg;
            $this->currentLevel = $level;
            $this->changed=true;
        }
        return $this;
    }

    /**
     * It push a new flag under a specific identifier.
     * <p>pushflag('somemessage','light',2); // It adds a flag in 'lights'</p>
     * <p>pushflag('new message','light',2); // It replaces the other flag</p>
     * <p>pushflag('new message','siren',2); // It addsa a flag in 'siren'</p>
     * @param string $msg
     * @param string|int $idUnique
     * @param int    $level
     *
     * @return $this
     */
    public function pushFlag($msg="",$idUnique=0,$level=0) {
        @$this->stack[$idUnique] = $msg;
        $this->currentLevel = $level;
        $this->changed=true;
        return $this;
    }
    public function pullFlag($idUnique="") {
        unset($this->stack[$idUnique]);
        return $this;
    }
    public function cleanFlag() {
        $this->value="";
        $this->currentLevel=0;
        $this->changed=true;
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