#!/bin/bash
case $1 in
  start)
    echo $$ > /var/run/tivo-transcode-worker.pid;
    exec hhvm /opt/tivo/console transcode-worker 2>&1 1>>/var/log/tivamypre-transcode.log
    ;;
  stop)
    kill `cat /var/run/tivo-transcode-worker.pid`
    ;;
  *)
    echo "usage: wrap-transcode {start|stop}"
    ;;
esac
exit 0