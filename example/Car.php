<?php
/**
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @link https://github.com/EFTEC/StateMachineOne
 */

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

// we use autoload's composer, so we call it here.
include "../vendor/autoload.php";

$smachine=new StateMachineOne();
$smachine->setDebug(true);





// it is specific for this project
define('PARKED',1);
define('IDLING',2);
define('DRIVING',3);

$smachine->setDefaultInitState(PARKED);

$smachine->setStates([
	PARKED=>'Parked',
	IDLING=>'Idling',
	DRIVING=>'Driving'
]);

$smachine->fieldDefault=[
	'pedal'=>0
	,'turnkey'=>0
	,'gas'=>100
	,'brake'=>0
	,'speed'=>0];

// database configuration
$smachine->tableJobs="car_jobs";
$smachine->tableJobLogs="car_logs"; // it is optional
$smachine->setdb('mysql','localhost',"root","abc.123","statemachinedb");
$smachine->createDbTable(false); // you don't need to create this table every time.

$smachine->loadDBAllJob(); // we load all jobs, including finished ones.
//$smachine->loadDBActiveJobs(); // use this in production, we don't need stopped job every time.


// business rules
$smachine->addTransition(PARKED,IDLING
	,'when pedal = 1 and turnkey = 1 and gas > 0');
$smachine->addTransition(IDLING,DRIVING
	,'when gas > 0 and speed > 0');
$smachine->addTransition(DRIVING,IDLING
	,'when brake = 1 and speed = 0');
$smachine->addTransition(IDLING,PARKED
	,'when turnkey = 0 and speed = 0');
$msg=$smachine->fetchUI(); // we show a visual id (it is optional and it's only for debug purpose)
$smachine->checkAllJobs(); // we check every (active,pause,continue) job available.

$smachine->viewUI(null,$msg); // null means it takes the current job
	

