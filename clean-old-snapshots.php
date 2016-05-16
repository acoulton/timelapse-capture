#!/usr/bin/env php
<?php
error_reporting(E_ALL);
foreach (glob(__DIR__.'/snapshots/*') as $snap_dir) {
  $clean_before = new \DateTime('-5 days');
  if ( ! is_dir($snap_dir)) {
    continue;
  }

  $snap_date = \DateTime::createFromFormat('Y-m-d', basename($snap_dir));
  if ( ! $snap_date) {
    print "Bad date format: $snap_dir\n";
    continue;
  }

  if ($snap_date < $clean_before) {
    print "Clean $snap_dir\n";
    `rm -rf $snap_dir`;
  }
}
echo "Done\n";
