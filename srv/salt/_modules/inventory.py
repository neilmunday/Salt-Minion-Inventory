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

import hashlib
import logging
import os

log = logging.getLogger(__name__)

# grains to audit
__AUDIT_GRAINS = [
	'id',
	'biosreleasedate',
	'biosversion',
	'cpu_model',
	'fqdn',
	'host',
	'hwaddr_interfaces',
	'ip4_interfaces',
	'kernel',
	'kernelrelease',
	'mem_total',
	'num_cpus',
	'num_gpus',
	'os',
	'osrelease',
	'saltversion',
	'server_id'
]

def audit(force=False):
	"""
	Perform an audit of this minion and return data via a salt
	event for the master.
	"""
	log.debug("inventory.audit: performing audit...")

	grains = __salt__['grains.items']()

	properties = {}
	for p in __AUDIT_GRAINS:
		properties[p] = grains[p]

	if 'selinux_enabled' in properties and 'selinux_enforced' in properties:
		properties['selinux_enabled'] = grains['selinux']['enabled']
		properties['selinux_enforced'] = grains['selinux']['enforced'].encode()
	else:
		properties['selinux_enabled'] = False
		properties['selinux_enforced'] = 'Disabled'

	properties['pkgs'] = __salt__['pkg.list_pkgs'](versions_as_list=True)

	checksum = hashlib.md5(str(properties).encode()).hexdigest()

	cacheFile = "/var/tmp/salt_inventory_audit.cache"
	if not force and os.path.exists(cacheFile):
			contents = None
			with open(cacheFile, 'r') as f:
				contents = f.read()
			if contents == checksum:
				# record that the audit check ran
				__salt__['event.send']('inventory/audit', {
					'properties': {"server_id": properties["server_id"]},
					'propertiesChanged': False
				})
				log.debug("inventory.audit: %s properties have not changed" % properties["id"])
				return "%s properties have not changed" % properties["id"]

	__salt__['event.send']('inventory/audit', {
		'properties': properties,
		'propertiesChanged': True
	})
	with open(cacheFile, 'w') as f:
		f.write(checksum)
	log.debug("inventory.audit: success")
	return "Success"
