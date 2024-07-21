<?php

use eftec\statemachineone\Flags;
use eftec\statemachineone\StateMachineOne;

include '../vendor/autoload.php';
echo "<h1>Example timeline</h1>";
echo "This example shows to record a state machine event with times and later replicates it";
echo "<h2>Initial</h2>";
$smachine = new StateMachineOne(null);
$smachine->setRecordEventState(__DIR__ . '/exampletimeline.json', true);
$smachine->setDefaultInitState(1);
$smachine->setDocDB('exampledb', 'col1', 'folder');
try {
    $smachine->getDocOne()->deleteCollection('col1');
    $smachine->getDocOne()->createCollection('col1');
} catch(Throwable $e) {

}
$smachine->getDocOne()->autoSerialize(true, 'json_array');
$smachine->setTime(1000);
//$smachine->setTime(6000);
$smachine->setStates([1 => 'init', 2 => 'mid', 3 => 'end']);
$smachine->fieldDefault = ['v1' => 0, 'flag1' => new Flags('flag1', true, $smachine), 'v3' => 'abc'];
$smachine->addTransition(1, 1, 'where _time=1005 then flag1.push("a1","abc") and v3="second1005"', 'stay');
$smachine->addTransition(1, 2, 'where _time>=1010 then flag1.push("a1","xyz") and v3="second1010"', 'change');
$smachine->addTransition(2, 3, 'where _time>=1020 then and v3="second1020"', 'change');
$smachine->setTime(1000);
$job = $smachine->createJob();
$job->idJob=1;
for ($i = 1000; $i <= 1020; $i++) {
    $smachine->setTime($i);
    $smachine->checkJob($job);
    //$smachine->recordEventState(__DIR__.'/event.php','php');
    $r = $job->fields;
    unset($r['flag1']);
    echo "State:".json_encode($r)." time:$i<br>";
}
$smachine->deleteJobDB($job);
echo "<h2>replay</h2>\n";
$smachine->setRecordEventState(__DIR__ . '/exampletimeline2.json', true); // set a new record
$oldValues = $smachine->replayRecordInit('exampletimeline.json');
$job = $smachine->createJob();



foreach ($oldValues as $row) {
    $smachine->replayRecordInsideLoop($row,$job);
    //$r = $job->fields;
    //unset($r['flag1']);

    $smachine->checkJob($job);
    $r = $job->fields;
    unset($r['flag1']);

    echo "State:".json_encode($r)." time:$i<br>";


}
$smachine->deleteJobDB($job);
//var_dump($txt);
//$smachine->saveDBAllJob();