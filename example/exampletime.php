<?php

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

// we use autoload's composer, so we call it here.
include '../vendor/autoload.php';

$smachine=new StateMachineOne(null);

$smachine->setStates([1=>'init',2=>'mid',3=>'end']);
$smachine->fieldDefault=['v1'=>0];
$smachine->addTransition(1,2,'where 1=1','change');
$smachine->addTransition(2,3,'where wait timeout 3','change');

$job=$smachine->createJob(['v1'=>1]);


$smachine->checkJob($job);
echo "<pre>";
var_dump($job);
var_dump($smachine->transitions[0]->getFullDuration($job));
echo "</pre>";

