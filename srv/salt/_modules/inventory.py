#
#    This file is part of the Salt Minion Inventory.
#
#    Salt Minion Inventory provides a web based interface to your
#    SaltStack minions to view their state.
#
#    Copyright (C) 2019 Neil Munday (neil@mundayweb.com)
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
import re
import tempfile

log = logging.getLogger(__name__)

# grains to audit
__AUDIT_GRAINS = [
	'id',
	'biosreleasedate',
	'biosversion',
	'cpu_model',
	'fqdn',
	'gpus',
	'host',
	'hwaddr_interfaces',
	'ip4_interfaces',
	'kernel',
	'kernelrelease',
	'manufacturer',
	'mem_total',
	'num_cpus',
	'num_gpus',
	'os',
	'osrelease',
	'productname',
	'saltversion',
	'serialnumber',
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
		if p in grains:
			properties[p] = grains[p]
		else:
			properties[p] = 'Unknown'

	properties['disks'] = []
	properties['users'] = []

	if properties['kernel'] == 'Windows':
		properties['boot_time'] = __salt__['status.uptime']()
	else:
		for u in __salt__['status.w']():
			if u['user'] not in properties['users']:
				properties['users'].append(u['user'])

		properties['boot_time'] = __salt__['status.uptime']()['since_t']

	if properties['kernel'] == 'Linux':
		lsblkRe = re.compile('([A-Z]+)="(.*?)"')
		for line in __salt__['cmd.run']('lsblk -d -o name,serial,vendor,size,type -P -n').split("\n"):
			matches = lsblkRe.findall(line)
			if len(matches) > 0:
				disk = {}
				for field, value in matches:
					disk[field.lower()] = value.strip()
				if len(disk) == 5 and disk['type'] == 'disk': # name, serial, vendor, size, type
					# convert size to MB
					units = disk['size'][-1]
					size = disk['size'][0:-1]
					if units == 'T':
						disk['size'] = float(size) * 1048576
					elif units == 'G':
						disk['size'] = float(size) * 1024
					elif units == 'M':
						disk['size'] = float(size)
					elif units == 'K':
						disk['size'] = float(size) / 1024.0
					properties['disks'].append(disk)

	if 'selinux' in grains and 'enabled' in grains['selinux'] and 'enforced' in grains['selinux']:
		properties['selinux_enabled'] = grains['selinux']['enabled']
		properties['selinux_enforced'] = grains['selinux']['enforced']
	else:
		properties['selinux_enabled'] = False
		properties['selinux_enforced'] = 'Disabled'

	properties['pkgs'] = __salt__['pkg.list_pkgs'](versions_as_list=True)

	checksum = hashlib.md5(str(properties).encode()).hexdigest()

	cacheFile = os.path.join(tempfile.gettempdir(), 'salt_inventory_audit.cache')
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
