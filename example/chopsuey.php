<?php
use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

include "../vendor/autoload.php";


// it is specific for this project
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
$smachine->tableJobs="chopsuey_jobs";
$smachine->tableJobLogs="chopsuey_logs";

$smachine->setDefaultInitState(STATE_PICK);
$smachine->setAutoGarbage(false); // we don't want to delete automatically a stopped job.
$smachine->setStates([STATE_PICK=>'Pick order'
	,STATE_CANCEL=>'Cancel order'
	,STATE_TRANSPORT=>'Transport order'
	,STATE_ABORTTRANSPORT=>'Abort the delivery'
	,STATE_TODELIVER=>'Pending to deliver'
	,STATE_HELP=>'Request assistance'
	,STATE_DELIVERED=>'Delivered'
	,STATE_ABORTED=>'Aborted']);
/*
$smachine->setStates([STATE_PICK
	,STATE_CANCEL
	,STATE_TRANSPORT
	,STATE_ABORTTRANSPORT
	,STATE_TODELIVER
	,STATE_HELP
	,STATE_DELIVERED
	,STATE_ABORTED]);
*/
$smachine->extraColumnJobs=['customerpresent','addressnotfound'
	,'signeddeliver','abort','instock','picked'];
$smachine->setDB('localhost',"root","abc.123","statemachinedb");
$smachine->createDbTable(false); // you don't need to create this table every time.

$smachine->loadDBAllJob(); // we load all jobs, including finished ones.
//$smachine->loadDBActiveJobs(); // use this in production!.


// business rules
$smachine->addTransition(STATE_PICK,STATE_CANCEL
	,'where instock = 0 set abort = 1',null,'stop');
$smachine->addTransition(STATE_PICK,STATE_TRANSPORT
	,'where instock = 1',null,'change');
$smachine->addTransition(STATE_TRANSPORT,STATE_ABORTTRANSPORT
	,'where abort = 1',null,'stop');
$smachine->addTransition(STATE_TRANSPORT,STATE_DELIVERED
	,'where addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1',60*60,'stop'); // 1 hour max.
$smachine->addTransition(STATE_TRANSPORT,STATE_HELP
	,'where addressnotfound = 1 or customerpresent = 0',60*60,'change'); // 1 hour max
$smachine->addTransition(STATE_HELP,STATE_ABORTED
	,'where timeout',60*15,'change'); // it waits 15 minutes max.
$smachine->addTransition(STATE_HELP,STATE_DELIVERED
	,'where addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1',null,'change');

//include "chopsuey_ui.php";
$msg=$smachine->fetchUI();
$smachine->checkAllJobs();

$smachine->viewUI(null,$msg); // null means it takes the current job
	

