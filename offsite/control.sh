#!/bin/sh

CMD="$1"
SID="$2"
NAME="ts_$SID"
STEAM="/home/tempservers/users/ts$SID"

while [ -f $STEAM/.lock ]
do
	sleep 3
done

while [ $# -gt 0 ]
do
	case "$1" in
		'-game') GAMEID="$2"; shift;;
		'-ip') IP="$2"; shift;;
		'-port') PORT="$2"; shift;;
		'-maxplayers') SIZE="$2"; shift;;
		'-map') MAP="$2"; shift;;
		'-rcon') RCON="$2"; shift;;
	esac
	shift
done

case "$GAMEID" in
	'tf')
		GAME='tf'
		STEAM="$STEAM/orangebox"
		DAEMON='srcds_run'
	;;
	'dods')
		GAME='dod'
		STEAM="$STEAM/orangebox"
		DAEMON='srcds_run'
	;;
	'css')
		GAME='cstrike'
		STEAM="$STEAM/css"
		DAEMON='srcds_run'
	;;
	'ins')
		GAME='insurgency'
		DAEMON='srcds_run'
	;;
	'l4d1')
		GAME='left4dead'
		STEAM="$STEAM/l4d"
		DAEMON='srcds_run'
	;;
	'l4d2')
		GAME='left4dead2'
		STEAM="$STEAM/left4dead2"
		DAEMON='srcds_run'
	;;
	'cstrike'|'dod'|'tfc')
		GAME="$GAMEID"
		DAEMON='hlds_run'
	;;
	*)
		echo "Invalid Game $GAMEID"
		exit
esac

service_start() {
	case "$DAEMON" in
		'srcds_run')
			OPTS="-game $GAME -ip $IP -port $PORT -maxplayers $SIZE +map $MAP +rcon_password $RCON -nobots -nohltv -autoupdate -pidfile $STEAM/$NAME.pid"
		;;
		'hlds_run')
			OPTS="-game $GAME +ip $IP +port $PORT -maxplayers $SIZE +map $MAP +rcon_password $RCON -autoupdate -pidfile $STEAM/$NAME.pid"
		;;
	esac
	
	INTERFACE="/usr/bin/screen -A -m -d -S $NAME"

	if [ ! -f $STEAM/$NAME.pid ] && [ ! -f $STEAM/$NAME-screen.pid ]; then
		if [ -x $STEAM/$DAEMON ]; then
			echo "Starting server..."
			cd $STEAM
			$INTERFACE $STEAM/$DAEMON $OPTS
			sleep 1
			ps -ef | grep SCREEN | grep "$NAME" | grep -v grep | awk '{ print $2}' > $STEAM/$NAME-screen.pid
			
			echo "Server started."
		fi
	else
		echo -e "Cannot start server.  Server is already running."
	fi
}

service_stop() {
	if [ -f $STEAM/$NAME.pid ] && [ -f $STEAM/$NAME-screen.pid ]; then
		echo "Stopping server..."
		for id in `cat $STEAM/$NAME-screen.pid`
			do kill -9 $id
			rm -rf $STEAM/$NAME-screen.pid
			break
		done
		rm -rf $STEAM/$NAME.pid
		screen -wipe 1> /dev/null 2> /dev/null
		echo "Server stopped."
	else
		rm -rf $STEAM/$NAME-screen.pid
		rm -rf $STEAM/$NAME.pid
		echo -e "Cannot stop server.  Server is not running."
	fi	
}	


case "$CMD" in
	'start')
		service_start
		;;
	'stop')
		service_stop
		;;
	'restart')
		service_stop
		sleep 1
		service_start
		;;
	*)
		echo "Usage $0 start|stop|restart name"
esac