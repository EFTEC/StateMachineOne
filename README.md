# StateMachineOne
It is State Machine library written on PHP aimed to business process.   
This library has only a simple external dependency, it is a minimalist (yet complete) library with only 3 classes.  

Since this library is PHP native, then it could run in Laravel, Symfony and any other frameworks.  
 

[![Build Status](https://travis-ci.org/EFTEC/StateMachineOne.svg?branch=master)](https://travis-ci.org/EFTEC/StateMachineOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/statemachineone.svg)](https://packagist.org/packages/eftec/statemachineone)
[![Total Downloads](https://poser.pugx.org/eftec/statemachineone/downloads)](https://packagist.org/packages/eftec/statemachineone)
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

- [StateMachineOne](#statemachineone)
  * [What is a state machine?.](#what-is-a-state-machine-)
  * [Notes](#notes)
  * [Example, ChopSuey Chinese Delivery Food.](#example--chopsuey-chinese-delivery-food)
    + [Fields (ChopSuey's exercise)](#fields--chopsuey-s-exercise-)
    + [States (ChopSuey's exercise)](#states--chopsuey-s-exercise-)
    + [Transitions (ChopSuey's exercise)](#transitions--chopsuey-s-exercise-)
    + [Final Code (ChopSuey's example)](#final-code--chopsuey-s-example-)
  * [Other examples](#other-examples)
  * [Transition language](#transition-language)
  * [The transition language is written with the next syntax.](#the-transition-language-is-written-with-the-next-syntax)
    + [Transition when](#transition-when)
    + [Transition set](#transition-set)
    + [Transition timeout (in seconds)](#transition-timeout--in-seconds-)
    + [Transition fulltimeout (in seconds)](#transition-fulltimeout--in-seconds-)
  * [Classes](#classes)
  * [Version](#version)
  * [What is missing](#what-is-missing)


## What is a state machine?.

A State Machine (also called **Automata**) is a procedural execution of a **job** based in **states**. 
Every job must have a single state at the same time, such as "INITIATED","PENDING","IN PROCESS" and so on,
and the job changes of state (**transition**) according to some logic or condition. Such conditions could be a field, a time or a custom function.   


The target of this library is to ease the process to create a state machine for business.  



## Notes

* **Job:** it's the process to run.  A job could have a single state at the same time.  
* **State:** it's the current condition of the job.  
* **Transition:** it's the change from one **state** to another. The transition is conditioned to a set of values, time or a function.  
Also, every transition could have a timeout. If the timeout is reached then the transition is done, no matter the values or the conditions (even if it has the **active** state paused).  The transition could have 3 outcomes:
* * **change** The transition changes of state and the job is keep active. It is only possible to do the transition if the job has the ****active state**** = active.
* * **pause**  The transition changes of state and the job is paused. It is only possible to do the transition if the job has the **active state** = active.
* * **continue**  The transition changes of state and the job resumes of the pause. It is only possible to do the transition if the job has the **active state** = pause or active
* * **stop** The transition changes of state and the job is stopped. It is only possible to do the transition if the job has the **active state** = active or pause.
* **Active:** Every job has an **active state**. There are 4: none,stop,active,inactive,pause. It is different from the states.
So, for example, a job could have the **state**: INPROGRESS and the **active state**: PAUSE.   
* * **none** = the job doesn't exist. It can't change of state, neither it is loaded (from the database) by default
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

## Other examples

[Example/BuyMilk.php](example/BuyMilk.php) (buy milk)

[Example/Car.php](example/Car.php) (car parking)

```php
<?php
use eftec\statemachineone\StateMachineOne;

include "vendor/autoload.php";

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
	,'when wait timeout 900','change'); // it waits 15 minutes max.
$smachine->addTransition(STATE_HELP,STATE_DELIVERED
	,'when addressnotfound = 0 and customerpresent = 1 and signeddeliver = 1','change');


$msg=$smachine->fetchUI();
$smachine->checkAllJobs();

$smachine->viewUI(null,$msg); // null means it takes the current job
```


## Transition language

Let's say the next transition

```php
$smachine->addTransition(STATE_PICK,STATE_CANCEL
	,'when instock = 0 set abort = 1','stop');

```

The transition is written as follow:
* initial state
* end state
* Transition language
* outcome, it could be **change** (default value),**stop**,**pause** and **continue**
* * **change** means the state will change from **initial state** to **end state** if it meets the condition (or timeout).  It will only change if the state is active.  
* * **stop** means the state will change and the job will stop (end of the job)  
* * **pause** it means the state will change and the job will pause.  A job paused can't change of state, even if it meets the condition.  
* * **continue** it means the state will change and the job will continue from pause.  

## The transition language is written with the next syntax.
> _when_ **var1** = **var2** and **var3** = **var4** or **var4** = **var5**
> _set_ **var1** = **var2** , **var3** = **var4**
> _timeout_ **var1**
> _fulltimeout_ **var2**
* there are two operations we could do **when** and/or **set**

### Transition when
The transition happens when this condition meets. For example:  
> when field=0  // it happens when the field is zero.   
> when $var='hi' // it happens when the global variable is 'hi'   
> when fn()=44 // the transition is triggered when the function fn() returns 44  
> when timeout // it waits until the timeout. it is the same than "when 1=2". The transition is never executed (never until timeout)
> when always // its always true. It is the same than "when 1=1". The transition is always executed

It compares a constant. The binary operator for comparison are
* = Equals
* **&lt;&gt;** Not equals
* **&lt; &lt;=** Less and less than
* **&gt; &gt;=** Great and great than
* **contain** If a text contains other.
> when field contain 'text'

Values of the field could be as the next ones:
* **field** = it is a field of the job.
> when field = field2  // when field (of the job) is equals to field2
* **$var** = it is a global variable (php)
* **777** = it is a numeric constant
> when field = 777 // when field is equals to 777
* **"AAA"**, **'aaa'** = it is a literal     
> when field = 'hello' // when field is equals to the textr hello
* **function()** = it is a global function. Every function must have the parameter $job.
> when field = somefunc() // function somefunc(Job $job) {...} 
* **null()** it is the null value
> when field = null() 
* **true()** it is the true value (true)
> when field = true() // when field is equals to true
* **false()** it is the false value (false)
> when field = true() // when field is equals to false
* **on()** it is the on value (1)  
* **off()** it is the off value (0)  
* **undef()** it is the undefined value (-1)  
* **flip()** indicates that the value will be flipped (1=>0 and 0=>1). Example (x=1) x = flip(), now (x=0). If the value is not zero, then it's flipped to zero.    
> set field=flip() // it is only valid for set.
* **now()** it defines the current timestamp (in seconds)
* **interval()** it returns the current interval between now and the last state.
* **fullinterval()** it returns the current interval between now and the start of the job.

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

> set field = 0  

It sets the field to the value 0

> set field + 1

It increases the value of field by 1 (field=field+1)

> set field - 1

It decreases the value of field by 1 (field=field-1)

### Transition timeout (in seconds)

It sets the timeout between the time of current state and the current time.
If a timeout happens, then the transition is executed.

> timeout 3600   // 1 hour timeout  
> timeout field // timeout by field, it is calculated each time.  

### Transition fulltimeout (in seconds)

It sets the timeout between the time of initial state and the current time.
If a timeout happens, then the transition is executed.

> fulltimeout 3600   // 1 hour timeout  
> fulltimeout field // timeout by field, the field is evaluated each time.    

## GUI

This library has a build-in GUI for testing.

![GUID](Docs/uid.jpg)


## Classes
[StateMachineOne](StateMachineOne.md) It is the main class.
[Job](Job.md) It is the model class for the job  
[Transition](Transition.md) It is the model class for the transitions.  

## Version

* 1.6 2018-12-26 Now MiniLang is a separate dependency.   
* 1.5 2018-12-23 Xmas update (btw porca miseria).     
* * Now the language is parsed differently.  The space is not mandatory anymore.   
* * "when timeout" is not deprecated. Now it is called as "when always"    
* 1.4 2018-12-12 
* * Some fixes.  
* 1.3 2018-12-11 
* * Added addEvent() and callEvent()   
* * Added timeout and fulltimeout to the transition language  
* * Now transitions doesn't require the timeout.  
* * idRef are not longer used.    
* 1.2 2018-12-09 Updated dependency  
* 1.1 2018-12-09 Some corrections.  
* 1.0 2018-12-08 First (non beta) version.

## What is missing

* ~~events and timeout~~
* Most unit test, ~~now it is only the barebone.~~ the unit test is real but it's still basic. 
* Increase the log features.  