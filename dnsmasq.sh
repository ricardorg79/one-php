#!/bin/bash

# pre
tmp=`readlink -f $0`; export CWD=`dirname $tmp`; source $CWD/util/globals.sh

export NAME="dnsmasq"
export IMAGE="andyshinn/dnsmasq:2.78"
export EXTRA_PARAMS="-d \
		-p $IP:53:53/tcp \
		-p $IP:53:53/udp \
		-v /srv/dnsmasq.conf:/etc/dnsmasq.conf \
		--cap-add=NET_ADMIN"

startStopFunc $1
