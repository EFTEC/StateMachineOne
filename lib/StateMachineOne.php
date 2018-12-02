<?php
namespace eftec\statemachineone;
// cambia por tiempo
// cambia por estado


use eftec\MessageItem;
use eftec\MessageList;

class StateMachineOne {
    var $counter;
    /** @var StateMachineJob[] */
    var $jobs;

    public function createJob($idRef,$fields,$initState) {
        $this->counter++;
        $this->jobs[$this->counter]->idRef=$idRef;
        $this->jobs[$this->counter]->dateInit=time();
        $this->jobs[$this->counter]->dateEnd=0;
        $this->jobs[$this->counter]->state=$initState;
        $this->jobs[$this->counter]->fields=$fields;
        $this->jobs[$this->counter]->messages=new MessageList();
        return $this->counter;
    }
    public function checkState($idJob) {
        return false;
    }
    public function checkAllState() {
        foreach($this->jobs as $idx=>$job) {
            $this->checkState($idx);
        }
    }
    public function changeState($idJob,$newState) {
        $this->jobs[$idJob]['state']=$newState;
    }
}

