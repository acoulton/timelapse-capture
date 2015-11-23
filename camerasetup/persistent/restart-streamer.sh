#!/bin/sh
PROCESSES=`ps`
PID=`echo "$PROCESSES" | grep /bin/ubnt-streamer | sed -rn 's/^ +([0-9]+).*/\1/p'`
echo "Found /bin/ubnt-streamer PID $PID"
kill $PID
echo "Killed"

