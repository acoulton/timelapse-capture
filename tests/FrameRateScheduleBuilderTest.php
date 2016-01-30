<?php
use Acoulton\Timelapse\FrameRateScheduleBuilder;

class FrameRateScheduleBuilderTest extends PHPUnit_Framework_TestCase {

  public function test_it_is_initialisable()
  {
    $this->assertInstanceOf('Acoulton\Timelapse\FrameRateScheduleBuilder', $this->newSubject());
  }

  public function test_it_returns_empty_schedule_for_no_input()
  {
    $this->assertSame([], $this->newSubject()->listTransitions(new \DateTime, new \DateTime('tomorrow')));
  }

  public function test_it_returns_schedule_with_single_manual_rate_change()
  {
    $subject = $this->newSubject();
    $subject->setRate(new \DateTime('2016-01-09 10:02:23'), 5);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-09 10:02:23'), 'rate' => 5]
      ],
      $subject->listTransitions(new \DateTime('2016-01-01'), new \DateTime('2016-12-31'))
    );
  }

  public function test_it_returns_schedule_with_multiple_manual_changes()
  {
    $subject = $this->newSubject();
    $subject->setRate(new \DateTime('2016-01-09 10:02:23'), 5);
    $subject->setRate(new \DateTime('2016-02-04 15:05:23'), 9);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-09 10:02:23'), 'rate' => 5],
        ['time' => new \DateTime('2016-02-04 15:05:23'), 'rate' => 9],
      ],
      $subject->listTransitions(new \DateTime('2016-01-01'), new \DateTime('2016-12-31'))
    );
  }

  public function test_it_only_returns_manual_changes_within_date_range_of_schedule_requesed()
  {
    $subject = $this->newSubject();
    $subject->setRate(new \DateTime('2016-01-09 10:02:23'), 5);
    $subject->setRate(new \DateTime('2016-02-04 15:05:23'), 9);
    $subject->setRate(new \DateTime('2016-02-01 15:05:23'), 4);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-02-01 15:05:23'), 'rate' => 4],
      ],
      $subject->listTransitions(new \DateTime('2016-02-01'), new \DateTime('2016-02-02'))
    );
  }

  public function test_it_returns_schedule_with_transitions_on_single_recurring_profile()
  {
    $subject = $this->newSubject();
    $subject->addRecurringTransition(new \DateTime('2016-01-01 08:00:00'), new \DateInterval('P1D'), 7);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-01 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-02 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-03 08:00:00'), 'rate' => 7],
      ],
      $subject->listTransitions(new \DateTime('2015-12-31 22:03'), new \DateTime('2016-01-03 14:00'))
    );
  }

  public function test_it_returns_schedule_with_multiple_recurring_transitions()
  {
    $subject = $this->newSubject();
    $subject->addRecurringTransition(new \DateTime('2016-01-01 08:00:00'), new \DateInterval('P1D'), 7);
    $subject->addRecurringTransition(new \DateTime('2016-01-01 18:00:00'), new \DateInterval('P1D'), 3);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-01 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-01 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-02 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-02 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-03 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-03 18:00:00'), 'rate' => 3],
      ],
      $subject->listTransitions(new \DateTime('2015-12-31 22:03'), new \DateTime('2016-01-03 20:00'))
    );
  }

  public function test_it_only_returns_recurring_transitions_in_requested_schedule_range()
  {
    $subject = $this->newSubject();
    $subject->addRecurringTransition(new \DateTime('2016-01-01 08:00:00'), new \DateInterval('P1D'), 7);
    $subject->addRecurringTransition(new \DateTime('2016-01-01 18:00:00'), new \DateInterval('P1D'), 3);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-01 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-02 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-02 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-03 08:00:00'), 'rate' => 7],
      ],
      $subject->listTransitions(new \DateTime('2016-01-01 17:59:03'), new \DateTime('2016-01-03 14:00'))
    );
  }

  public function test_it_returns_schedule_with_combined_recurring_and_manual_transitions()
  {
    $subject = $this->newSubject();
    $subject->addRecurringTransition(new \DateTime('2016-01-01 08:00:00'), new \DateInterval('P1D'), 7);
    $subject->addRecurringTransition(new \DateTime('2016-01-01 18:00:00'), new \DateInterval('P1D'), 3);
    $subject->setRate(new \DateTime('2016-01-02 14:30'), 5);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-01 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-02 08:00:00'), 'rate' => 7],
        ['time' => new \DateTime('2016-01-02 14:30:00'), 'rate' => 5],
        ['time' => new \DateTime('2016-01-02 18:00:00'), 'rate' => 3],
        ['time' => new \DateTime('2016-01-03 08:00:00'), 'rate' => 7],
      ],
      $subject->listTransitions(new \DateTime('2016-01-01 17:59:03'), new \DateTime('2016-01-03 14:00'))
    );
  }

  public function test_it_overrides_recurring_transition_with_manual_transition_at_same_time()
  {
    $subject = $this->newSubject();
    $subject->addRecurringTransition(new \DateTime('2016-01-01 13:00'),  new \DateInterval('PT1H'), 5);
    $subject->setRate(new \DateTime('2016-01-01 14:00'), 4);
    $this->assertEquals(
      [
        ['time' => new \DateTime('2016-01-01 13:00:00'), 'rate' => 5],
        ['time' => new \DateTime('2016-01-01 14:00:00'), 'rate' => 4],
        ['time' => new \DateTime('2016-01-01 15:00:00'), 'rate' => 5],
      ],
      $subject->listTransitions(new \DateTime('2016-01-01 12:30:00'), new \DateTime('2016-01-01 15:30:00'))
    );
  }

  protected function newSubject()
  {
    return new FrameRateScheduleBuilder();
  }


}

