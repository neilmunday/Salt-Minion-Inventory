<?php
/*
  This file is part of the Salt Minion Inventory.

  Salt Minion Inventory provides a web based interface to your
  SaltStack minions to view their state.

  Copyright (C) 2019-2023 Neil Munday (neil@mundayweb.com)

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

$root = dirname(__FILE__) . "/..";
require_once($root . "/common/common.php");

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
	die("Server ID not valid");
}

$data = array();
$sth  = dbQuery("SELECT `package_name`, `package_version` FROM `package`, `minion_package` WHERE `server_id` = $id AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`;");

$data['data'] = array();
while($row = $sth->fetch()) {
        $data['data'][] = $row;
}

// return as JSON
echo(json_encode($data));
?>
