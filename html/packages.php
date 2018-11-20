<!doctype html>
<!--
  This file is part of the Salt Minion Inventory.

	Salt Minion Inventory provides a web based interface to your
  SaltStack minions to view their state.

  Copyright (C) 2018 Neil Munday (neil@mundayweb.com)

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
!-->
<?php
	require_once("common.php");

	$serverId = filter_input(INPUT_GET, 'server_id', FILTER_VALIDATE_INT);
	if (!$serverId) {
		die("Server ID not valid");
	}

	$mysqli = dbConnect();
	// get server name
	$result = doQuery($mysqli, "SELECT `fqdn` FROM `minion` WHERE `server_id` = $serverId;");
	if ($result->num_rows == 0) {
		die("Server not found");
	}
	$row = $result->fetch_assoc();
	$serverName = $row['fqdn'];
?>

<html lang="en">
	<head>
		<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>Packages for <?php echo($serverName); ?></title>
		<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="css/style.css">
		<link rel="stylesheet" type="text/css" href="datatables/datatables.min.css"/>
	</head>
	<body>

		<?php
			$result = doQuery($mysqli, "SELECT `package_name`, `package_version` FROM `package`, `minion_package` WHERE `server_id` = $serverId AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`;");
		?>

		<div class="container-fluid">
			<div class="row">
				<div class="col-md-11">
					<h1>Packages: <?php echo($serverName); ?></h1>
				</div>
				<div class="col-md-1">
					<button id="backBtn" type="button" class="btn btn-primary">Back</button>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<table id="packageTable" class="table table-striped table-sm" width="98%">
						<thead>
						<tr>
							<th>Package</th>
							<th>Version</th>
						</tr>
						</thead>
						<tbody>
							<?php
								while ($row = $result->fetch_assoc()) {
									printf("<tr>
										<td>%s</td>
										<td>%s</td>
									</tr>\n", $row["package_name"], $row["package_version"]);
								}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<script src="js/jquery-3.3.1.min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>
		<script src="datatables/datatables.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#packageTable').DataTable({
					"responsive": true
				});

				$('#backBtn').on('click', function(){
					document.location = "index.html";
				});
			});
		</script>
	</body>
</html>
