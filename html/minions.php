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

	$row = $sth->fetch();
	$details = array("Summary", "Software", "Packages", "Disks", "Network", "GPUs", "Users");
  $disabledTabs = array();

  $count_sth = dbQuery("SELECT COUNT(*) AS `total` FROM `minion_user` WHERE `server_id` = $id;");
  $count_row = $count_sth->fetch();
  if ($count_row['total'] == 0) {
    $disabledTabs[] = "Users";
  }

  $count_sth = dbQuery("SELECT COUNT(*) AS `total` FROM `minion_gpu` WHERE `server_id` = $id;");
  $count_row = $count_sth->fetch();
  if ($count_row['total'] == 0) {
    $disabledTabs[] = "GPUs";
  }

	$jsonPackages	= mkPath("/json/packages.json.php?id=" . $id);
	$lastSeen = date('Y-m-d H:i:s', $row["last_seen"]);
	$lastAudit = date('Y-m-d H:i:s', $row["last_audit"]);

	$active = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING);

	if($active === NULL || !in_array($active, $details)) {
		$active = "Summary";
	}

	printPageStart($row['fqdn']);

	echo <<<EOT
	<div class="card" style="height:500px;">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs" id="minionTabs" role="tablist">

EOT;

	foreach($details as $tab) {
		if ($active == $tab) {
			printf("\t\t\t\t<li class=\"nav-item\"><a aria-selected=\"true\" aria-controls=\"%s\" class=\"nav-link active %s\" data-toggle=\"tab\" id=\"tab-%s\" href=\"#content-%s\" role=\"tab\">%s</a></li>\n", $tab, in_array($tab, $disabledTabs) ? 'disabled' : '', $tab, $tab, $tab);
		} else {
			printf("\t\t\t\t<li class=\"nav-item\"><a aria-selected=\"false\" aria-controls=\"%s\" class=\"nav-link %s\" data-toggle=\"tab\" id=\"tab-%s\" href=\"#content-%s\" role=\"tab\">%s</a></li>\n", $tab, in_array($tab, $disabledTabs) ? 'disabled' : '', $tab, $tab, $tab);
		}
	}

	echo <<<EOT
			</ul>
		</div>
		<div class="card-block" style="overflow-y: auto;">
			<div class="tab-content" id="minionTabContent">

EOT;

	$prefix = "\t\t\t\t";

	foreach($details as $tab) {
		printf("%s<!-- %s TAB -->\n", $prefix, strtoupper($tab));
		printf("%s<div class=\"tab-pane %sp-3\" id=\"content-%s\" role=\"tabpanel\" aria-labelledby=\"tab-%s\">\n", $prefix, ($active == $tab) ? "show active " : "", $tab, $tab);

		switch ($tab) {
			case "Summary":
				printf("%s\t<table class=\"table table-responsive table-sm table-borderless\">\n", $prefix);
				printf("%s\t\t<tr><td>CPUs:</td><td>%s</td></tr>\n", $prefix, $row["num_cpus"]);
				printf("%s\t\t<tr><td nowrap>CPU Model:</td><td>%s</td></tr>\n", $prefix, $row["cpu_model"]);
				printf("%s\t\t<tr><td>GPUs:</td><td>%s</td></tr>\n", $prefix, $row["num_gpus"]);
				printf("%s\t\t<tr><td>Memory:</td><td>%s MB</td></tr>\n", $prefix, $row["mem_total"]);
				printf("%s\t\t<tr><td>BIOS:</td><td>%s (%s)</td></tr>\n", $prefix, $row["biosversion"], $row["biosreleasedate"]);
				printf("%s\t\t<tr><td nowrap>Last Seen:</td><td>%s</td></tr>\n", $prefix, $lastSeen);
				printf("%s\t</table>\n", $prefix);
				break;
			case "Software":
				printf("%s\t<table class=\"table table-responsive table-sm table-borderless\">\n", $prefix);
				printf("%s\t\t<tr><td>OS:</td><td>%s %s</td></tr>\n", $prefix, $row["os"], $row["osrelease"]);
				printf("%s\t\t<tr><td>Kernel:</td><td>%s</td></tr>\n", $prefix, $row["kernelrelease"]);
				printf("%s\t\t<tr><td>Salt Version:</td><td>%s</td></tr>\n", $prefix, $row["saltversion"]);
				printf("%s\t\t<tr><td>Selinux:</td><td>%s</td></tr>\n", $prefix, $row["selinux_enforced"]);
				printf("%s\t\t<tr><td>Packages:</td><td><a href=\"#\" onclick=\"$('#tab-Packages').trigger('click');\">%s</a></td></tr>\n", $prefix, $row["package_total"]);
				printf("%s\t\t<tr><td>Last Audit:</td><td>%s</td></tr>\n", $prefix, $lastAudit);
				printf("%s\t</table>\n", $prefix);
				break;
			case "Packages":
				printf("%s\t<table id=\"packageTable\" class=\"table table-striped table-sm\" width=\"100%%\">\n", $prefix);
				printf("%s\t\t<thead><tr><th>Package</th><th>Version</th></tr></thead>\n", $prefix);
				printf("%s\t\t<tbody></tbody>\n", $prefix);
				printf("%s\t</table>\n", $prefix);
				break;
			case "Disks":
				printf("%s\t<table class=\"table table-responsive table-sm table-borderless\">\n", $prefix);
				printf("%s\t\t<tr><th>Path</th><th>Size</th><th>Vendor</th><th>Serial</th></tr>\n", $prefix);

				$disk_sth = dbQuery("SELECT `vendor_name`, `disk_path`, `disk_serial`, `disk_size` FROM `minion_disk`, `vendor` WHERE `server_id` = $id AND `minion_disk`.`vendor_id` = `vendor`.`vendor_id`;");

				while ($disk_row = $disk_sth->fetch()) {
					printf("%s\t\t<tr>", $prefix);
					printf("<td>%s</td>", $disk_row["disk_path"]);
					printf("<td>%s</td>", formatMegaBytes($disk_row["disk_size"]));
					printf("<td>%s</td>", $disk_row["vendor_name"]);
					printf("<td>%s</td>", $disk_row["disk_serial"]);
					printf("</tr>\n");
				}

				printf("%s\t</table>\n", $prefix);
				break;
			case "Network":
				printf("%s\t<table class=\"table table-responsive table-sm table-borderless\">\n", $prefix);
				printf("%s\t\t<tr><th>Interface</th><th>MAC</th><th>IP</th></tr>\n", $prefix);

				$nw_sth = dbQuery("SELECT `interface`.`interface_id`, `interface_name`, `mac` FROM `interface`, `minion_interface` WHERE `server_id` = $id AND `interface`.`interface_id` = `minion_interface`.`interface_id` ORDER BY `interface_name`;");

				while ($nw_row = $nw_sth->fetch()) {
					printf("%s\t\t<tr>", $prefix);
					printf("<td>%s</td>", $nw_row["interface_name"]);
					printf("<td>%s</td>", $nw_row["mac"]);
					printf("<td>");

					$nw_sub = dbQuery("SELECT `ip4` FROM `minion_ip4` WHERE `server_id` = $id AND `interface_id` = " . $nw_row["interface_id"] . " ORDER BY `ip4`;");
					while ($nw_sub_row = $nw_sub->fetch()) {
						printf("%s<br/>", $nw_sub_row["ip4"]);
					}

					printf("</td>");
					printf("</tr>\n");
				}

				printf("%s\t</table>\n", $prefix);
				break;
			case "GPUs":
				printf("%s\t<table class=\"table table-responsive table-sm table-borderless\">\n", $prefix);
				printf("%s\t\t<tr><th>Model</th><th>Vendor</th></tr>\n", $prefix);

				$gpu_sth = dbQuery("SELECT `vendor_name`, `gpu_model` FROM `minion_gpu`, `gpu`, `vendor` WHERE `server_id` = $id AND `minion_gpu`.`gpu_id` = `gpu`.`gpu_id` AND `gpu`.`vendor_id` = `vendor`.`vendor_id`;");

				while ($gpu_row = $gpu_sth->fetch()) {
					printf("%s\t\t<tr>", $prefix);
					printf("<td>%s</td>", $gpu_row["gpu_model"]);
					printf("<td>%s</td>", $gpu_row["vendor_name"]);
					printf("</tr>\n");
				}

				printf("%s\t</table>\n", $prefix);
				break;
			case "Users":
				printf("%s\t<p>The following users are logged in to this minon:</p>\n", $prefix);
				printf("%s\t<ul>\n", $prefix);

				$user_sth = dbQuery("SELECT `user_name` FROM `minion_user`, `user` WHERE `server_id` = $id AND `minion_user`.`user_id` = `user`.`user_id` ORDER BY `user_name`;");
				while ($user_row = $user_sth->fetch()) {
					printf("%s\t<li>%s</li>\n", $prefix, $user_row["user_name"]);
				}

				printf("%s\t</ul>\n", $prefix);
				break;
		}

		printf("%s</div>\n", $prefix);
	}

	echo <<<EOT
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
		<table id="minionTable" class="table table-hover table-sm table-striped nowrap" width="100%">
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
