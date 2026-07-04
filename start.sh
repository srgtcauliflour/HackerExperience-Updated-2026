#!/bin/bash
set -e

WORKDIR=/home/runner/workspace
MYSQL_BASEDIR=/nix/store/a4jsa8kjdn3wlccj2wkvhxqza38rpxzf-mariadb-server-10.11.13
MYSQL_DATADIR=$WORKDIR/.mysql/data
MYSQL_RUNDIR=$WORKDIR/.mysql/run
MYSQL_SOCK=$MYSQL_RUNDIR/mysqld.sock

mkdir -p "$MYSQL_RUNDIR" "$MYSQL_RUNDIR/nginx_client_temp" "$MYSQL_RUNDIR/nginx_proxy_temp" "$MYSQL_RUNDIR/nginx_fastcgi_temp" "$MYSQL_RUNDIR/nginx_uwsgi_temp" "$MYSQL_RUNDIR/nginx_scgi_temp"

echo "Starting MariaDB..."
"$MYSQL_BASEDIR/bin/mariadbd" --no-defaults \
  --datadir="$MYSQL_DATADIR" \
  --basedir="$MYSQL_BASEDIR" \
  --lc-messages-dir="$MYSQL_BASEDIR/share/mysql" \
  --socket="$MYSQL_SOCK" \
  --port=3306 \
  --bind-address=127.0.0.1 \
  --skip-networking=0 \
  --sql-mode="NO_ENGINE_SUBSTITUTION" \
  --pid-file="$MYSQL_RUNDIR/mysqld.pid" &
MYSQL_PID=$!

for i in $(seq 1 30); do
  if [ -S "$MYSQL_SOCK" ]; then
    echo "MariaDB is up."
    break
  fi
  sleep 1
done

echo "Starting PHP-FPM..."
php-fpm --nodaemonize --fpm-config "$WORKDIR/.local_config/php-fpm.conf" -c "$WORKDIR/.local_config/php.ini" &
PHPFPM_PID=$!

sleep 2

echo "Starting nginx..."
nginx -c "$WORKDIR/.local_config/nginx.conf" -g "daemon off;" &
NGINX_PID=$!

trap "kill $MYSQL_PID $PHPFPM_PID $NGINX_PID 2>/dev/null" EXIT

wait -n $MYSQL_PID $PHPFPM_PID $NGINX_PID
