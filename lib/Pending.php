<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace eftec\statemachineone;


use RuntimeException;

class Pending implements StateSerializable
{
    /** @var array=[\eftec\statemachineone\Pending::factoryScheduleItem()]  */
    public $schedule;
    
    public $cyclical=false;
    public $cyclicalInterval=0;

    /**
     * @param int  $time
     * @param bool $done
     *
     * @return array
     */
    public static function factoryScheduleItem($time=0,$done=false) {
        return ['time'=>$time,'done'=>$done];
    }

    
    /**
     * Pending constructor.
     *
     * @param array $schedule=[\eftec\statemachineone\Pending::factoryScheduleItem()]
     * @param bool  $cyclical
     * @param int   $cyclicalInterval
     */
    public function __construct($schedule=[], $cyclical=false, $cyclicalInterval=0)
    {
        $this->schedule = $schedule;
        $this->cyclical = $cyclical;
        $this->cyclicalInterval = $cyclicalInterval;
    }

    /**
     * Returns the index of the last past Pending.
     * @param int|null $time timestamp
     *
     * @return int
     */
    public function getLastIndex($time=null) {
        $time=($time===null)?time():$time;
        $result=0;
        $index=-1;
        foreach($this->schedule as $k=>$s) {
            if ($s['time']<$time && $s['time']>$result) {
                $result=$s['time'];
                $index=$k;
            }
        }
        return $index;
    }
    
    public function toString()
    {
        return serialize($this->schedule).';;'.serialize($this->cyclical).';;'.serialize($this->cyclicalInterval);
    }

    public function fromString($job,$string)
    {
        $arr = explode(';;', $string);
        $this->schedule=unserialize($arr[0]);
        $this->cyclical=unserialize($arr[1]);
        $this->cyclicalInterval=unserialize($arr[2]);
    }

    /**
     * It sets the parent
     *
     * @param Job $job
     *
     * @return void
     */
    public function setParent($job)
    {
       throw new RuntimeException("Not implemented");
    }
}