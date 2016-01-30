<?php
namespace Acoulton\Timelapse;

class FrameRateController {

  protected $rate_transitions  = [];

  public function __construct(array $rate_transition_schedule)
  {
    $this->rate_transitions = $rate_transition_schedule;
  }

  public function getFrameRateAt(\DateTimeInterface $time)
  {
    //@todo: inefficient o(n) algorithm
    $current_rate = 1;
    foreach ($this->rate_transitions as $transition) {
     if ($time >= $transition['time']) {
       $current_rate = $transition['rate'];
     } else {
       break;
     }
    }
    return $current_rate;
  }

  
}

