<?php
use Acoulton\Timelapse\FrameRateController;

class FrameRateControllerTest extends PHPUnit_Framework_TestCase {

  public function test_it_is_initialisable()
  {
    $this->assertInstanceOf('Acoulton\Timelapse\FrameRateController', $this->newSubject());
  }

  public function test_it_returns_current_rate_of_1_with_no_transitions()
  {
    $this->assertSame(1, $this->newSubject([])->getFrameRateAt(new \DateTime('2016-01-02 20:23:20')));
  }

  public function test_it_returns_rate_of_1_before_first_transition()
  {
    $this->assertSame(
     1,
     $this->newSubject([
       ['time' => new \DateTime('2016-04-05 10:20'), 'rate' => 12]
     ])->getFrameRateAt(new \DateTime('2016-04-05 10:19'))
    );
  }

  public function test_it_returns_last_transition_rate_after_last_transition()
  {
    $this->assertSame(
      3,
      $this->newSubject([
        ['time' => new \DateTime('2016-02-03 10:20'), 'rate' => 3]
      ])->getFrameRateAt(new \DateTime('2018-10-13'))
    );
  }

  /**
   * @testWith [{"2016-02-03 10:20": 5, "2016-03-01 15:30": 8},  "2016-02-20 10:00", 5]
   */
  public function test_it_returns_middle_transition_rate_between_two_transitions($rates, $at_time, $expect)
  {
    foreach ($rates as $time => $rate) {
      $transitions[] = ['time' => new \DateTime($time), 'rate' => $rate];
    }
    $this->assertSame(
      $expect,
      $this->newSubject($transitions)->getFrameRateAt(new \DateTime($at_time))
    );
  }

  public function test_it_returns_correct_values_with_long_sequence_of_transitions()
  {
    $prev_rate = 1;
    for ($day = 1; $day < 5; $day ++) {
     $rate = round(rand(1,18));
     $transitions[] = ['time' => new \DateTime("2015-06-$day 12:30:00"), 'rate' => $rate];
     $tests[] = ['time' => new \DateTime("2015-06-$day 12:29:59"), 'rate' => $prev_rate];
     $tests[] = ['time' => new \DateTime("2015-06-$day 12:30:01"), 'rate' => $rate];
     $prev_rate = $rate;
    }

    $subject = $this->newSubject($transitions);
    foreach ($tests as $test) {
      $results[] = [
        'time' => $test['time'],
        'rate' => $subject->getFrameRateAt($test['time'])
      ];
    }

    $this->assertEquals($tests, $results);
  }

  public function test_it_returns_correct_values_with_initial_list_out_of_sequence()
  {
     $this->markTestIncomplete();
  }

  public function test_it_returns_correct_values_when_times_checked_out_of_sequence()
  {
    $this->markTestIncomplete();
  }

  protected function newSubject(array $transitions = [])
  {
    return new FrameRateController($transitions);
  }

}
