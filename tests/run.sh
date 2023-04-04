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

set -e

function usage {
  echo "Usage: $0 -s SALT_VERSION [-n MINION_TOTAL ]" 1>&2
  echo "  -s SALT_VERSION     version of Salt to test against"
  exit 0
}

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

while getopts ":s:n:" options; do
  case "${options}" in
    n)
      MINIONS=${OPTARG}
      ;;
    s)
      SALT_VER=${OPTARG}
      ;;
    :)
      echo "Error: -${OPTARG} requires a value"
      usage
      ;;
    *)
      usage
      ;;
  esac
done

if [ -z $SALT_VER ]; then
  usage
fi

cd $DIR/..

# build salt-master
docker build -t salt-master:latest -t salt-master:$SALT_VER --build-arg SALT_VERSION=${SALT_VER} -f tests/Dockerfile.master .
# build salt-minion
docker build -t salt-minion:latest -t salt-minion:$SALT_VER --build-arg SALT_VERSION=${SALT_VER} -f tests/Dockerfile.minion .
# run containers
if [ -z $MINIONS ]; then
  docker compose -f tests/docker-compose.yaml up
else
  docker compose -f tests/docker-compose.yaml up --scale salt-minion=$MINIONS
fi
