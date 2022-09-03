<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace eftec\statemachineone;

use RuntimeException;

class Pending implements StateSerializable
{
    /** @var array=[\eftec\statemachineone\Pending::factoryScheduleItem()] */
    public $schedule;

    public $cyclical = false;
    public $cyclicalInterval = 0;

    /**
     * @param int  $time
     * @param bool $done
     *
     * @return array
     */
    public static function factoryScheduleItem(int $time = 0, bool $done = false): array
    {
        return ['time' => $time, 'done' => $done];
    }


    /**
     * Pending constructor.
     *
     * @param array $schedule =[\eftec\statemachineone\Pending::factoryScheduleItem()]
     * @param bool  $cyclical
     * @param int   $cyclicalInterval
     */
    public function __construct(array $schedule = [], bool $cyclical = false, int $cyclicalInterval = 0)
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
    public function getLastIndex(?int $time = null): int
    {
        $time = $time ?? time();
        $result = 0;
        $index = -1;
        foreach ($this->schedule as $k => $s) {
            if ($s['time'] < $time && $s['time'] > $result) {
                $result = $s['time'];
                $index = $k;
            }
        }
        return $index;
    }

    public function toString(): string
    {
        return serialize($this->schedule) . ';;' . serialize($this->cyclical) . ';;' . serialize($this->cyclicalInterval);
    }

    public function fromString($job, $string)
    {
        $arr = explode(';;', $string);
        $this->schedule = unserialize($arr[0], ['allowed_classes' => false]);
        $this->cyclical = unserialize($arr[1], ['allowed_classes' => false]);
        $this->cyclicalInterval = unserialize($arr[2], ['allowed_classes' => false]);
    }

    /**
     * It sets the parent
     *
     * @param Job $job
     *
     * @return void
     */
    public function setParent($job): void
    {
        throw new RuntimeException("Not implemented");
    }
}
