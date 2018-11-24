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
?>

	<h1>Salt Minion Inventory</h1>

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12">
				<table id="minionTable" class="table table-striped table-sm nowrap" width="98%">
					<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>OS</th>
						<th>Release</th>
						<th>Kernel</th>
						<th>Packages</th>
						<th>Salt Version</th>
						<th>Last Seen</th>
						<th>Last Audit</th>
					</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<script type="text/javascript">

		var minionDataTable = null;

		function reloadJson() {
			minionDataTable.ajax.reload();
		}

		$(document).ready(function() {
			minionDataTable = $('#minionTable').DataTable({
				"ajax": "minions-json.php",
				"columns": [
					{ data: "server_id", visible: false },
					{ data: "fqdn" },
					{ data: "os" },
					{ data: "osrelease" },
					{ data: "kernelrelease" },
					{
						data: "package_total",
						render: function(data, type, row, meta) {
							if (data == 0) {
								return 0;
							}
							return "<a href='packages.php?server_id=" + row["server_id"] + "'>" + data + "</a>";
						}
					},
					{ data: "saltversion"},
					{ data: "last_seen", type: "date" },
					{ data: "last_audit", type: "date" }
				],
				"info": true,
				"ordering": true,
				"order": [[1, 'asc']],
				"paging": false,
				"responsive": true,
				"createdRow": function(row, data, dataIndex) {
					// highlight rows for minions that have not been
					// heard from for over two minutes
					var d = new Date(data["last_seen"]);
					if (new Date().getTime() - d.getTime() > 120000) {
						$(row).addClass("table-danger");
					}
				}
			});

			setInterval(reloadJson, 60000); // reload feed every 60s
		});
	</script>
<?php pageEnd(); ?>