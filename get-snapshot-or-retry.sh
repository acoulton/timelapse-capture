#!/bin/bash
set -o errexit
set -o nounset

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
source "$DIR/logecho.sh"

JUNIT_FILE="$DIR/junit.xml"
JUNIT_TEST1=''
JUNIT_TEST2='<skipped />'

write_junit () {
  logecho "Writing junit file in $JUNIT_FILE"
  echo '<?xml version="1.0" encoding="UTF-8"?><testsuite tests="2">' > "$JUNIT_FILE"
  echo '<testcase classname="snapshot" name="First Attempt">' >> "$JUNIT_FILE"
  echo "  $JUNIT_TEST1" >> "$JUNIT_FILE"
  echo '</testcase>' >> "$JUNIT_FILE"
  echo '<testcase classname="snapshot" name="Second Attempt">' >> "$JUNIT_FILE"
  echo "  $JUNIT_TEST2" >> "$JUNIT_FILE"
  echo '</testcase>' >> "$JUNIT_FILE"
  echo '</testsuite>' >> "$JUNIT_FILE"
}


logecho "Taking snapshot, first attempt"
"$DIR/get-snapshot.sh" && EXITCODE=$? || EXITCODE=$?

if [ "$EXITCODE" == 0 ]; then
  logecho "Success on first attempt"
  write_junit
  exit 0
fi

logecho "Snapshot failure, exit code $EXITCODE"
JUNIT_TEST1="<failure message=\"Exit Code $EXITCODE\" />"

logecho "Taking snapshot, second attempt"
"$DIR/get-snapshot.sh" && EXITCODE=$? || EXITCODE=$?

if [ "$EXITCODE" == 0 ]; then
  logecho "Success on second attempt"
  JUNIT_TEST2=''
  write_junit
  exit 0
else
  logecho "Snapshot failure, exit code $EXITCODE"
  JUNIT_TEST2="<failure message=\"Exit Code $EXITCODE\" />"
  write_junit
  exit $EXITCODE
fi
