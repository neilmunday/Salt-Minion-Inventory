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

	$server1Id = filter_input(INPUT_GET, 'server1', FILTER_VALIDATE_INT);
	if (!$server1Id) {
		die("Server 1 ID not valid");
	}

	$server2Id = filter_input(INPUT_GET, 'server2', FILTER_VALIDATE_INT);
	if (!$server2Id) {
		die("Server 2 ID not valid");
	}

	$mysqli = dbConnect();

	$result = doQuery($mysqli, "SELECT `server_id`, `fqdn`, `package_total` FROM `minion` WHERE `server_id` = $server1Id OR `server_id` = $server2Id;");
	if ($result->num_rows == 0) {
		die("Could not find $server1Id or $server2Id");
	}
	$servers = [];
	while ($row = $result->fetch_assoc()) {
		$servers[$row["server_id"]] = array("name" => $row["fqdn"], "package_total" => $row["package_total"]);
	}
	$result->close();

	$result = doQuery($mysqli, "SELECT `server_id`, `package_name`, `package_version` FROM `package`, `minion_package` WHERE (`server_id` = $server1Id OR `server_id` = $server2Id) AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`, `package_version`;");

	$packages = [];
	$packages["all"] = [];
	$packages[$server1Id] = [];
	$packages[$server2Id] = [];

	while ($row = $result->fetch_assoc()) {
		if (!in_array($row["package_name"], $packages["all"])) {
			$packages["all"][] = $row["package_name"];
		}
		if (array_key_exists($row["package_name"], $packages[$row["server_id"]])) {
			$packages[$row["server_id"]][$row["package_name"]][] = $row["package_version"];
		}
		else {
			$packages[$row["server_id"]][$row["package_name"]] = [$row["package_version"]];
		}
	}
	$result->close();
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-md-11">
			<h1>Package Diff</h1>
		</div>
		<div class="col-md-1">
			<button id="backBtn" type="button" class="btn btn-primary">Back</button>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<p>The difference in packages between <a href="minion-info.php?server_id=<?php echo($server1Id); ?>"><?php echo($servers[$server1Id]["name"]); ?></a> and <a href="minion-info.php?server_id=<?php echo($server2Id); ?>"><?php echo($servers[$server2Id]["name"]); ?></a> are show below:</p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-sm">
				<thead>
					<tr>
						<th><?php echo($servers[$server1Id]["name"] . " (" . $servers[$server1Id]["package_total"] . ")"); ?></th>
						<th><?php echo($servers[$server2Id]["name"] . " (" . $servers[$server2Id]["package_total"] . ")"); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($packages['all'] as $package) {
							$packageOnServer1 = array_key_exists($package, $packages[$server1Id]);
							$packageOnServer2 = array_key_exists($package, $packages[$server2Id]);
							if ($packageOnServer1 && $packageOnServer2) {
								$merged = array_unique(array_merge($packages[$server1Id][$package], $packages[$server2Id][$package]));
								foreach ($merged as $v) {
									$onServer1 = in_array($v, $packages[$server1Id][$package]);
									$onServer2 = in_array($v, $packages[$server2Id][$package]);
									if ($onServer1 && $onServer2) {
										echo("<tr>\n");
										echo("<td>$package-$v</td>\n");
										echo("<td>$package-$v</td>\n");
										echo("</tr>\n");
									}
									else if ($onServer1) {
										echo("<tr class=\"table-success\">\n");
										echo("<td>$package-$v</td>\n");
										echo("<td>&nbsp;</td>\n");
										echo("</tr>\n");
									}
									else {
										echo("<tr class=\"table-danger\">\n");
										echo("<td>&nbsp;</td>\n");
										echo("<td>$package-$v</td>\n");
										echo("</tr>\n");
									}
								}
							}
							else if ($packageOnServer1) {
								foreach ($packages[$server1Id][$package] as $v) {
									echo("<tr class=\"table-success\">\n");
									echo("<td>$package-$v</td>\n");
									echo("<td>&nbsp;</td>\n");
									echo("<tr>\n");
								}
							}
							else {
								foreach ($packages[$server2Id][$package] as $v) {
									echo("<tr class=\"table-danger\">\n");
									echo("<td>&nbsp;</td>\n");
									echo("<td>$package-$v</td>\n");
									echo("<tr>\n");
								}
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php pageEnd(); ?>
