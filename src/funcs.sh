#!/bin/bash

start_process() {
	echo "docker run --restart always -d $EXTRA_PARAMS --name $NAME $IMAGE"
	docker run --restart always -d $EXTRA_PARAMS --name $NAME $IMAGE
}

stop_process() {
	docker kill $NAME
	docker rm $NAME
}

startStopFunc() {
	action=$1
	case "$action" in
	  start)
		echo "Starting"
		start_process
		;;
	  stop)
		echo "Stopping"
		stop_process
		;;
	  restart)
		stop_process
		start_process
		;;
	  *)
		echo "Usage:"
		echo "   $0 start"
		echo "   $0 stop"
		echo "   $0 restart"
		echo ""
		;;
	esac
}
