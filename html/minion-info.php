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
	pageStart();

	$serverId = filter_input(INPUT_GET, 'server_id', FILTER_VALIDATE_INT);
	if (!$serverId) {
		die("Server ID not valid");
	}

	$mysqli = dbConnect();
	// get server name
	$result = doQuery($mysqli, "SELECT * FROM `minion` WHERE `server_id` = $serverId;");
	if ($result->num_rows == 0) {
		die("Server not found");
	}
	$row = $result->fetch_assoc();
	$result->close();
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-md-11">
			<h1><?php echo($row['fqdn']); ?></h1>
		</div>
		<div class="col-md-1">
			<button id="backBtn" type="button" class="btn btn-primary">Back</button>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4">
			<div class="card">
				<div class="card-header">Hardware</div>
				<div class="card-body">
					<table class="table table-responsive table-sm table-borderless">
						<tr>
							<td>CPUs:</td>
							<td><?php echo($row["num_cpus"]); ?></td>
						</tr>
						<tr>
							<td nowrap>CPU Model:</td>
							<td><?php echo($row["cpu_model"]); ?></td>
						</tr>
						<tr>
							<td>GPUs:</td>
							<td><?php echo($row["num_gpus"]); ?></td>
						</tr>
						<tr>
							<td>Memory:</td>
							<td><?php echo($row["mem_total"]); ?> MB</td>
						</tr>
						<tr>
							<td>BIOS:</td>
							<td><?php echo($row["biosversion"]); ?> (<?php echo($row["biosreleasedate"]); ?>)</td>
						</tr>
						<tr>
							<td nowrap>Last Seen:</td>
							<td><?php echo($row["last_seen"]); ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card">
				<div class="card-header">Software</div>
				<div class="card-body">
					<table class="table table-responsive table-sm table-borderless">
						<tr>
							<td>OS:</td>
							<td><?php echo($row["os"] . ' ' . $row["osrelease"]); ?></td>
						</tr>
						<tr>
							<td>Kernel:</td>
							<td><?php echo($row["kernelrelease"]); ?></td>
						</tr>
						<tr>
							<td>Salt Version:</td>
							<td><?php echo($row["saltversion"]); ?></td>
						</tr>
						<tr>
							<td>Selinux:</td>
							<td><?php echo($row["selinux_enforced"]); ?></td>
						</tr>
						<tr>
							<td>Packages:</td>
							<td><a href="packages.php?server_id=<?php echo($serverId); ?>"><?php echo($row["package_total"]); ?></a></td>
						</tr>
						<tr>
							<td>Last Audit:</td>
							<td><?php echo($row["last_audit"]); ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="card">
				<div class="card-header">Network</div>
				<div class="card-body">
					<table class="table table-responsive table-sm table-borderless">
						<?php
							$result = doQuery($mysqli, "SELECT `interface`.`interface_id`, `interface_name`, `mac` FROM `interface`, `minion_interface` WHERE `server_id` = $serverId AND `interface`.`interface_id` = `minion_interface`.`interface_id` ORDER BY `interface_name`;");
							while ($row = $result->fetch_assoc()) {
								echo("<tr>\n");
								echo("<td>" . $row["interface_name"] . "</td>\n");
								echo("<td>" . $row["mac"] . "</td>\n");
								echo("<td>");
								$subResult = doQuery($mysqli, "SELECT `ip4` FROM `minion_ip4` WHERE `server_id` = $serverId AND `interface_id` = " . $row["interface_id"] . " ORDER BY `ip4`;");
								while ($subRow = $subResult->fetch_assoc()) {
									echo($subRow["ip4"] . "<br/>\n");
								}
								echo("</td>");
								echo("</tr>\n");
							}
						?>
					</table>
			</div>
		</div>
	</div>
</div>

<?php pageEnd(); ?>
