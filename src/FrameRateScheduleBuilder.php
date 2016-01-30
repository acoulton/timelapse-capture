<?php
namespace Acoulton\Timelapse;

class FrameRateScheduleBuilder {


  protected $fixed_rate_periods = [];
  protected $recurring_periods  = [];

  public function listTransitions(\DateTimeInterface $from, \DateTimeInterface $to)
  {
    $transitions = [];

    foreach ($this->recurring_periods as $period) {
      foreach (new \DatePeriod($period['first'], $period['interval'], $to) as $time) {
        if ($time > $from) {
          $transitions[$time->format('c')] = ['time' => $time, 'rate' => $period['rate']];
        }
      }
    }

    foreach ($this->fixed_rate_periods as $period) {
      if ($this->isBetween($period['start'], $from, $to)) {
        $transitions[$period['start']->format('c')] = ['time' => $period['start'], 'rate' => $period['rate']];
      }
    }


    usort($transitions, function ($tr1, $tr2) {
      return $tr1['time']->getTimestamp() - $tr2['time']->getTimestamp();
    });

    return array_values($transitions);
  }

  protected function isBetween(\DateTimeInterface $date, \DateTimeInterface $from, \DateTimeInterface $to)
  {
    return (
     ($date >= $from)
     AND
     ($date < $to)
    );
  }

  public function addRecurringTransition(\DateTime $first, \DateInterval $recurrence, $rate)
  {
    $this->recurring_periods[] = ['first' => $first, 'interval' => $recurrence, 'rate' => $rate];
  }

  public function setRate(\DateTimeInterface $start,  $rate)
  {
    $this->fixed_rate_periods[] = ['start' => $start,  'rate' => $rate];
  }

}

