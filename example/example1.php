<?php

use eftec\statemachineone\StateMachineOne;

include "../vendor/autoload.php";

$sm=new StateMachineOne();
$sm->createJob([1],['f1']);