# StateMachineOne
State Machine library for PHP with an optional store in mysql.  
This library has only a simple external dependency, it is a minimalist (yet complete) library with only 3 classes. 

[![Build Status](https://travis-ci.org/EFTEC/StateMachineOne.svg?branch=master)](https://travis-ci.org/EFTEC/BladeOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/statemachineone.svg)](https://packagist.org/packages/eftec/bladeone)
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()


## What is a state machine?.

A state machine (also called Automata, such as Nier minus her high heels) is a procedural execution of a **job** based in **states**. 
Every job must has a state such as "INITIATED","PENDING","IN PROCESS" and so on,
and the job changes of state (**transition**) according some logic or condition. Such conditions could be a field, a time or a custom function.   


The target of this library is to ease the process to create a state machine.


## Notes

* **Job:** it's the process to run.  A job could have a single state.  
* **State:** it's the current condition of the job.  
* **Transition:** it's the change from one **state** to another. The transition is conditioned to a set of values, time or a function.  
Also, every transition has a timeout. If the timeout is reached then the transition is done, no matter the values or the conditions (even if it has the **active** state paused).  Transition could have 3 outcomes:
* * **change** The transition changes of state and the job is still active. It is only possible to do the transition if the job has the ACTIVE STATE = active.
* * **pause**  The transition changes of state and the job is paused. It is only possible to do the transition if the job has the ACTIVE STATE = active.
* * **continue**  The transition changes of state and the job resumes of the pause. It is only possible to do the transition if the job has the ACTIVE STATE = pause or active
* * **stop** The transition changes of state and the job is stopped. It is only possible to do the transition if the job has the ACTIVE STATE = active or pause.
* **Active:** Every job has an **active state**. There are 4: none,stop,active,inactive,pause. It is different to the states.
So, for example, a job could has the **state**: INPROGRESS and the **active state**: PAUSE.   
* * **none** = the job doesn't exist. It can't change of state, neither it is loaded by default
* * **stop** = the job has stopped (finished), it could be a succesful, aborted or cancelled. It can't change of state neither it is loaded by default.   
* * **pause** = the job is on hold, it will not change of state (unless it is forced) but it could be continued. 
* * **active** = the job is running normally, it could change of state.
* * **inactive** = the job is schedule to run at some date.  it couldn't change of state unless it is activated (by schedule) 
* **Refs:** Every job has some related fields.  For example, if the job is about an invoice, then refs must be the number of the invoice. The State Machine doesn't use any refs but it keeps the values for integration with other systems.
* **Fields:** Every job has some fields or values, it could trigger a transition.

## Example, ChopSuey Chinese Delivery Food.

We need to create a process to deliver chinese food at home. Is it easy?. Well, let's find it out.

![ChopSuey](Docs/ChopSuey.jpg)


### Fields (ChopSuey's exercise)

**Fields** are values used for out State Machine. 
In this example, I am not including other values that it could be useful (such as money, customer name, address and such), because they are not part or used by of the state machine.

* **customerpresent** =1 if the customer is at home, 0=if not, =null not defined yet
* **addressnotfound** =1 if the address is not found by the delivery boy, =0 if found, =null if it's not yet defined.
* **signeddeliver** =1 if the customer signed the deliver, =0 if not, =null if its not defined. 
* **abort** =1 if the deliver must be aborted (for example, an accident), =0 if not.
* **instock**  =1 if the product is in stock, =0 if it's not, =null if it is not defined.
* **picked** =1 if the deliver boy picked and packed the product, =0 if not yet.

### States (ChopSuey's exercise)

It must includes all the possible situation. The real world is not as easy as : sell and money.

* **STATE_PICK**  The deliver boy will pick the food, if any. For example, what if a customer asks for Pekin's duck and the restaurant doesn't have?.
* **STATE_CANCEL**   The order is cancelled. 
* **STATE_TRANSPORT**  The deliver boy is on route to deliver the food.  
* **STATE_ABORTTRANSPORT**   Something happened, the deliver must be aborted. 
* **STATE_HELP**    The deliver boy is ready to deliver but he is not able to find the address or maybe there is nobody, so he calls for a help.  
* **STATE_DELIVERED**  The food is delivered. Our hero returns to base (Chinese restaurant). 
* **STATE_ABORTED**  The transaction is aborted, nobody at home or the address is wrong.

### Transitions (ChopSuey's exercise)

* **STATE_PICK** -> **STATE_CANCEL** (END)  When?. instock=0 (end of the job)
* **STATE_PICK** -> **STATE_TRANSPORT**  When?. instock=1 and picked=1
* **STATE_TRANSPORT** -> **STATE_ABORTTRANSPORT** (END)  When?. abort=1 (for some reason, our boy abort the transport, is it raining?)
* **STATE_TRANSPORT** -> **STATE_DELIVERED** (END)  When?. addressnotfound=0,customerpresent=1 and signeddeliver=1. It is delivered, the customer is present and it signed the deliver (plus a tip, I hope it)
* **STATE_TRANSPORT** -> **STATE_HELP**  When?. addressnotfound=1,customerpresent=0 and signeddeliver<>1. Our deliver calls to home and ask for new instructions. Is it Fake Street #1234 the right address?.  
* **STATE_HELP** -> **STATE_ABORTED** (END) When?. (15 minutes deadline) or if abort=1.  Our deliver called home and yes, the address is fake (it's a shocking surprise)  
* **STATE_HELP** -> **STATE_DELIVERED** (END) When?. addressnotfound=0,customerpresent=1 and signeddeliver=1. It is delivered, the customer is present and it signed the deliver (plus a tip, I hope it)  










  

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

1.0 2018-12-08 First (non beta) version.
