#!/bin/sh
echo "Checking for differences in streamer config"
diff /var/etc/persistent/ubnt-streamer.conf.custom /etc/ubnt-streamer.conf
DIFF_RESULT="$?"
if [ "$DIFF_RESULT" = "0" ]; then
  echo "Files match, correct config"

else  
  echo "Replacing config and restarting streamer"
  cp /var/etc/persistent/ubnt-streamer.conf.custom /etc/ubnt-streamer.conf
  /var/etc/persistent/restart-streamer.sh
  RESTART_RESULT=$?
  echo "Waiting 120 seconds for streamer to restart"
  sleep 120
  exit $RESTART_RESULT
fi  
