#!/bin/bash
set -o errexit
set -o nounset

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source "$DIR/environment.sh"
source "$DIR/logecho.sh"
# environment.sh defines CAMERA_HOST
CAMERA_USER=ubnt
CAMERA_KEY="$DIR/.ssh/fetcam_ubnt_rsa"

"$DIR/ensure-camera-reachable.sh"

logecho "Check camera configuration"
ssh -i "$CAMERA_KEY" "$CAMERA_USER"@"$CAMERA_HOST" /etc/persistent/ensure-correct-streamer-config.sh

SNAPSHOT_DATE=`date +%Y-%m-%d`
SNAPSHOT_TIME=`date +%H-%M-%S`
SNAPSHOT_PATH="$DIR/snapshots/$SNAPSHOT_DATE"
if [ ! -d "$SNAPSHOT_PATH" ]; then
  logecho "Creating snapshot directory $SNAPSHOT_PATH"
  mkdir -p "$SNAPSHOT_PATH"
fi

SNAPSHOT_FILE="$SNAPSHOT_PATH/$SNAPSHOT_DATE-$SNAPSHOT_TIME.jpeg"
logecho "Capturing to $SNAPSHOT_FILE"
scp -i "$CAMERA_KEY" "$CAMERA_USER"@"$CAMERA_HOST":/tmp/ch00.jpeg "$SNAPSHOT_FILE"
logecho "Done"

logecho "Updating most recent snapshot"
cp "$SNAPSHOT_FILE" "$DIR/report/last-snapshot.jpeg"
