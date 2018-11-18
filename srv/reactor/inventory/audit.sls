#
#    This file is part of the Salt Minion Inventory.
#
#    Salt Minion Inventory provides a web based interface to your
#    SaltStack minions to view their state.
#
#    Copyright (C) 2018 Neil Munday (neil@mundayweb.com)
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

# React to inventory.audit events and pass data onto
# the inventory.audit runner on the Salt master to
# update the database.
# You must add the following to /etc/salt/master
# to enable the reactor:
#
# reactor:
#  - 'inventory/audit':
#    - /srv/reactor/inventory/audit.sls
#
# Restart the Salt master for the changes to take effect.
inventory_audit:
  runner.inventory.audit:
    - ts: {{ data['_stamp'] }}
    - properties: {{ data['data']['properties'] }}
    - propertiesChanged: {{ data['data']['propertiesChanged'] }}
