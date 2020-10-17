<?php

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

include "../vendor/autoload.php";

define("STATE_PICK",1);
define("STATE_CANCEL",2);
define("STATE_TRANSPORT",3);
define("STATE_ABORTTRANSPORT",4);
define("STATE_TODELIVER",5);
define("STATE_HELP",6);
define("STATE_DELIVERED",7);
define("STATE_ABORTED",8);

$smachine=new StateMachineOne(null);
$smachine->setDebug(true);
$smachine->tableJobs="deliver_product";
$smachine->tableJobLogs="deliver_product_log";
$smachine->setDefaultInitState(STATE_PICK);
$smachine->fieldDefault=[
	'customerpresent'=>null
	,'addressnotfound'=>null
	,'signeddeliver'=>null
	,'abort'=>null
	,'instock'=>null
	,'picked'=>null];
$smachine->setdb('mysql','localhost',"root","abc.123","statemachinedb");
$smachine->getDB()->logLevel=3;
$smachine->createDbTable(false); // you don't need to create this table every time.

$smachine->setStopTrigger(function($smo,$job) {echo "Trigger: job is stopping<br>"; return true;});
$smachine->setPauseTrigger(function($smo,$job) {echo "Trigger: job is paused<br>"; return true;},'after');
//$smachine->loadDBActiveJobs();


$smachine->setStates(
		[STATE_PICK=>'STATE_PICK'
		,STATE_CANCEL=>'STATE_CANCEL'
		,STATE_TRANSPORT=>'STATE_TRANSPORT'
		,STATE_ABORTTRANSPORT=>'STATE_ABORTTRANSPORT'
		,STATE_TODELIVER=>'STATE_TODELIVER'
		,STATE_HELP=>'STATE_HELP'
		,STATE_DELIVERED=>'STATE_DELIVERED'
		,STATE_ABORTED=>'STATE_ABORTED']);

// if instock = 0 and picked = 1 then change and set instock = 1 , instock = 2
// if _timeout then change and set instock = 1 , instock = 2
$dummy='hello';

function dummy($job) {
	return 'hello';
}
var_dump($smachine->getDB()->lastError());


$smachine->addTransition(STATE_PICK,STATE_CANCEL,'when instock = 0',"stop");
$smachine->addTransition(STATE_PICK,STATE_TRANSPORT,'when picked = 1',"change");
$smachine->addTransition(STATE_TRANSPORT,STATE_TODELIVER,'when addressnotfound = 0',"change");
$smachine->addTransition(STATE_TRANSPORT,STATE_HELP,'when addressnotfound = 1',"change");
//$smachine->addTransition(STATE_HELP,STATE_ABORTED,'when addressnotfound = 9999 timeout 1',"stop"); // we wait 2 seconds, then we give it up

$smachine->addTransition(STATE_HELP,STATE_ABORTED,'when addressnotfound = 9999',"stop"); // we wait 2 seconds, then we give it up
$smachine->addTransition(STATE_HELP,STATE_TODELIVER,'when addressnotfound = 0',"change");
//$smachine->addTransition(STATE_TODELIVER,STATE_DELIVERED
//	,'when signeddeliver = 1 set addressnotfound = 0 , customerpresent = 1 timeout 3600',"stop");

$smachine->addTransition(STATE_TODELIVER,STATE_DELIVERED
	,'when signeddeliver = 1 set addressnotfound = 0 , customerpresent = 1 timeout 3600',"stop");
$smachine->addEvent('CUSTOMERPRESENT','set addressnotfound = 0 , customerpresent = 1');

$smachine->checkConsistency();

$object=['customerpresent'=>1
	,'addressnotfound'=>1
	,'signeddeliver'=>1
	,'abort'=>-1
	,'fieldnotstored'=>'hi' // this field is not store or it's part of the state machine
	,'instock'=>1
	,'picked'=>1];
echo "<br><br><b>Creating job</b><br>";
echo "set1:";
var_dump($smachine->getDB()->set);
$job=$smachine->createJob($object);
echo "set2:";
var_dump($smachine->getDB()->lastError());
var_dump($smachine->getDB()->set);
echo "<br><br><b>Checking job</b><br>";
$smachine->checkJob($job);
echo "<br><br><b>sleeping 2 seconds</b><br>";
die(1);
sleep(2);
$smachine->getJobQueue();

echo "<hr>";
$object=['customerpresent'=>-1 // undefined
	,'addressnotfound'=>-1 // undefined.
	,'signeddeliver'=>1
	,'abort'=>-1
	,'fieldnotstored'=>'hi' // this field is not store
	,'instock'=>1
	,'picked'=>1];
$job=$smachine->createJob($object);
$smachine->checkAllJobs();
echo "calling event CUSTOMERPRESENT<br>";
$smachine->callEvent('CUSTOMERPRESENT');
$smachine->checkAllJobs();

