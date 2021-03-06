<?php

namespace eftec\statemachineone;


interface StateSerializable
{
    public function toString();

    /**
     * It creates an object using a string.
     * 
     * @param Job $job
     * @param String $string
     *
     * @return mixed
     */
    public function fromString($job,$string);

    /**
     * It sets the parent
     * 
     * @param Job $job
     *
     * @return mixed
     */
    public function setParent($job);
}