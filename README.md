# StateMachineOne
State Machine library for PHP with an optional store in MySQL.  
This library has only a simple external dependency, it is a minimalist (yet complete) library with only 3 classes. 

[![Build Status](https://travis-ci.org/EFTEC/StateMachineOne.svg?branch=master)](https://travis-ci.org/EFTEC/StateMachineOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/statemachineone.svg)](https://packagist.org/packages/eftec/statemachineone)
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()


## What is a state machine?.

A State Machine (also called **Automata**) is a procedural execution of a **job** based in **states**. 
Every job must have a single state at the same time, such as "INITIATED","PENDING","IN PROCESS" and so on,
and the job changes of state (**transition**) according to some logic or condition. Such conditions could be a field, a time or a custom function.   


The target of this library is to ease the process to create a state machine.


- [StateMachineOne](#statemachineone)
  * [What is a state machine?.](#what-is-a-state-machine-)
  * [Notes](#notes)
  * [Example, ChopSuey Chinese Delivery Food.](#example--chopsuey-chinese-delivery-food)
    + [Fields (ChopSuey's exercise)](#fields--chopsuey-s-exercise-)
    + [States (ChopSuey's exercise)](#states--chopsuey-s-exercise-)
    + [Transitions (ChopSuey's exercise)](#transitions--chopsuey-s-exercise-)
    + [Final Code (ChopSuey's example)](#final-code--chopsuey-s-example-)
  * [Transition language](#transition-language)
    + [Transition when](#transition-when)
    + [Transition set](#transition-set)
- [Definition of the class](#definition-of-the-class)
  * [Field tableJobs (string)](#field-tablejobs--string-)
  * [Field tableJobLogs (string)](#field-tablejoblogs--string-)
  * [Field columnJobs (array)](#field-columnjobs--array-)
  * [Field columnJobLogs (array)](#field-columnjoblogs--array-)
  * [Field idRef (string[])](#field-idref--string---)
  * [Field extraColumnJobs (array)](#field-extracolumnjobs--array-)
  * [Method setChangeStateTrigger()](#method-setchangestatetrigger--)
    + [Parameters:](#parameters-)
  * [Method setStartTrigger()](#method-setstarttrigger--)
    + [Parameters:](#parameters--1)
  * [Method setPauseTrigger()](#method-setpausetrigger--)
    + [Parameters:](#parameters--2)
  * [Method setStopTrigger()](#method-setstoptrigger--)
    + [Parameters:](#parameters--3)
  * [Method setGetNumberTrigger()](#method-setgetnumbertrigger--)
    + [Parameters:](#parameters--4)
  * [Method addTransition()](#method-addtransition--)
    + [Parameters:](#parameters--5)
  * [Method resetTransition()](#method-resettransition--)
    + [Parameters:](#parameters--6)
  * [Method isDbActive()](#method-isdbactive--)
    + [Parameters:](#parameters--7)
  * [Method setDbActive()](#method-setdbactive--)
    + [Parameters:](#parameters--8)
  * [Method isDebug()](#method-isdebug--)
    + [Parameters:](#parameters--9)
  * [Method setDebug()](#method-setdebug--)
    + [Parameters:](#parameters--10)
  * [Method getJobQueue()](#method-getjobqueue--)
    + [Parameters:](#parameters--11)
  * [Method setJobQueue()](#method-setjobqueue--)
    + [Parameters:](#parameters--12)
  * [Method setDefaultInitState()](#method-setdefaultinitstate--)
    + [Parameters:](#parameters--13)
  * [Method getStates()](#method-getstates--)
    + [Parameters:](#parameters--14)
  * [Method setStates()](#method-setstates--)
    + [Parameters:](#parameters--15)
  * [Method __construct()](#method---construct--)
    + [Parameters:](#parameters--16)
  * [Method setDB()](#method-setdb--)
    + [Parameters:](#parameters--17)
  * [Method getDB()](#method-getdb--)
    + [Parameters:](#parameters--18)
  * [Method loadDBJob()](#method-loaddbjob--)
    + [Parameters:](#parameters--19)
  * [Method loadDBActiveJobs()](#method-loaddbactivejobs--)
    + [Parameters:](#parameters--20)
  * [Method loadDBAllJob()](#method-loaddballjob--)
    + [Parameters:](#parameters--21)
  * [Method arrayToJob()](#method-arraytojob--)
    + [Parameters:](#parameters--22)
  * [Method jobToArray()](#method-jobtoarray--)
    + [Parameters:](#parameters--23)
  * [Method createDbTable()](#method-createdbtable--)
    + [Parameters:](#parameters--24)
  * [Method saveDBJob()](#method-savedbjob--)
    + [Parameters:](#parameters--25)
  * [Method saveDBJobLog()](#method-savedbjoblog--)
    + [Parameters:](#parameters--26)
  * [Method saveDBAllJob()](#method-savedballjob--)
    + [Parameters:](#parameters--27)
  * [Method createJob()](#method-createjob--)
    + [Parameters:](#parameters--28)
  * [Method getJob()](#method-getjob--)
    + [Parameters:](#parameters--29)
  * [Method checkJob()](#method-checkjob--)
    + [Parameters:](#parameters--30)
  * [Method checkAllJobs()](#method-checkalljobs--)
    + [Parameters:](#parameters--31)
  * [Method changeState()](#method-changestate--)
    + [Parameters:](#parameters--32)
  * [Method dateToString()](#method-datetostring--)
    + [Parameters:](#parameters--33)
  * [Method addLog()](#method-addlog--)
    + [Parameters:](#parameters--34)
  * [Method removeJob()](#method-removejob--)
    + [Parameters:](#parameters--35)
  * [Method checkConsistence()](#method-checkconsistence--)
    + [Parameters:](#parameters--36)
  * [Version](#version)
  * [What is missing](#what-is-missing)




## Notes

* **Job:** it's the process to run.  A job could have a single state.  
* **State:** it's the current condition of the job.  
* **Transition:** it's the change from one **state** to another. The transition is conditioned to a set of values, time or a function.  
Also, every transition has a timeout. If the timeout is reached then the transition is done, no matter the values or the conditions (even if it has the **active** state paused).  The transition could have 3 outcomes:
* * **change** The transition changes of state and the job is keep active. It is only possible to do the transition if the job has the ****active state**** = active.
* * **pause**  The transition changes of state and the job is paused. It is only possible to do the transition if the job has the **active state** = active.
* * **continue**  The transition changes of state and the job resumes of the pause. It is only possible to do the transition if the job has the **active state** = pause or active
* * **stop** The transition changes of state and the job is stopped. It is only possible to do the transition if the job has the **active state** = active or pause.
* **Active:** Every job has an **active state**. There are 4: none,stop,active,inactive,pause. It is different from the states.
So, for example, a job could have the **state**: INPROGRESS and the **active state**: PAUSE.   
* * **none** = the job doesn't exist. It can't change of state, neither it is loaded (fro the database) by default
* * **stop** = the job has stopped (finished), it could be a successful, aborted or canceled. It can't change of state neither it is loaded by default.   
* * **pause** = the job is on hold, it will not change of state (unless it is forced) but it could be continued. 
* * **active** = the job is running normally, it could change of state.
* * **inactive** = the job is scheduled to run at some date.  it couldn't change of state unless it is activated (by schedule) 
* **Refs:** Every job has some related fields.  For example, if the job is about an invoice, then refs must be the number of the invoice. The State Machine doesn't use any refs, but it keeps the values for integration with other systems.
* **Fields:** Every job has some fields or values, it could trigger a transition.

## Example, ChopSuey Chinese Delivery Food.

We need to create a process to deliver Chinese food at home. Is it easy?. Well, let's find it out.

![ChopSuey](Docs/ChopSuey.jpg)


### Fields (ChopSuey's exercise)

**Fields** are values used for out State Machine. 
In this example, I am not including other values that it could be useful (such as money, 
customer name, address and such) because they are not part or used by the state machine.

* **customerpresent** =1 if the customer is at home, 0=if not, =null not defined yet
* **addressnotfound** =1 if the address is not found by the delivery boy, =0 if found, =null if it's not yet defined.
* **signeddeliver** =1 if the customer signed the deliver, =0 if not, =null if its not defined. 
* **abort** =1 if the deliver must be aborted (for example, an accident), =0 if not.
* **instock**  =1 if the product is in stock, =0 if it's not, =null if it is not defined.
* **picked** =1 if the deliver boy picked and packed the product, =0 if not yet.

### States (ChopSuey's exercise)

It must include all the possible situation. The real world is not as easy as: sell and money.

* **STATE_PICK**  The delivery boy will pick the food if any. For example, what if a customer asks for Pekin's duck (with orange sauce) and the restaurant doesn't have?.

![cooker](Docs/cooker.jpg)

* **STATE_CANCEL**   The order is canceled. 
* **STATE_TRANSPORT**  The delivery boy is on route to deliver the food.

![cooker](Docs/deliveryboy.jpg)

  
* **STATE_ABORTTRANSPORT**   Something happened, the delivery must be aborted. 
* **STATE_HELP**    The delivery boy is ready to deliver but he is not able to find the address or maybe there is nobody, so he calls for help.  
* **STATE_DELIVERED**  The food is delivered. Our hero returns to base (Chinese restaurant).

![cooker](Docs/food.jpg)

 
* **STATE_ABORTED**  The transaction is aborted, nobody at home or the address is wrong.

### Transitions (ChopSuey's exercise)

* **STATE_PICK** -> **STATE_CANCEL** (END)  When?. instock=0 (end of the job)
* **STATE_PICK** -> **STATE_TRANSPORT**  When?. instock=1 and picked=1
* **STATE_TRANSPORT** -> **STATE_ABORTTRANSPORT** (END)  When?. abort=1 (for some reason, our boy abort the transport, is it raining?)
* **STATE_TRANSPORT** -> **STATE_DELIVERED** (END)  When?. addressnotfound=0,customerpresent=1 and signeddeliver=1. It is delivered, the customer is present and it signed the deliver (plus a tip, I hope it)
* **STATE_TRANSPORT** -> **STATE_HELP**  When?. addressnotfound=1,customerpresent=0 and signeddeliver<>1. Our deliver calls to home and ask for new instructions. Is it Fake Street #1234 the right address?.  
* **STATE_HELP** -> **STATE_ABORTED** (END) When?. (15 minutes deadline) or if abort=1.  Our deliver called home and yes, the address is fake (it's a shocking surprise)  
* **STATE_HELP** -> **STATE_DELIVERED** (END) When?. addressnotfound=0,customerpresent=1 and signeddeliver=1. It is delivered, the customer is present and it signed the deliver (plus a tip, I hope it)  

### Final Code (ChopSuey's example)
[Example/ChopSuey.php](example/ChopSuey.php)

```php
<?php
use eftec\statemachineone\StateMachineOne;

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
$smachine->setDB('localhost',"root","abc.123","statemachinedb");
$smachine->createDbTable(false); // you don't need to create this table every time.

$smachine->loadDBAllJob(); // we load all jobs, including finished ones.

// business rules
$smachine->addTransition(STATE_PICK,STATE_CANCEL
	,'when instock = 0 set abort = 1',null,'stop');
$smachine->addTransition(STATE_PICK,STATE_TRANSPORT
	,'when instock = 1',null,'change');
$smachine->addTransition(STATE_TRANSPORT,STATE_ABORTTRANSPORT
	,'when abort = 1',null,'stop');
$smachine->addTransition(STATE_TRANSPORT,STATE_DELIVERED
	,'when addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1',60*60,'stop'); // 1 hour max.
$smachine->addTransition(STATE_TRANSPORT,STATE_HELP
	,'when addressnotfound = 1 or customerpresent = 0',60*60,'change'); // 1 hour max
$smachine->addTransition(STATE_HELP,STATE_ABORTED
	,'when timeout',60*15,'change'); // it waits 15 minutes max.
$smachine->addTransition(STATE_HELP,STATE_DELIVERED
	,'when addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1',null,'change');


$msg=$smachine->fetchUI();
$smachine->checkAllJobs();

$smachine->viewUI(null,$msg); // null means it takes the current job
```



## Transition language

Let's say the next transition

```php
$smachine->addTransition(STATE_PICK,STATE_CANCEL
	,'when instock = 0 set abort = 1',null,'stop');

```

The transition is written as follow:
* initial state
* end state
* Transition language
* timeout (in seconds), if null then it never stop.
* outcome, it could be change (default value),stop,pause and continue

The transition language is written with the next syntax.
* it uses spaces between each operation. It is a must (it's for optimization)  
* there are two operations we could do **when** and **set**

### Transition when  
> when field = 0

It compares a constant. The binary operator for comparison are
* = Equals
* &lt;&gt; Not equals
* &lt; &lt;= Less and less than
* &gt; &gt;= Great and great than
* contain If a text contains other.

Values of the field could be as the next ones:
* field = it is a field of the job.
* $var = it is a global variable (php)
* 0 = it is a numeric constant
* "AAA", 'aaa' = it is a literal 
* function() = it is a global function. Every function must have the parameter $job. 
* null it is the null value
* now() it defines the current timestamp (in seconds)
* interval() it returns the current interval between now and the last state.
* fullinterval() it returns the current interval between now and the start of the job.

For example  
> when field2 = 2 and field3 > someFunction() and  field4=$var  
> > Where somefunction must be defined as someFunction(Job $job) {}

### Transition set
> set field = 0 , field2 = 3

It sets a field of the job.

* The first value of the operation can't be a constant. 
> set 0 = 20  // is invalid
* The first value could be a function.
> set myfunc() = 20  
> > Where the function (global) must be defined as myfunc(Job $job,$input) {}
* The firsr value could be a field or a (global) variable
> set field=20  
> set $variable=20  



# Definition of the class

  

## Field tableJobs (string)
The name of the table to store the jobs
## Field tableJobLogs (string)
The name of the table to store the logs per job. If it's empty then it is not used
## Field columnJobs (array)
The list of database columns used by the job
## Field columnJobLogs (array)
The List of database columns used by the log of the job
## Field idRef (string[])
It indicates a special field to set the reference of the job.
## Field extraColumnJobs (array)
It indicates extra fields/states
## Method setChangeStateTrigger()
It sets the method called when the job change state

### Parameters:  
* **$changeStateTrigger** param callable $changeStateTrigger (callable)
## Method setStartTrigger()
It sets the method called when the job starts

### Parameters:  
* **$startTrigger** param callable $startTrigger (callable)
## Method setPauseTrigger()
It sets the method called when job is paused

### Parameters:  
* **$pauseTrigger** param callable $pauseTrigger (callable)
## Method setStopTrigger()
It sets the method called when the job stop

### Parameters:  
* **$stopTrigger** param callable $stopTrigger (callable)
```php
$tmp=$statemachineone->setStopTriggerthis(); 
```
## Method setGetNumberTrigger()
It sets a function to returns the number of the process. By default, it is obtained by the database
or via an internal counter.

### Parameters:  
* **$getNumberTrigger** param callable $getNumberTrigger (callable)
## Method addTransition()
add a new transition

### Parameters:  
* **$state0** Initial state (string)
* **$state1** Ending state (string)
* **$conditions** Conditions, it could be a function or a string 'instock = "hello"' (mixed)
* **$duration** Duration of the transition in seconds. (int)
* **$result** =['change','pause','continue','stop'][$i] (string)
## Method resetTransition()
We clear all transitions.

### Parameters:  
## Method isDbActive()
Returns true if the database is active

### Parameters:  
## Method setDbActive()
It sets the database as active. When we call setDb() then it is set as true automatically.

### Parameters:  
* **$dbActive** param bool $dbActive (bool)
## Method isDebug()
Returns true if is in debug mode.

### Parameters:  
## Method setDebug()
Set the debug mode. By default the debug mode is false.

### Parameters:  
* **$debug** param bool $debug (bool)
## Method getJobQueue()
Returns the job queue.

### Parameters:  
## Method setJobQueue()
Set the job queue

### Parameters:  
* **$jobQueue** param Job[] $jobQueue (Job[])
## Method setDefaultInitState()


### Parameters:  
* **$defaultInitState** param int $defaultInitState (int)
## Method getStates()
Gets an array with the states

### Parameters:  
## Method setStates()
Set the array with the states

### Parameters:  
* **$states** param array $states (array)
## Method __construct()
Constructor of the class. By default, the construct set default triggers.
StateMachineOne constructor.

### Parameters:  
## Method setDB()
It sets the database

### Parameters:  
* **$server**    server ip, example "localhost" (string)
* **$user**      user of the database, example "root" (string)
* **$pwd**       password of the database, example "123456" (string)
* **$db**        database(schema), example "sakila" (string)
## Method getDB()
It returns the current connection. If there is not a connection then it generates a new one.

### Parameters:  
## Method loadDBJob()
Loads a job from the database

### Parameters:  
* **$idJob** param $idJob ()
## Method loadDBActiveJobs()
It loads all jobs from the database with all active state but none and stopped.

### Parameters:  
## Method loadDBAllJob()
It loads all jobs from the database regardless its active state.

### Parameters:  
## Method arrayToJob()


### Parameters:  
## Method jobToArray()


### Parameters:  
* **$job** param Job $job (Job)
## Method createDbTable()
(optional), it creates a database table, including indexes.

### Parameters:  
* **$drop** if true, then the table will be dropped. (bool)
## Method saveDBJob()
It saves a job in the database. It only saves a job that is marked as new or updated

### Parameters:  
* **$job** param Job $job (Job)
## Method saveDBJobLog()
Insert a new job log into the database.

### Parameters:  
* **$idJob** param $idJob ()
* **$arr** param $arr ()
## Method saveDBAllJob()
It saves all jobs in the database that are marked as new or updated.

### Parameters:  
## Method createJob()
It creates a new job.

### Parameters:  
* **$idRef**  Every job must refence some object/operation/entity/individual. (int[])
* **$fields** param array $fields (array)
* **$active=['none','inactive','active','pause','stop'][$i]** param string $active=['none','inactive','active','pause','stop'][$i] (string)
* **$initState** param mixed $initState (mixed)
* **$dateStart** param int|null $dateStart (int|null)
* **$durationSec** Duration (maximum) in seconds of the event (int|null)
* **$expireSec** param int|null $expireSec (int|null)
## Method getJob()
It gets a job by id.

### Parameters:  
* **$idJob** param int $idJob (int)
## Method checkJob()
It checks a specific job and proceed to change state.
We check a job and we change the state

### Parameters:  
* **$idJob** param $idJob ()
## Method checkAllJobs()
It checks all jobs available (if the active state of the job is any but none or stop)

### Parameters:  
## Method changeState()
It changes the state of a job manually.
It changes the state manually.

### Parameters:  
* **$job** param Job $job (Job)
* **$newState** param mixed $newState (mixed)
## Method dateToString()


### Parameters:  
* **$time** timestamp with microseconds (int|null)
## Method addLog()
It adds a log of the job.

### Parameters:  
* **$idJob** param int $idJob (int)
* **$type=['ERROR','WARNING','INFO','DEBUG'][$i]** param string $type=['ERROR','WARNING','INFO','DEBUG'][$i] (string)
* **$description** param string $description (string)
## Method removeJob()
It removes a jobs of the queue.

### Parameters:  
* **$job** param Job $job (Job)
```php
$tmp=$statemachineone->removeJobthis(); 
```
## Method checkConsistence()
We check if the states are consistents. It is only for testing.

### Parameters:  
```php
$tmp=$statemachineone->checkConsistencethis(); 
```

## Version

* 1.1 2018-12-09 Some corrections.  
* 1.0 2018-12-08 First (non beta) version.

## What is missing

* Most unit test, now it is only the barebone.
* Increase the log features.  