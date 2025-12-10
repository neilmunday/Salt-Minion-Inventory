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

import logging
log = logging.getLogger(__name__)

# try to use the ConfigParser module (Python v2)
try:
	import ConfigParser
	log.debug("inventory: imported ConfigParser module")
except ImportError as e:
	# try to use the ConfigParser module (Python v3)
	import configparser as ConfigParser
	log.debug("inventory: imported configparser module")

import datetime
import MySQLdb
import MySQLdb.cursors
import os
import pytz
import subprocess
import sys

IS_PYTHON_3 = sys.version_info.major == 3

if IS_PYTHON_3:
	log.debug("inventory: Python 3")
else:
	log.debug("inventory: Python 2")

def __connect():
	"""
	Helper function to connect to the database.
	Returns a MySQLdb connection object.
	Database settings must be put into a file called "inventory.ini"
	in the same directory as this script with the following contents:

	[database]
	user:		salt_minion
	password:	salt_minion
	host:		localhost
	name:		salt_minion
	"""
	CONFIG_FILE = os.path.join(os.path.dirname(os.path.realpath(__file__)), "inventory.ini")
	log.debug("inventory.__connect: using config file: %s" % CONFIG_FILE)
	if not os.path.exists(CONFIG_FILE):
		raise Exception("%s does not exist or is not readable" % CONFIG_FILE)
	conf = ConfigParser.ConfigParser()
	conf.read(CONFIG_FILE)
	dbUser = conf.get("database", "user")
	dbPassword = conf.get("database", "password")
	dbHost = conf.get("database", "host")
	dbName = conf.get("database", "name")

	log.debug("inventory.__connect: connection to %s on %s as %s" % (dbName, dbHost, dbUser))
	try:
		db = MySQLdb.connect(
			user=dbUser,
			passwd=dbPassword,
			db=dbName,
			host=dbHost,
			cursorclass=MySQLdb.cursors.DictCursor
		)
		log.debug("inventory.__connect: connected!")
		return db
	except Exception as e:
		log.error("inventory.__connect: failed to connect: %s" % e)

def __doQuery(cursor, query):
	"""
	Helper function to execute a query on the given cursor object.
	"""
	try:
		cursor.execute(query)
	except Exception as e:
		raise Exception("inventory.__query: %s\nquery was: %s" % (e, query))

def __getRecordId(cursor, table, keyField, field, value):
	"""
	Returns the primary key for the given record.
	"""
	__doQuery(cursor, "SELECT `%s` FROM `%s` WHERE `%s` = \"%s\" LIMIT 0,1;" % (keyField, table, field, value))
	if cursor.rowcount > 0:
		return cursor.fetchone()[keyField]
	return None

def __getTimeStamp(tsStr):
	"""
	Helper function to convert a Salt time stamp string
	into a Unix timestamp.
	Credit: David Murray @dajamu
	"""
	return int(pytz.timezone("UTC").localize(datetime.datetime.strptime(tsStr, "%Y-%m-%dT%H:%M:%S.%f")).timestamp())

def __getVendorId(db, cursor, vendor):
	"""
	Helper function to get a vendor ID.
	"""
	__doQuery(cursor, "SELECT `vendor_id` FROM `vendor` WHERE `vendor_name` = '%s';" % vendor)
	if cursor.rowcount == 0:
		# add new vendor
		__doQuery(cursor, "INSERT INTO `vendor` (`vendor_name`) VALUES ('%s');" % vendor)
		vendorId = cursor.lastrowid
		db.commit()
	else:
		vendorId = cursor.fetchone()['vendor_id']
	return vendorId

def __items(d):
	if IS_PYTHON_3:
		return d.items()
	return d.iteritems()

def audit(ts, properties, propertiesChanged):
	"""
	This function is called by a minion's inventory.audit function
	via an event which in turn causes a reactor on the master to
	call this function.
	If the minion's properties have changed then the properties
	dictionary will be populated with the minion's new state.
	Otherwise only the server_id field will be populated.
	The minon's state is saved into the database.
	"""
	ts = __getTimeStamp(ts)
	db = __connect()
	cursor = db.cursor()

	# get server id
	serverId = __getRecordId(cursor, "minion", "server_id", "server_id", properties["server_id"])
	if not propertiesChanged:
		# no changes to host, so just update last_audit field
		log.debug("inventory.audit: no changes needed for %d" % serverId)
		__doQuery(cursor, "UPDATE `minion` SET `last_audit` = \"%s\" WHERE `server_id` = %d;" % (ts, serverId))
		db.commit()
		return True

	# new server vendor?
	vendorId = __getVendorId(db, cursor, properties["manufacturer"])
	__doQuery(cursor, "SELECT `server_model_id` FROM `server_model` WHERE `vendor_id` = %d AND `server_model` = \"%s\" LIMIT 0,1;" % (vendorId, properties["productname"]))
	modelId = 0
	if cursor.rowcount == 0:
		__doQuery(cursor, "INSERT INTO `server_model` (`server_model`, `vendor_id`) VALUES (\"%s\", %d);" % (properties["productname"], vendorId))
		db.commit()
		modelId = cursor.lastrowid
	else:
		modelId = cursor.fetchone()["server_model_id"]

	if serverId:
		# update minion info
		log.debug("inventory.audit: updating host \"%s\"" % properties["host"])
		query = """
			UPDATE `minion`
			SET
				`os` = \"%s\",
				`osrelease` = \"%s\",
				`boot_time` = %d,
				`last_audit` = %d,
				`id` = \"%s\",
				`server_model_id` = %d,
				`server_serial` = \"%s\",
				`biosreleasedate` = \"%s\",
				`biosversion` = \"%s\",
				`cpu_model` = \"%s\",
				`fqdn` = \"%s\",
				`host` = \"%s\",
				`kernel` = \"%s\",
				`kernelrelease` = \"%s\",
				`mem_total` = %d,
				`num_cpus` = %d,
				`num_gpus` = %d,
				`os` = \"%s\",
				`osrelease` = \"%s\",
				`saltversion` = \"%s\",
				`selinux_enabled` = %d,
				`selinux_enforced` = \"%s\"
			WHERE `server_id` = %d;
			""" % (
				properties["os"],
				properties["osrelease"],
				properties["boot_time"],
				ts,
				properties["id"],
				modelId,
				properties["serialnumber"],
				properties["biosreleasedate"],
				properties["biosversion"],
				properties["cpu_model"],
				properties["fqdn"],
				properties["host"],
				properties["kernel"],
				properties["kernelrelease"],
				properties["mem_total"],
				properties["num_cpus"],
				properties["num_gpus"],
				properties["os"],
				properties["osrelease"],
				properties["saltversion"],
				int(properties["selinux_enabled"]),
				properties["selinux_enforced"],
				int(properties["server_id"])
			)
	else:
		# new minion
		log.debug("inventory.audit: adding new host \"%s\"" % properties["host"])
		query = """
			INSERT into `minion` (
				`server_id`,
				`boot_time`,
				`last_audit`,
				`last_seen`,
				`id`,
				`server_model_id`,
				`server_serial`,
				`biosreleasedate`,
				`biosversion`,
				`cpu_model`,
				`fqdn`,
				`host`,
				`kernel`,
				`kernelrelease`,
				`mem_total`,
				`num_cpus`,
				`num_gpus`,
				`os`,
				`osrelease`,
				`saltversion`,
				`selinux_enabled`,
				`selinux_enforced`
			)
			VALUES (
				%d,
				%d,
				%d,
				%d,
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				%d,
				%d,
				%d,
				"%s",
				"%s",
				"%s",
				%d,
				"%s"
			);
			""" % (
				properties["server_id"],
				properties["boot_time"],
				ts,
				ts,
				properties["id"],
				modelId,
				properties["serialnumber"],
				properties["biosreleasedate"],
				properties["biosversion"],
				properties["cpu_model"],
				properties["fqdn"],
				properties["host"],
				properties["kernel"],
				properties["kernelrelease"],
				properties["mem_total"],
				properties["num_cpus"],
				properties["num_gpus"],
				properties["os"],
				properties["osrelease"],
				properties["saltversion"],
				int(properties["selinux_enabled"]),
				properties["selinux_enforced"]
			)
		serverId = int(properties["server_id"])
	try:
		__doQuery(cursor, query)
		db.commit()
	except Exception as e:
		log.error("inventory.audit: failed for %s" % properties["host"])
		log.error(e)
		return False
	# process users
	__doQuery(cursor, "UPDATE `minion_user` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	for user in properties['users']:
		userId = __getRecordId(cursor, "user", "user_id", "user_name", user)
		if not userId:
			__doQuery(cursor, "INSERT INTO `user` (`user_name`) VALUES (\"%s\");" % user)
			userId = cursor.lastrowid
			db.commit()
		__doQuery(cursor, "INSERT INTO `minion_user` (`server_id`, `user_id`, `present`) VALUES (%d, %d, 1) ON DUPLICATE KEY UPDATE `present` = 1;" % (serverId, userId))
		db.commit()
	# delete removed users
	__doQuery(cursor, "DELETE FROM `minion_user` WHERE `present` = 0;")
	db.commit()
	# process disks
	__doQuery(cursor, "UPDATE `minion_disk` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	for disk in properties['disks']:
		# new vendor?
		vendorId = __getVendorId(db, cursor, disk['vendor'])
		__doQuery(cursor, "INSERT INTO `minion_disk` (`server_id`, `disk_path`, `disk_serial`, `disk_size`, `vendor_id`, `present`) VALUES (%d, '%s', '%s', %d, %d, 1) ON DUPLICATE KEY UPDATE `present` = 1, `disk_serial` = '%s', `disk_size` = %d, `vendor_id` = %d;" % (serverId, disk['name'], disk['serial'], float(disk['size']), vendorId, disk['serial'], float(disk['size']), vendorId))
		db.commit()
	# delete removed disks
	__doQuery(cursor, "DELETE FROM `minion_disk` WHERE `present` = 0;")
	db.commit()
	# process GPUs
	__doQuery(cursor, "UPDATE `minion_gpu` SET `gpu_qty` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	for gpu in properties["gpus"]:
		# new vendor?
		vendorId = __getVendorId(db, cursor, gpu["vendor"])
		__doQuery(cursor, "SELECT `gpu_id` FROM `gpu` WHERE `gpu_model` = \"%s\" AND `vendor_id` = %d;" % (gpu["model"], vendorId))
		if cursor.rowcount == 0:
			# add new GPU
			__doQuery(cursor, "INSERT INTO `gpu` (`gpu_model`, `vendor_id`) VALUES (\"%s\", %d);" % (gpu["model"], vendorId))
			gpuId = cursor.lastrowid
			db.commit()
		else:
			gpuId = cursor.fetchone()['gpu_id']
		__doQuery(cursor, "INSERT INTO `minion_gpu` (`server_id`, `gpu_id`, `gpu_qty`) VALUES (%d, %d, 1) ON DUPLICATE KEY UPDATE `gpu_qty` = `gpu_qty` + 1;" % (serverId, gpuId))
		db.commit()
	# delete removed GPUs
	__doQuery(cursor, "DELETE FROM `minion_gpu` WHERE `gpu_qty` = 0;")
	db.commit()
	# process network inerfaces
	__doQuery(cursor, "UPDATE `minion_interface` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	__doQuery(cursor, "UPDATE `minion_ip4` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	for interface, addr in __items(properties["hwaddr_interfaces"]):
		if interface != "lo":
			interfaceId = __getRecordId(cursor, "interface", "interface_id", "interface_name", interface)
			if not interfaceId:
				__doQuery(cursor, "INSERT INTO `interface` (`interface_name`) VALUES (\"%s\");" % interface)
				interfaceId = cursor.lastrowid
				db.commit()
			__doQuery(cursor, "INSERT INTO `minion_interface` (`server_id`, `interface_id`, `mac`, `present`) VALUES (%d, %d, \"%s\", 1) ON DUPLICATE KEY UPDATE `present` = 1, `mac` = \"%s\";" % (serverId, interfaceId, addr, addr))
			db.commit()
			if interface in properties["ip4_interfaces"]:
				if len(properties["ip4_interfaces"][interface]) > 0:
					for ip in properties["ip4_interfaces"][interface]:
						__doQuery(cursor, "INSERT INTO `minion_ip4` (`server_id`, `interface_id`, `ip4`, `present`) VALUES (%d, %d, \"%s\", 1) ON DUPLICATE KEY UPDATE `present` = 1;" % (serverId, interfaceId, ip))
						db.commit()

	__doQuery(cursor, "DELETE FROM `minion_ip4` WHERE `server_id` = %d AND `present` = 0;" % serverId)
	db.commit()
	__doQuery(cursor, "DELETE FROM `minion_interface` WHERE `server_id` = %d AND `present` = 0;" % serverId)
	db.commit()

	# tidy-up package records if previous run failed
	__doQuery(cursor, "DELETE FROM `minion_package` WHERE `server_id` = %d AND `present` = 0;" % serverId)
	db.commit()
	# mark all packages for this minion as being removed
	__doQuery(cursor, "UPDATE `minion_package` SET `present` = 0 WHERE `server_id` = %d" % serverId)
	db.commit()
	# process install packages
	for package, versions in __items(properties["pkgs"]):
		pkgId = __getRecordId(cursor, "package", "package_id", "package_name", package)
		if not pkgId:
			__doQuery(cursor, "INSERT INTO `package` (`package_name`) VALUES (\"%s\");" % package)
			pkgId = cursor.lastrowid
			db.commit()
		for v in versions:
			version = None
			if isinstance(v, dict) and 'version' in v:
				version = v['version']
			elif (not IS_PYTHON_3 and isinstance(v, basestring)) or (IS_PYTHON_3 and isinstance(v, str)):
				version = v
			else:
				log.error("inventory.audit: could not process %s version %s" % (package, v))
				continue
			__doQuery(cursor,
				"""
					INSERT INTO `minion_package` (`server_id`, `package_id`, `package_version`, `present`)
					VALUES (%d, %d, \"%s\", 1)
					ON DUPLICATE KEY UPDATE `present` = 1;
				""" % (serverId, pkgId, version))
			db.commit()
	# purge any deleted packages
	__doQuery(cursor, "DELETE FROM `minion_package` WHERE `server_id` = %d AND `present` = 0;" % serverId)
	db.commit()
	# update package total
	__doQuery(cursor, "UPDATE `minion` SET `package_total` = (SELECT COUNT(*) FROM `minion_package` WHERE `server_id` = %d) WHERE `server_id` = %d;" % (serverId, serverId))
	db.commit()
	return True

def present(ts, minions):
	"""
	This function is called by a reactor that responds to
	salt.presence.present events.
	It updates the last_seen field for the minions that
	are present.
	If a minion does not exist in the database then
	the Inventory.audit function will be called to
	populate the database.
	"""
	ts = __getTimeStamp(ts)
	db = __connect()
	cursor = db.cursor()
	# process each minion
	for m in minions:
		try:
			serverId = __getRecordId(cursor, "minion", "server_id", "id", m)
			if serverId:
				serverId = int(serverId)
				__doQuery(cursor, "UPDATE `minion` SET `last_seen` = \"%s\" WHERE `server_id` = %d" % (ts, serverId))
				db.commit()
			else:
				log.info("inventory.present: minion %s has not been audited, invoking audit" % m)
				# New minion, call the inventory.audit function on the minion
				# to populate the database.
				# Note: Newer versions of Salt have the 'salt.execute' function.
				# For those that don't, call via a subprocess instead.
				if 'salt.execute' in __salt__:
					__salt__['salt.execute'](m, 'inventory.audit', args=('force=True'))
				else:
					rtn = subprocess.call("salt '%s' inventory.audit force=True" % m, shell=True)
					if rtn == 0:
						return True
					log.error("inventory.present: failed to invoke audit of %s" % m)
			log.debug("inventory.present: updated %s" % m)
		except Exception as e:
			log.error("inventory.present: failed to update %s due to: %s" % (m, e))

	return True
