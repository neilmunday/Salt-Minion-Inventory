#!/bin/bash

#
#    This file is part of the Salt Minion Inventory.
#
#    Salt Minion Inventory provides a web based interface to your
#    SaltStack minions to view their state.
#
#    Copyright (C) 2019-2023 Neil Munday (neil@mundayweb.com)
#
#    Salt Minion Inventory is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    Salt Minion Inventory is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with Salt Minion Inventory.  If not, see <http://www.gnu.org/licenses/>.
#

function die {
  echo $1
  if [ ! -z $2 ]; then
    cat $2
  fi
  exit 1
}

mkdir -p /run/php-fpm

/usr/bin/supervisord --configuration /etc/supervisord.conf

for service in httpd mysqld php-fpm salt-master; do
  echo -n "checking service: $service -> "
  if supervisorctl status $service | grep -q STOPPED; then
    echo "starting"
    supervisorctl start $service
  else
    echo "already running"
  fi
done

for i in `seq 1 60`; do
  if [ -e /var/lib/mysql/mysql.sock ]; then
    echo "mysqld started"
    break
  fi
  sleep 1
done

if ! mysql -e "show databases;" > /dev/null 2>&1; then
  echo "failed to query mysql - did it start?"
  exit 1
fi

# create Slurm database
mysql < /root/database.sql || die "failed to set-up database"
echo "tables created:"
mysql -e "show tables" salt_minion || die "failed to show tables"
echo ""

exec $@
