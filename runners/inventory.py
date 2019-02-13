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

import ConfigParser
import MySQLdb
import MySQLdb.cursors
import logging
import os
import subprocess

log = logging.getLogger(__name__)

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
	configParser = ConfigParser.ConfigParser()
	configParser.read(CONFIG_FILE)
	dbUser = configParser.get("database", "user")
	dbPassword = configParser.get("database", "password")
	dbHost = configParser.get("database", "host")
	dbName = configParser.get("database", "name")

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
	db = __connect()
	cursor = db.cursor()

	serverId = __getRecordId(cursor, "minion", "server_id", "server_id", properties["server_id"])
	if serverId:
		serverId = int(serverId)
		if not propertiesChanged:
			# no changes to host, so just update last_audit field
			log.debug("inventory.audit: no changes needed for %d" % serverId)
			__doQuery(cursor, "UPDATE `minion` SET `last_audit` = \"%s\" WHERE `server_id` = %d;" % (ts, serverId))
			db.commit()
			return True
		# update minion info
		log.debug("inventory.audit: updating host \"%s\"" % properties["host"])
		query = """
			UPDATE `minion`
			SET
				`os` = \"%s\",
				`osrelease` = \"%s\",
				`last_audit` = \"%s\",
				`id` = \"%s\",
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
				ts,
				properties["id"],
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
				`last_audit`,
				`last_seen`,
				`id`,
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
				ts,
				ts,
				properties["id"],
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
	# process GPUs
	__doQuery(cursor, "UPDATE `minion_gpu` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	#for model, vendor in properties["gpus"].iteritems():
	for gpu in properties["gpus"]:
		__doQuery(cursor, "SELECT `gpu_id` FROM `gpu` WHERE `gpu_model` = \"%s\" AND `gpu_vendor` = \"%s\";" % (gpu["model"], gpu["vendor"]))
		if cursor.rowcount == 0:
			# add new GPU
			__doQuery(cursor, "INSERT INTO `gpu` (`gpu_model`, `gpu_vendor`) VALUES (\"%s\", \"%s\");" % (gpu["model"], gpu["vendor"]))
			gpuId = cursor.lastrowid
			db.commit()
		else:
			gpuId = cursor.fetchone()['gpu_id']
		__doQuery(cursor, "INSERT INTO `minion_gpu` (`server_id`, `gpu_id`, `present`) VALUES (%d, %d, 1) ON DUPLICATE KEY UPDATE `present` = 1;" % (serverId, gpuId))
		db.commit()
	# delete removed GPUs
	__doQuery(cursor, "DELETE FROM `minion_gpu` WHERE `present` = 0;")
	db.commit()
	# process network inerfaces
	__doQuery(cursor, "UPDATE `minion_interface` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	__doQuery(cursor, "UPDATE `minion_ip4` SET `present` = 0 WHERE `server_id` = %d;" % serverId)
	db.commit()
	for interface, addr in properties["hwaddr_interfaces"].iteritems():
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
	for package, versions in properties["pkgs"].iteritems():
		pkgId = __getRecordId(cursor, "package", "package_id", "package_name", package)
		if not pkgId:
			__doQuery(cursor, "INSERT INTO `package` (`package_name`) VALUES (\"%s\");" % package)
			pkgId = cursor.lastrowid
			db.commit()
		for v in versions:
			version = None
			if isinstance(v, dict) and 'version' in v:
				version = v['version']
			elif isinstance(v, basestring):
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
