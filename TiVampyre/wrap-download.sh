#!/bin/bash
case $1 in
  start)
    echo $$ > /var/run/tivo-download-worker.pid;
    exec hhvm /opt/tivo/console download-worker 2>&1 1>>/var/log/tivamypre-download.log
    ;;
  stop)
    kill `cat /var/run/tivo-download-worker.pid`
    ;;
  *)
    echo "usage: wrap-download {start|stop}"
    ;;
esac
exit 0