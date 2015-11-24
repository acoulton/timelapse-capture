#!/bin/bash
set -o errexit
set -o nounset
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source "$DIR/environment.sh"
source "$DIR/logecho.sh"
# environment.sh defines CAMERA_HOST
# environment.sh defines IFTT_KEY
BOOT_INITIAL_WAIT=15
BOOT_CHECK_RETRIES=60
BOOT_CHECK_RETRY_DELAY=1
SSH_TIMEOUT=5
SSH_PORT=22

CAMERA_STATUS=2

checkssh()
{
  set +o errexit
  logecho "Check camera SSH connectivity on $CAMERA_HOST:$SSH_PORT"
  nc -z -v -w$SSH_TIMEOUT $CAMERA_HOST $SSH_PORT
  CAMERA_STATUS=$?
  set -o errexit
}

checkssh
if [ "$CAMERA_STATUS" -eq 0 ]; then
  logecho "Camera is reachable, all good"
  exit 0;
fi

logecho "Camera is not reachable, try power cycling"
logecho "Requesting camera power off"
curl -sS -X POST "https://maker.ifttt.com/trigger/fetcam-turn-off/with/key/$IFTT_KEY"
echo ""
logecho "Powered off, waiting 5 seconds"
sleep 5
logecho "Requesting camera power on"
curl -sS -X POST "https://maker.ifttt.com/trigger/fetcam-turn-on/with/key/$IFTT_KEY"
echo ""
logecho "Camera should be coming up, waiting $BOOT_INITIAL_WAIT seconds initially"
sleep $BOOT_INITIAL_WAIT

RETRIES=$BOOT_CHECK_RETRIES
while [[ $RETRIES -ne 0 ]]; do
  checkssh

  if [[ "$CAMERA_STATUS" -eq 0 ]]; then
    logecho "Camera PPTP is up"
    exit 0;
  fi

  ((RETRIES=RETRIES - 1))
  logecho "Unreachable, $RETRIES remaining. Waiting $BOOT_CHECK_RETRY_DELAY"
  sleep $BOOT_CHECK_RETRY_DELAY
done

logecho "Timed out waiting for camera to boot after power cycle"
exit 1
