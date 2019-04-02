<!doctype html>
<!--
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
!-->

<?php
	require_once("common.php");
	pageStart();
?>

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12">
				<h1>Salt Minion Inventory</h1>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div id="alertBox"></div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<table id="minionTable" class="table table-striped table-sm nowrap" width="98%">
					<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>Name</th>
						<th>OS</th>
						<th>Release</th>
						<th>Kernel</th>
						<th>Packages</th>
						<th>Users</th>
						<th>Salt Version</th>
						<th>Selinux</th>
						<th>Last Seen</th>
						<th>Last Audit</th>
					</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="row" style="margin-top: 20px;">
			<div class="col-md-12">
				With selected: <button id="diffBtn" type="button" class="btn btn-primary" disabled>Diff Packages</button>
			</div>
		</div>
	</div>

	<script type="text/javascript">

		var minionDataTable = null;

		function reloadJson() {
			minionDataTable.ajax.reload();
		}

		function showAlert(msg) {
			$("#alertBox").empty();
			$("#alertBox").append("<div class=\"alert alert-danger alert-dismissible fade show\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>" + msg + "</div>");
			$("#alertBox").attr("visibility", "visible");
		}

		$(document).ready(function() {
			minionDataTable = $('#minionTable').DataTable({
				"ajax": "minions-json.php",
				"columns": [
					{
							orderable: false,
	            			className: 'select-checkbox',
							data: function() {
								return "";
							}
					},
					{ data: "server_id", visible: false },
					{
						data: "fqdn",
						render: function(data, type, row, meta) {
							return "<a href=\"minion-info.php?server_id=" + row["server_id"] + "\">" + data + "</a>";
						}
					},
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
					{ data: "users" },
					{ data: "saltversion" },
					{ data: "selinux_enforced" },
					{ data: "last_seen", render: $.fn.dataTable.render.moment('X', 'YYYY-MM-DD HH:mm') },
					{ data: "last_audit", render: $.fn.dataTable.render.moment('X', 'YYYY-MM-DD HH:mm') }
				],
				"info": true,
				"ordering": true,
				"order": [[2, 'asc']],
				"paging": false,
				"responsive": true,
		        select: {
		            style:    'os',
		            selector: 'td:first-child'
		        },
				"createdRow": function(row, data, dataIndex) {
					// highlight rows for minions that have not been
					// heard from for over two minutes
					if (new Date().getTime() - (data["last_seen"] * 1000) > 120000){
						$(row).addClass("table-danger");
					}
				},
			});

			minionDataTable.on("select", function(e, dt, type, indexes) {
				if (type == "row") {
					$("#diffBtn").prop("disabled", minionDataTable.rows('.selected').data().length != 2);
				}
			});

			minionDataTable.on("deselect", function(e, dt, type, indexes) {
				if (type == "row") {
					$("#diffBtn").prop("disabled", minionDataTable.rows('.selected').data().length != 2);
				}
			});

			$("#diffBtn").click(function(){
				var rows = minionDataTable.rows('.selected').data();
				// in theory this error handling code should not be
				// triggered due to the select/deselect table event handlers
				if (rows.length == 0 || rows.length > 2) {
					showAlert("Please select two minions");
					return;
				}
				document.location = "package-diff.php?server1=" + rows[0]["server_id"] + "&server2=" + rows[1]["server_id"];
			});

			setInterval(reloadJson, 60000); // reload feed every 60s
		});
	</script>
<?php pageEnd(); ?>
