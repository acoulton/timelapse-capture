#!/bin/bash
# Support function for echoing a log value etc with a timestamp
# Include in other files like source $DIR/logecho.sh

logecho () {
  local datestamp=`date --rfc-3339=seconds`
  echo  "[$datestamp] $1"
}
