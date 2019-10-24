<?php
/**
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @link https://github.com/EFTEC/StateMachineOne
 */

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

define("EVENT_ABORT",'ABORT'); // it could be a number too
define("EVENT_FLIPABORT",'FLIP ABORT'); // it could be a number too

$smachine=new StateMachineOne(null);
$smachine->setDebug(true);
$smachine->setDebugAsArray(true);
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

$smachine->fieldDefault=[
	'customerpresent'=>-1
	,'addressnotfound'=>-1
	,'signeddeliver'=>-1
	,'abort'=>-1
	,'instock'=>-1
	,'picked'=>-1];
$smachine->setDocDB('exampledb',"chopsuey",'auto',null);

$smachine->tableJobs="chopsuey_jobs";
$smachine->tableJobLogs="chopsuey_logs";

$smachine->createDbTable(false); // you don't need to create this table every time.

$smachine->loadDBAllJob(); // we load all jobs, including finished ones.

/*
$job=$smachine->createJob($smachine->fieldDefault);
$smachine->saveDBJob($job);

$smachine->loadDBJob($job->idJob);

$job2=$smachine->getJob($job->idJob);

var_dump($job2);

$smachine->loadDBAllJob();
var_dump($smachine->getJobQueue());
die(1);
*/
// we load all jobs, including finished ones.
//$smachine->loadDBActiveJobs(); // use this in production!.


// business rules
$smachine->addTransition(STATE_PICK,STATE_CANCEL
	,'when instock = 0 set abort = 1','stop');
$smachine->addTransition(STATE_PICK,STATE_TRANSPORT
	,'when instock = 1','change');
$smachine->addTransition(STATE_TRANSPORT,STATE_ABORTTRANSPORT
	,'when abort = 1','stop');
$smachine->addTransition(STATE_TRANSPORT,STATE_DELIVERED
	,'when addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1 timeout 3600','stop'); // 1 hour max.
$smachine->addTransition(STATE_TRANSPORT,STATE_HELP
	,'when addressnotfound = 1 or customerpresent = 0 timeout 3600','change'); // 1 hour max
$smachine->addTransition(STATE_HELP,STATE_ABORTED
	,'when wait timeout 900 fulltimeout 2000','change'); // it waits 15 minutes max.
$smachine->addTransition(STATE_HELP,STATE_DELIVERED
	,'when addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1','change');

$smachine->addEvent(EVENT_ABORT,'set abort = 1');
$smachine->addEvent(EVENT_FLIPABORT,'set abort=flip()');
// $smachine->callEvent(EVENT_ABORT);

$msg=$smachine->fetchUI();
$smachine->checkAllJobs();

$smachine->viewUI(null,$msg); // null means it takes the current job
	

