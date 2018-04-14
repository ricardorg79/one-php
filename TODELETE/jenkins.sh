#!/bin/bash

# pre
tmp=`readlink -f $0`; export CWD=`dirname $tmp`; source $CWD/util/globals.sh

export NAME="jenkins"
export IMAGE="jenkins/jenkins:lts"
export EXTRA_PARAMS="-p $IP:8384:8080 -v /srv/jenkins:/var/jenkins_home"

startStopFunc $1
