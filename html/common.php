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

define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'salt_minion');
define('MYSQL_PASS', 'salt_minion');
define('MYSQL_DB', 'salt_minion');

function dbConnect() {
	$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

	if (mysqli_connect_errno()) {
		die("Unable to connect to MySQL server! " . mysqli_connect_error());
	}
	return $mysqli;
}

function doQuery($mysqli, $query) {
	if ($result = $mysqli->query($query)) {
		return $result;
	}
	die("Query: $query failed with error: " . $mysqli->error);
}

function formatMegaBytes($s) {
	return sprintf("%.2f GB", $s / 1024.0);
}

function pageEnd() {
	echo("
		</body>
	</html>
	");
}

function pageStart() {
	echo("
	<html lang=\"en\">
		<head>
			<meta charset=\"utf-8\">
	    	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">
			<title>Salt Minion Inventory</title>
			<link rel=\"stylesheet\" href=\"bootstrap/css/bootstrap.min.css\">
			<link rel=\"stylesheet\" href=\"css/style.css\">
			<link rel=\"stylesheet\" type=\"text/css\" href=\"datatables/datatables.min.css\"/>
			<script src=\"js/jquery-3.3.1.min.js\"></script>
			<script src=\"js/moment.min.js\"></script>
			<script src=\"bootstrap/js/bootstrap.min.js\"></script>
			<script src=\"datatables/datatables.min.js\"></script>
			<script src=\"datatables/datetime.js\"></script>
			<script src=\"js/common.js\"></script>
		</head>
		<body>
	");
}

?>
