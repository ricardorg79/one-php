#!/bin/bash

# pre
tmp=`readlink -f $0`; export CWD=`dirname $tmp`; source $CWD/util/globals.sh

export NAME="gerrit"
export IMAGE="gerritcodereview/gerrit"
export EXTRA_PARAMS="-v /srv/gerrit/gerrit/cache:/var/gerrit/cache \
		-v /srv/gerrit/gerrit/db:/var/gerrit/db \
		-v /srv/gerrit/gerrit/etc:/var/gerrit/etc \
		-v /srv/gerrit/gerrit/git:/var/gerrit/git \
		-v /srv/gerrit/gerrit/index:/var/gerrit/index \
		-v /srv/gerrit/gerrit/hooks:/var/gerrit/hooks \
		-v /srv/gerrit/gerrit/lib:/var/gerrit/lib \
		-v /srv/gerrit/gerrit/plugins:/var/gerrit/plugins \
		-v /srv/gerrit/gerrit/data:/var/gerrit/data \
		-p $IP:8383:8080 -p $IP:29418:29418"

startStopFunc $1
