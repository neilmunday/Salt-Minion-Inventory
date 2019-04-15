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

$root = dirname(__FILE__);
require_once($root . "/common/common.php");

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 0), 'flags' => FILTER_NULL_ON_FAILURE));

if($id === false || $id === NULL) {
        summary();
} else {
        details($id);
}

function details($id) {
	$sth = dbQuery("SELECT * FROM `minion` WHERE `server_id` = $id;");

	if ($sth->rowcount() != 1) {
		summary();
		exit(0);
	}

	$row			= $sth->fetch();
	$details		= array("Summary", "Software", "Packages", "Disks", "Network", "GPUs", "Users");
	$jsonPackages	= mkPath("/json/packages.json.php?id=" . $id);
	$lastSeen		= date('Y-m-d H:i:s', $row["last_seen"]);
	$lastAudit		= date('Y-m-d H:i:s', $row["last_audit"]);

	printPageStart($row['fqdn']);

	echo <<<EOT
	<div class="card" style="height:500px;">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs" id="minionTabs" role="tablist">

EOT;
	
	$i = 0;
	foreach($details as $tab) {
		if($i++ > 0) {
			printf("\t\t\t\t<li class=\"nav-item\"><a aria-selected=\"false\" aria-controls=\"%s\" class=\"nav-link\" data-toggle=\"tab\" id=\"tab-%s\" href=\"#content-%s\" role=\"tab\">%s</a></li>\n", $tab, $tab, $tab, $tab);
		} else {
			printf("\t\t\t\t<li class=\"nav-item\"><a aria-selected=\"true\" aria-controls=\"%s\" class=\"nav-link active\" data-toggle=\"tab\" id=\"tab-%s\" href=\"#content-%s\" role=\"tab\">%s</a></li>\n", $tab, $tab, $tab, $tab);
		}
	}

	echo <<<EOT
			</ul>
		</div>
		<div class="card-block" style="overflow-y: auto;">
			<div class="tab-content" id="minionTabContent">
				<!-- SUMMARY TAB -->
				<div class="tab-pane show active p-3" id="content-Summary" role="tabpanel" aria-labelledby="tab-Summary">
					<table class="table table-responsive table-sm table-borderless">
						<tr><td>CPUs:</td><td>{$row["num_cpus"]}</td></tr>
						<tr><td nowrap>CPU Model:</td><td>{$row["cpu_model"]}</td></tr>
						<tr><td>GPUs:</td><td>{$row["num_gpus"]}</td></tr>
						<tr><td>Memory:</td><td>{$row["mem_total"]} MB</td></tr>
						<tr><td>BIOS:</td><td>{$row["biosversion"]} ({$row["biosreleasedate"]})</td></tr>
						<tr><td nowrap>Last Seen:</td><td>{$lastSeen}</td></tr>
					</table>
				</div>

				<!-- SOFTWARE TAB -->
				<div class="tab-pane p-3" id="content-Software" role="tabpanel" aria-labelledby="tab-Software">
					<table class="table table-responsive table-sm table-borderless">
						<tr><td>OS:</td><td>{$row["os"]} {$row["osrelease"]}</td></tr>
						<tr><td>Kernel:</td><td>{$row["kernelrelease"]}</td></tr>
						<tr><td>Salt Version:</td><td>{$row["saltversion"]}</td></tr>
						<tr><td>Selinux:</td><td>{$row["selinux_enforced"]}</td></tr>
						<tr><td>Packages:</td><td><a href="#" onclick="$('#tab-Packages').trigger('click');">{$row["package_total"]}</a></td></tr>
						<tr><td>Last Audit:</td><td>{$lastAudit}</td></tr>
					</table>
				</div>

				<!-- PACKAGES TAB -->
				<div class="tab-pane p-3" id="content-Packages" role="tabpanel" aria-labelledby="tab-Packages">
					<table id="packageTable" class="table table-striped table-sm" width="100%">
						<thead><tr><th>Package</th><th>Version</th></tr></thead>
						<tbody></tbody>
					</table>
				</div>

				<!-- DISKS TAB -->
				<div class="tab-pane p-3" id="content-Disks" role="tabpanel" aria-labelledby="tab-Disks">
					<table class="table table-responsive table-sm table-borderless">
						<tr><th>Path</th><th>Size</th><th>Vendor</th><th>Serial</th></tr>
EOT;

	$disk_sth = dbQuery("SELECT `vendor_name`, `disk_path`, `disk_serial`, `disk_size` FROM `minion_disk`, `vendor` WHERE `server_id` = $id AND `minion_disk`.`vendor_id` = `vendor`.`vendor_id`;");

	echo("\n");
	while ($disk_row = $disk_sth->fetch()) {
		echo("\t\t\t\t\t\t<tr>");
		echo("<td>" . $disk_row["disk_path"] . "</td>");
		//echo("<td>" . formatMegaBytes($row["disk_size"]) . "</td>\n");
		echo("<td>" . $disk_row["disk_size"] . "</td>");
		echo("<td>" . $disk_row["vendor_name"] . "</td>");
		echo("<td>" . $disk_row["disk_serial"] . "</td>");
		echo("</tr>\n");
	}

	echo <<<EOT
					</table>
				</div>

				<!-- NETWORK TAB -->
				<div class="tab-pane p-3" id="content-Network" role="tabpanel" aria-labelledby="tab-Network">
					<table class="table table-responsive table-sm table-borderless">
						<tr><th>Interface</th><th>MAC</th><th>IP</th></tr>
EOT;

	$nw_sth = dbQuery("SELECT `interface`.`interface_id`, `interface_name`, `mac` FROM `interface`, `minion_interface` WHERE `server_id` = $id AND `interface`.`interface_id` = `minion_interface`.`interface_id` ORDER BY `interface_name`;");

	echo("\n");
	while ($nw_row = $nw_sth->fetch()) {
		echo("\t\t\t\t\t\t<tr>");
		echo("<td>" . $nw_row["interface_name"] . "</td>");
		echo("<td>" . $nw_row["mac"] . "</td>");
		echo("<td>");
		$nw_sub = dbQuery("SELECT `ip4` FROM `minion_ip4` WHERE `server_id` = $id AND `interface_id` = " . $nw_row["interface_id"] . " ORDER BY `ip4`;");
		while ($nw_sub_row = $nw_sub->fetch()) {
			echo($nw_sub_row["ip4"] . "<br/>");
		}
		echo("</td>");
		echo("</tr>\n");
	}

	echo <<<EOT
					</table>
				</div>

				<!-- GPU TAB -->
				<div class="tab-pane p-3" id="content-GPUs" role="tabpanel" aria-labelledby="tab-GPUs">
					<table class="table table-responsive table-sm table-borderless">
						<tr><th>Model</th><th>Vendor</th></tr>
EOT;

$gpu_sth = dbQuery("SELECT `vendor_name`, `gpu_model` FROM `minion_gpu`, `gpu`, `vendor` WHERE `server_id` = $id AND `minion_gpu`.`gpu_id` = `gpu`.`gpu_id` AND `gpu`.`vendor_id` = `vendor`.`vendor_id`;");

echo("\n");
while ($gpu_row = $gpu_sth->fetch()) {
	echo("\t\t\t\t\t\t<tr>");
	echo("<td>" . $gpu_row["gpu_model"] . "</td>");
	echo("<td>" . $gpu_row["vendor_name"] . "</td>");
	echo("</tr>\n");
}

echo <<<EOT
					</table>		
				</div>
				
				<!-- USER TAB -->
				<div class="tab-pane p-3" id="content-Users" role="tabpanel" aria-labelledby="tab-Users">
					<p>The following users are logged in to this minon:</p>
					<ul>
EOT;

	$user_sth = dbQuery("SELECT `user_name` FROM `minion_user`, `user` WHERE `server_id` = $id AND `minion_user`.`user_id` = `user`.`user_id` ORDER BY `user_name`;");
	echo("\n");
	while ($user_row = $user_sth->fetch()) {
		echo("\t\t\t\t\t<li>" . $user_row["user_name"] . "</li>\n");
	}

	echo <<<EOT
					</ul>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function() {
			$('#packageTable').DataTable({
				"ajax": "{$jsonPackages}",
				"columns": [
					{ data: "package_name" },
					{ data: "package_version" }
				],
				"responsive": true
			});


EOT;

	$active = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING);

	if($active !== NULL && in_array($active, $details)) {
		printf("\t\t\t$('#tab-%s').trigger('click');\n", $active);
	} else {
		printf("\t\t\t$('#tab-Summary').trigger('click');\n");
	}

	echo <<<EOT
		});
	</script>

EOT;

	printPageEnd();
}

function summary() {
	//printPageStart("Minions");
	printPageStart();

	$json = mkPath("/json/minions.json.php");

    echo <<<EOT
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
            "ajax"      : '{$json}',
            "columns"   : [
                {
                    orderable : false,
                    className : 'select-checkbox',
                    data : function() {
                        return "";
                    }
                },
                {data : "server_id", visible : false},
				{
					data : "fqdn",
					render: function(data, type, row, meta) {
						return "<a href=\"minions.php?id=" + row["server_id"] + "\">" + data + "</a>";
					}
				},
				{data : "os"},
                {data : "osrelease"},
                {data : "kernelrelease" },
                {
                    data: "package_total",
                    render: function(data, type, row, meta) {
                        if (data == 0) {
                            return 0;
                        }
						return "<a href=\"minions.php?id=" + row["server_id"] + "&amp;tab=Packages\">" + data + "</a>";
                    }
                },
                { data: "users" },
                { data: "saltversion" },
                { data: "selinux_enforced" },
                { data: "last_seen", render: $.fn.dataTable.render.moment('X', 'YYYY-MM-DD HH:mm') },
                { data: "last_audit", render: $.fn.dataTable.render.moment('X', 'YYYY-MM-DD HH:mm') }
            ],
            "info"       : true,
            "order"      : [[2, 'asc']],
            "ordering"   : true,
            "paging"     : false,
            "responsive" : true,
            "searching"  : true,
            "select"     : {
                "style"    : 'os',
                "selector" : 'td:first-child'
            },
            "createdRow" : function(row, data, dataIndex) {
                // highlight rows for minions that have not been
                // heard from for over two minutes
                if (new Date().getTime() - (data["last_seen"] * 1000) > 120000){
                    $(row).addClass("table-danger");
                }
            }
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
            document.location = "diff.php?server1=" + rows[0]["server_id"] + "&server2=" + rows[1]["server_id"];
        });

        setInterval(reloadJson, 60000); // reload feed every 60s
    });
</script>

<div class="row">
    <div class="col-md-12">
        <div id="alertBox"></div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
		<table id="minionTable" class="table table-hover table-sm table-striped" cellspacing="0" cellpadding="0">
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
        </table>
    </div>
</div>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">With selected: <button id="diffBtn" type="button" class="btn btn-primary" disabled>Diff Packages</button></div>
</div>
EOT;

	printPageEnd();
}

?>
