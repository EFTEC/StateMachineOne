<?php

namespace eftec\statemachineone;


interface StateSerializable
{
    public function toString();

    public function fromString($string);
}