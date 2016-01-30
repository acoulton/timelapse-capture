#!/usr/bin/php
<?php
use Acoulton\Timelapse\FrameRateController;
use Acoulton\Timelapse\FrameRateScheduleBuilder;

error_reporting(E_ALL);
ini_set('display_errors',1);
require(__DIR__.'/vendor/autoload.php');

$start_at = new \DateTime('2015-11-26');
$end_at   = new \DateTime('2015-12-01');

if ($is_tty = posix_isatty(STDOUT)) {
  print "!!! Not piped to avconv, running in dry-run mode !!!\n";
}

// Rates currently used as relative speed - so eg a rate of 3 will drop 1/3 of snapshots
$day_rate           = 1;
$night_rate         = 4;
$weekend_rate       = 3;
$weekend_night_rate = 6;


$schedule = new FrameRateScheduleBuilder;

// General overnight speedup
$schedule->addRecurringTransition(new \DateTime('2015-11-16 20:00'), new \DateInterval('P1D'), $night_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-16 07:00'), new \DateInterval('P1D'), $day_rate);

// Weekends keep at overnight rate, override each transition point
$schedule->addRecurringTransition(new \DateTime('2015-11-20 20:00'), new \DateInterval('P7D'), $weekend_night_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-21 07:00'), new \DateInterval('P7D'), $weekend_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-21 17:00'), new \DateInterval('P7D'), $weekend_night_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-21 20:00'), new \DateInterval('P7D'), $weekend_night_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-22 07:00'), new \DateInterval('P7D'), $weekend_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-22 17:00'), new \DateInterval('P7D'), $weekend_night_rate);
$schedule->addRecurringTransition(new \DateTime('2015-11-22 20:00'), new \DateInterval('P7D'), $weekend_night_rate);

$rates = new FrameRateController($schedule->listTransitions($start_at, $end_at));

$current_rate = NULL;
$total_frames = 0;
foreach (new \DatePeriod($start_at, new \DateInterval('P1D'), $end_at) as $video_day) {
  foreach (glob(__DIR__.'/snapshots/'.$video_day->format('Y-m-d').'/*.jpeg') as $snapshot) {
    $snap_time = \DateTime::createFromFormat('Y-m-d-H-i-s', basename($snapshot, '.jpeg'));
    $new_rate  = $rates->getFrameRateAt($snap_time);
    if ($new_rate !== $current_rate) {
      if ($is_tty) {
        print "x$new_rate at ".$snap_time->format('Y-m-d H:i:s')."\n";
      }
      $snap_index   = 1;
      $current_rate = $new_rate;
    }

    // @todo very primitive implementation, drops frames even if there are missing snapshots in same period
    // should instead track time between frames and only drop if required
    // and ideally duplicate frames if in "slo-mo"
    if (($current_rate === 1) OR (($snap_index % $current_rate) === 1)) {
      $total_frames++;
      if ($is_tty) {
        print "   > ".$snap_time->format('Y-m-d H:i:s')."\n";
      } else {
        // Just output the file for piping into avconv
        readfile($snapshot);
      }
    } elseif ($is_tty) {
      // This snapshot skipped to achieve frame rate
      print "     x ".$snap_time->format('Y-m-d H:i:s')."\n";
    }

    $snap_index++;
  }
}

if ($is_tty) {
  $frame_rate  = 30;
  print "Total video frames: $total_frames\n";
  $play_seconds = ($total_frames / $frame_rate);
  print "Total video time:   ".gmdate('H:i:s', ceil($play_seconds))."\n";
}

