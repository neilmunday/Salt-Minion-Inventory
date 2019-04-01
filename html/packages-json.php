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

$serverId = filter_input(INPUT_GET, 'server_id', FILTER_VALIDATE_INT);
if (!$serverId) {
	die("Server ID not valid");
}

$result = doQuery($mysqli, "SELECT `package_name`, `package_version` FROM `package`, `minion_package` WHERE `server_id` = $serverId AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`;");

while ($row = $result->fetch_assoc()) {
	$data['data'][] = $row;
}
// return as JSON
echo(json_encode($data));
$mysqli->close();
?>
