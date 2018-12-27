<?php

// there is nothing here but mices🐁🐁 

use eftec\statemachineone\Job;
use eftec\statemachineone\StateMachineOne;
use eftec\statemachineone\Transition;

include "../vendor/autoload.php";

// transition condition
// rules:

// state_pick= to take the product, if not instock then it changes state to state_cancel, otherwise it changes to state_transport
// state_cancel -> close order
// state_transport = to transport the product, it has a duration of 1 hour (or the chicken is for free).
//                  to deliver the product, if customerpresent=false or addressnotfound=true and signeddeliver=false, then change to state_help, if not,  change state_delivered
// state_aborttransport -> close order
// state_help = central will try to contact the customer, it has a duration of 15 minutes (or the deliver boy is returned), then change to state_todeliver ot state_aborted (if time is out)
// state_delivered = return home, change status to SUCCESS.
// state_aborted = fails to deliver product, set field abort=true

