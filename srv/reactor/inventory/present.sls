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

# React to salt.prescence.present events.
# You must enable presecence_events in /etc/salt/master, i.e.
#
#     presence_events: True
#
# Also add to /etc/salt/master:
#
# reactor:
#  - 'salt/presence/present':
#    - /srv/reactor/inventory/present.sls
#
# Restart the daemon for the changes to take effect.
inventory_present:
  runner.inventory.present:
    - ts: {{ data['_stamp'] }}
    - minions: {{ data['present']|tojson }}
