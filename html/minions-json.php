<?php
/*
  This file is part of the Salt Minion Inventory.

  Salt Minion Inventory provides a web based interface to your
  SaltStack minions to view their state.

  Copyright (C) 2019 Neil Munday (neil@mundayweb.com)

  Salt Minion Inventory is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  Salt Minion Inventory is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Salt Minion Inventory.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once("common.php");

$data = array();
$data['data'] = array();

$mysqli = dbConnect();
// get records for all the minions
$result = doQuery($mysqli, "SELECT `server_id`, `id`, `host`, `fqdn`, `biosversion`, `biosreleasedate`, `cpu_model`, `kernel`, `kernelrelease`, `os`, `osrelease`, `saltversion`, `last_seen`, `last_audit`, `last_seen`, `package_total`, `selinux_enforced`, (SELECT COUNT(*) FROM `minion_user` WHERE `minion_user`.`server_id` = `minion`.`server_id`) AS `users` FROM `minion`;");
while ($row = $result->fetch_assoc()) {
	$data['data'][] = $row;
}
// return as JSON
echo(json_encode($data));
$mysqli->close();
?>
