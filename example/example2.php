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

$smachine=new StateMachineOne();
$smachine->setDebug(true);
$smachine->tableJobs="deliver_product";
$smachine->tableJobLogs="deliver_product_log";
$smachine->setDefaultInitState(STATE_PICK);
$smachine->extraColumnJobs=['customerpresent','addressnotfound','signeddeliver','abort','instock','picked'];
$smachine->setDB('localhost',"root","abc.123","statemachinedb");
$smachine->createDbTable(true); // you don't need to create this table every time.

$smachine->setStopTrigger(function($smo,$job) {echo "job is stopping<br>"; return true;});

//$smachine->loadDBActiveJobs();


$smachine->setStates([STATE_PICK,STATE_CANCEL,STATE_TRANSPORT,STATE_ABORTTRANSPORT,STATE_TODELIVER,STATE_HELP,STATE_DELIVERED,STATE_ABORTED]);

// if instock = 0 and picked = 1 then change and set instock = 1 , instock = 2
// if _timeout then change and set instock = 1 , instock = 2
$dummy='hello';

function dummy($job) {
	return 'hello';
}

$smachine->addTransition(STATE_PICK,STATE_CANCEL,'where instock = 0',60*30,"stop");
$smachine->addTransition(STATE_PICK,STATE_TRANSPORT,'where picked = 1',60*30,"change");
$smachine->addTransition(STATE_TRANSPORT,STATE_TODELIVER,'where addressnotfound = 0',60*30,"change");
$smachine->addTransition(STATE_TRANSPORT,STATE_HELP,'where addressnotfound = 1',60*30,"change");
$smachine->addTransition(STATE_HELP,STATE_ABORTED,'where addressnotfound = 9999',1,"stop"); // we wait 2 seconds, then we give it up
$smachine->addTransition(STATE_HELP,STATE_TODELIVER,'where addressnotfound = 0',60*30,"change");

$smachine->addTransition(STATE_TODELIVER,STATE_DELIVERED
	,'where signeddeliver = 1 set addressnotfound = 0 and customerpresent = 1',60*30,"stop");

$smachine->checkConsistency();

$job=$smachine->createJob(['idref'=>1]
        ,['customerpresent'=>''
        ,'addressnotfound'=>1
        ,'signeddeliver'=>1
        ,'abort'=>''
		,'fieldnotstored'=>'hi'
        ,'instock'=>1
        ,'picked'=>1]);

$smachine->checkAllJobs();
$smachine->checkAllJobs();
$smachine->checkAllJobs();
sleep(2);
$smachine->checkAllJobs();
$smachine->checkAllJobs();
$smachine->checkAllJobs();
$smachine->checkAllJobs();


