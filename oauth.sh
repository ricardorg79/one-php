#!/bin/bash

# pre
tmp=`readlink -f $0`; export CWD=`dirname $tmp`; source $CWD/util/globals.sh

export NAME="oauth"
export IMAGE="openjdk:8-jre"
export EXTRA_PARAMS="-p $IP:8385:8080 -v /srv/oauth:/app"

startStopFunc $1
