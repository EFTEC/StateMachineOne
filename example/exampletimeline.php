<?php

use eftec\statemachineone\StateMachineOne;

include '../vendor/autoload.php';

$smachine=new StateMachineOne(null);
$smachine->setDefaultInitState(1);
$smachine->setDocDB('exampledb','col1','folder');
$smachine->getDocOne()->autoSerialize(true,'json_array');
$smachine->setTime(1000);
//$smachine->setTime(6000);


$smachine->setStates([1=>'init',2=>'mid',3=>'end']);
$smachine->fieldDefault=['v1'=>0];
$smachine->addTransition(1,2,'where _time>=1010','change');
$smachine->addTransition(2,3,'where _time>=1020','change');

$job=$smachine->createJob(['v1'=>1]);

for($i=1000;$i<=1020;$i++) {
    $smachine->setTime($i);
    $smachine->checkAllJobs();

    $lastJob = $smachine->getLastJob();
    echo "State:$lastJob->state time:$i<br>";
}


//$smachine->saveDBAllJob();