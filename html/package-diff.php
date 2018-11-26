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
	// get server names
	$result = doQuery($mysqli, "SELECT `fqdn` FROM `minion` WHERE `server_id` = $server1Id;");
	if ($result->num_rows == 0) {
		die("Could not find $server1Id");
	}
	$row = $result->fetch_assoc();
	$server1Name = $row["fqdn"];
	$result->close();
	$result = doQuery($mysqli, "SELECT `fqdn` FROM `minion` WHERE `server_id` = $server2Id;");
	if ($result->num_rows == 0) {
		die("Could not find $server2Id");
	}
	$row = $result->fetch_assoc();
	$server2Name = $row["fqdn"];
	$result->close();

	$server1Pkgs = [];
	$server1PkgVersions = [];
	$server2Pkgs = [];
	$server2PkgVersions = [];
	$result = doQuery($mysqli, "SELECT `package_name`, `package_version` FROM `package`, `minion_package` WHERE `server_id` = $server1Id AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`, `package_version`;");
	while ($row = $result->fetch_assoc()) {
		$server1Pkgs[] = $row["package_name"];
		if (!array_key_exists($row["package_name"], $server1PkgVersions)) {
			$server1PkgVersions[$row["package_name"]] = array($row["package_version"]);
		}
		else {
			$server1PkgVersions[$row["package_name"]][] = $row["package_version"];
		}
	}
	$result->close();
	$result = doQuery($mysqli, "SELECT `package_name`, `package_version` FROM `package`, `minion_package` WHERE `server_id` = $server2Id AND `package`.`package_id` = `minion_package`.`package_id` ORDER BY `package_name`, `package_version`;");
	while ($row = $result->fetch_assoc()) {
		$server2Pkgs[] = $row["package_name"];
		if (!array_key_exists($row["package_name"], $server2PkgVersions)) {
			$server2PkgVersions[$row["package_name"]] = array($row["package_version"]);
		}
		else {
			$server2PkgVersions[$row["package_name"]][] = $row["package_version"];
		}
	}
	$result->close();

	$server1Pkgs = array_unique($server1Pkgs);
	$server2Pkgs = array_unique($server2Pkgs);
	$merged = array_merge($server1Pkgs, $server2Pkgs);
	$notOnServer1 = array_diff($server2Pkgs, $server1Pkgs);
	$notOnServer2 = array_diff($server1Pkgs, $server2Pkgs);
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
			<p>The difference in packages between <?php echo($server1Name); ?> and <?php echo($server2Name); ?> are show below:</p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<table class="table table-sm">
				<thead>
					<tr>
						<th><?php echo($server1Name); ?></th>
						<th><?php echo($server2Name); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($merged as $p) {
							if (!in_array($p, $notOnServer1) && !in_array($p, $notOnServer2)) {
								// are versions the same?
								$m = array_merge($server1PkgVersions[$p], $server2PkgVersions[$p]);
								$versionsNotOnServer1 = array_diff($server2PkgVersions[$p], $server1PkgVersions[$p]);
								$versionsNotOnServer2 = array_diff($server1PkgVersions[$p], $server2PkgVersions[$p]);
								foreach ($m as $v) {
									if (!in_array($v, $versionsNotOnServer1) && !in_array($v, $versionsNotOnServer2)) {
										echo("<tr>\n");
										echo("<td>$p-$v</td>\n");
										echo("<td>$p-$v</td>\n");
										echo("</tr>\n");
									}
									else if (in_array($v, $versionsNotOnServer1)) {
										echo("<tr class=\"table-danger\">\n");
										echo("<td>&nbsp;</td>\n");
										echo("<td>$p-$v</td>\n");
										echo("</tr>\n");
									}
									else if (in_array($v, $versionsNotOnServer2)) {
										echo("<tr class=\"table-danger\">\n");
										echo("<td>$p-$v</td>\n");
										echo("<td>&nbsp;</td>\n");
										echo("</tr>\n");
									}
								}
							}
							else if (in_array($p, $notOnServer1)) {
								foreach ($server2PkgVersions[$p] as $v) {
									echo("<tr class=\"table-danger\">\n");
									echo("<td>$p-$v</td>\n");
									echo("<td>&nbsp;</td>\n");
									echo("</tr>\n");
								}
							}
							else if (in_array($p, $notOnServer2)) {
								foreach ($server1PkgVersions[$p] as $v) {
									echo("<tr class=\"table-danger\">\n");
									echo("<td>$p-$v</td>\n");
									echo("<td>&nbsp;</td>\n");
									echo("</tr>\n");
								}
							}
						}
					?>
				</tbody>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#backBtn').on('click', function(){
			document.location = "index.php";
		});
	});
</script>

<?php
	pageEnd();
?>
