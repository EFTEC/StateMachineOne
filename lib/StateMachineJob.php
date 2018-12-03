<?php
namespace eftec\statemachineone;
use eftec\MessageItem;
use eftec\MessageList;

/**
 * Class StateMachineJob
 * @package eftec\statemachineone
 */
class StateMachineJob {
    /** @var int[] reference value. For example, (order number) or (order number, customer id) */
    var $idRef;
    /** @var int */
    var $dateInit;
    /** @var int */
    var $dateEnd;
    /** @var int */
    var $dateExpired;    
    /** @var mixed */
    var $state;
    /** @var array */
    var $fields;
    /** @var int 0=inactive,1=active,-1=on schedule (not yet started),-2=paused */
    var $active;

    var $isNew=false;
    var $isUpdate=false;

    /** @var MessageItem[] */
    var $messages;    
    /** @var array */
    var $log;

    /**
     * StateMachineJob constructor.
     */
    public function __construct()
    {
        $this->messages=[];
        $this->log=[];
    }

    /**
     * @param int[] $idRef
     * @return StateMachineJob
     */
    public function setIdRef(array $idRef): StateMachineJob
    {
        $this->idRef = $idRef;
        return $this;
    }

    /**
     * @param int $dateInit
     * @return StateMachineJob
     */
    public function setDateInit(int $dateInit): StateMachineJob
    {
        $this->dateInit = $dateInit;
        return $this;
    }

    /**
     * @param int $dateEnd
     * @return StateMachineJob
     */
    public function setDateEnd(int $dateEnd): StateMachineJob
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    /**
     * @param int $dateExpired
     * @return StateMachineJob
     */
    public function setDateExpired(int $dateExpired): StateMachineJob
    {
        $this->dateExpired = $dateExpired;
        return $this;
    }

    /**
     * @param mixed $state
     * @return StateMachineJob
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param array $fields
     * @return StateMachineJob
     */
    public function setFields(array $fields): StateMachineJob
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param int $active
     * @return StateMachineJob
     */
    public function setActive($active): StateMachineJob
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @param bool $isNew
     * @return StateMachineJob
     */
    public function setIsNew(bool $isNew): StateMachineJob
    {
        $this->isNew = $isNew;
        return $this;
    }

    /**
     * @param bool $isUpdate
     * @return StateMachineJob
     */
    public function setIsUpdate(bool $isUpdate): StateMachineJob
    {
        $this->isUpdate = $isUpdate;
        return $this;
    }
    
    
    
} // end StateMachineJob
