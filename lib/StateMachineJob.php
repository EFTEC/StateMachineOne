<?php
namespace eftec\statemachineone;
use eftec\MessageList;

class StateMachineJob {
    var $idRef;
    var $dateInit;
    var $dateEnd;
    var $state;
    var $fields;
    /** @var MessageList */
    var $messages;
    var $log;
}