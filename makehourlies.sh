#!/bin/bash
set -o nounset
set -o errexit
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source $DIR/logecho.sh
TODAY=`date +%Y-%m-%d`

logecho "Building hourly videos from today's unconverted snapshots"
for SNAP_DIR in `find $DIR/snapshots -mindepth 1 -type d -name "$TODAY"`; do
  SNAP_DATE="${SNAP_DIR##*/}"
  VIDEO_PATH="$DIR/videos/$SNAP_DATE"
  logecho "Processing for $SNAP_DATE in $SNAP_DIR"
  if [ ! -d "$VIDEO_PATH" ]; then
    logecho "Creating video path in $VIDEO_PATH"
    mkdir -p "$VIDEO_PATH"
  fi

  logecho "Removing previous part-hour videos"
  rm -f $VIDEO_PATH/$SNAP_DATE-??-partial.mpg

  for hour in {00..23}; do
    HOURLY_VID="$VIDEO_PATH/$SNAP_DATE-$hour-hourly.mpg"
    SNAPS_LIST=`find $SNAP_DIR -name "$SNAP_DATE-$hour-*.jpeg"`
    if [ -z "$SNAPS_LIST" ]; then
      logecho "No snapshots for hour $hour"
    elif [ -s "$HOURLY_VID" ]; then
      logecho "$HOURLY_VID already created"
    else
      logecho "Building $HOURLY_VID for hour $hour"
      cat $SNAP_DIR/$SNAP_DATE-$hour-*.jpeg \
        | avconv -f image2pipe -codec:v mjpeg -i - -pix_fmt yuvj420p -r 30 -c:v libx264  -y "$HOURLY_VID" > "$HOURLY_VID.log"
      #chmod 0744 "$HOURLY_VID"
      if [ ! -s "$HOURLY_VID" ]; then
        logecho "Video $HOURLY_VID was not generated or had 0 size!"
        exit 1
      fi
      logecho "Done"
    fi
  done
done

logecho "Marking current-hour video as partial"
DATE=`date +%Y-%m-%d`
HOUR=`date +%H`
mv "$DIR/videos/$DATE/$DATE-$HOUR-hourly.mpg" "$DIR/videos/$DATE/$DATE-$HOUR-partial.mpg"

logecho "Removing previous part-day videos"
rm -f $DIR/videos/daily/*-partial.mpg

logecho "Producing daily videos"
DAILY_VID_PATH="$DIR/videos/daily"
if [ ! -d "$DAILY_VID_PATH" ]; then
  mkdir -p "$DAILY_VID_PATH"
fi

for VIDEO_DIR in `find $DIR/videos -mindepth 1 -type d -name "$TODAY"`; do
  VIDEO_DATE="${VIDEO_DIR##*/}"
  DAILY_VID="$DAILY_VID_PATH/$VIDEO_DATE-daily.mpg"
  if [ -s "$DAILY_VID" ]; then
    logecho "$DAILY_VID for $VIDEO_DIR already exists"
  else
    logecho "Producing daily video $DAILY_VID for $VIDEO_DIR"
    cat $VIDEO_DIR/*.mpg > $DAILY_VID
  fi
done

logecho "Marking today's daily video as partial"
mv "$DAILY_VID_PATH/$DATE-daily.mpg" "$DAILY_VID_PATH/$DATE-partial.mpg"

logecho "Building combined videos"
COMBINED_VID_PATH="$DIR/videos/combined"
if [ ! -d "$COMBINED_VID_PATH" ]; then
  mkdir -p "$COMBINED_VID_PATH"
fi

#logecho "Updating combined-to-now.mpg"
#cat $DAILY_VID_PATH/*.mpg > "$COMBINED_VID_PATH/combined-to-now.mpg"

logecho "Updating today-so-far.mpg"
cat $DAILY_VID_PATH/$DATE*.mpg > "$COMBINED_VID_PATH/today-so-far.mpg"

for MPEG_PATH in `find $COMBINED_VID_PATH -name "*.mpg"`; do
  logecho "Converting $MPEG_PATH to public mp4"
  MPEG_FILE="${MPEG_PATH##*/}"
  avconv -i "$MPEG_PATH" -y /var/www/html/$MPEG_FILE.mp4 > $MPEG_PATH.mp4.log
done

logecho "All generated videos up to date"
