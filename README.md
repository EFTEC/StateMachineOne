# StateMachineOne
State Machine library for PHP

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
## Method __construct()


### Parameters:  
## Method __construct()


### Parameters:  
## Method __construct()


### Parameters:  
## Method __construct()


### Parameters:  
## Method __construct()


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
