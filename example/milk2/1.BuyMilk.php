<?php
/**
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @link https://github.com/EFTEC/StateMachineOne
 */

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

// we use autoload's composer, so we call it here.
include '../../vendor/autoload.php';

echo "<h1>Creating class BuildMilkClass</h1>";

$smachine=new StateMachineOne(null);
$smachine->setDebug(true);


// it is specific for this project
define('INITIAL_STATE',1);
define('DRIVING_TO_BUY_MILK',2);
define('CANCEL_DRIVING',3);
define('PICKING_THE_MILK',4);
define('PAYING_FOR_THE_MILK',5);
define('UNABLE_TO_PURCHASE',6);
define('DRIVE_BACK_HOME',7);
$smachine->setDefaultInitState(INITIAL_STATE);

$smachine->setStates([
	INITIAL_STATE=>'Initial State',
	DRIVING_TO_BUY_MILK=>'Driving to buy milk',
	CANCEL_DRIVING=>'Cancel driving.',
	PICKING_THE_MILK=>'Picking the milk',
	PAYING_FOR_THE_MILK=>'Paying for the milk',
	UNABLE_TO_PURCHASE=>'Unable to purchase',
	DRIVE_BACK_HOME=>'Drive back home.'
]);

$smachine->fieldUI=[
    'milk'=>'READWRITE'
    ,'money'=>['nothing'=>0,'little'=>20,'lot'=>2000]
    ,'price'=>'NUMERIC'
    ,'stock_milk'=>['many'=>10,'none'=>0]
    ,'store_open'=>'ONOFF'
    ,'gas'=>'READWRITE'];

$smachine->fieldDefault=[
	'milk'=>0
	,'money'=>9999
	,'price'=>null
	,'stock_milk'=>null
	,'store_open'=>null
	,'gas'=>10];

// business rules
$smachine->addMethodTransition2(INITIAL_STATE,DRIVING_TO_BUY_MILK
    ,'when milk = 0 and gas > 0');
$smachine->addMethodTransition2(INITIAL_STATE,CANCEL_DRIVING
    ,'when gas = 0','stop'); // null means, no timeout and stop means, the job will stop
$smachine->addMethodTransition2(DRIVING_TO_BUY_MILK,PICKING_THE_MILK
    ,'when store_open = 1 and stock_milk > 0');
$smachine->addMethodTransition2(DRIVING_TO_BUY_MILK,UNABLE_TO_PURCHASE
    ,'when store_open = 0 or stock_milk = 0');
$smachine->addMethodTransition2(PICKING_THE_MILK,PAYING_FOR_THE_MILK
    ,'when money >= price set milk = 1');
$smachine->addMethodTransition2(PICKING_THE_MILK,UNABLE_TO_PURCHASE
    ,'when money < price');
$smachine->addMethodTransition2(UNABLE_TO_PURCHASE,DRIVE_BACK_HOME
    ,'when always','stop');
$smachine->addMethodTransition2(PAYING_FOR_THE_MILK,DRIVE_BACK_HOME
    ,'when always','stop');
// dummies for example
$smachine->addMethodTransition2(PAYING_FOR_THE_MILK,DRIVE_BACK_HOME
    ,'set gas=0','stop');
$smachine->addMethodTransition2(PAYING_FOR_THE_MILK,DRIVE_BACK_HOME
    ,'set gas=0','stop');

// database configuration
$smachine->tableJobs= 'buymilk_jobs';
$smachine->tableJobLogs= 'buymilk_logs'; // it is optional
$smachine->setdb('mysql','localhost', 'root', 'abc.123', 'statemachinedb');

$smachine->createDbTable(true); // you don't need to create this table every time.



$smachine->miniLang->generateClass('BuildMilkClass','','BuildMilkClass.php');