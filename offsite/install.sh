#!/bin/sh

CMD="$1"
OWNER='tempservers'
GROUP='ftpgroup';

while [ $# -gt 0 ]
do
	case "$1" in
		'-g') GAME="$2"; shift;;
		'-s') SID="$2"; shift;;
	esac
	shift
done

UDIR="users/ts$SID"
LOCK="$UDIR/.lock"

if [ ! -f $LOCK ]; then
	case "$CMD" in
		'install')
			mkdir $UDIR
			touch $LOCK
			cp -R installs/$GAME/* $UDIR
			chown -R $OWNER:$GROUP $UDIR
			rm -f $LOCK
		;;
		'uninstall')
			rm -rf $UDIR
		;;
		'reinstall')
			touch $LOCK
			rm -rf $UDIR/*
			cp -R installs/$GAME/* $UDIR
			chown -R $OWNER:$GROUP $UDIR
			rm -f $LOCK
		;;
	esac
fi