#!/bin/bash
#baishaohua

CTIME=`date "+%Y-%m-%d-%H-%M"`
OLD_VERSION=$2

if [ -d "/data/plist_bak/" ];then
    echo ""
else
    mkdir /data/plist_bak/
    echo "begin: create backup directory"
    chown www:www /data/plist_bak/
fi




PLIST_BAK(){
  if [ -d "/data/plist_bak/plist_${CTIME}" ];then
    echo "backup is too busy"
  else
	  /bin/cp -r /data/plist /data/plist_bak/plist_${CTIME}
    echo "backup successfully Version < plist_${CTIME} >"
  fi
}

BAK_LIST(){
     cd  /data/plist_bak/
     for i in `ls`;do echo $i;done
}

PLIST_ROLLBACK(){

	/bin/cp -r /data/plist_bak/${OLD_VERSION}/* /data/plist/
	
}

ZAIBEI_PLIST(){
  /bin/cp -r /data/plist/zaibei/* /data/plist/
  chown -R www:www /data/plist
  echo "finish"
}

usage(){
	echo "Usage $0 { backup | zaibei | list | rollback backup_version }"
}

case $1 in
	backup)
		PLIST_BAK;
		;;
	list)
		BAK_LIST;
		;;
	rollback)
		PLIST_ROLLBACK;
		;;
  zaibei)
    PLIST_BAK;
    ZAIBEI_PLIST;
    ;;
	*)
		usage;
		;;
esac	
