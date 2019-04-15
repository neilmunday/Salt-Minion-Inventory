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

$distributions = array();
$titles = array();
$titles['os']      = "Operating Systems";
$titles['salt']    = "Salt Versions";
$titles['selinux'] = "SELinux Mode";

function updateDistribution($dist, $val) {
	global $distributions;

	if(!array_key_exists($dist, $distributions)) {
		$distributions[$dist] = array();
	}

	if(array_key_exists($val, $distributions[$dist])) {
		$distributions[$dist][$val]++;
	} else {
		$distributions[$dist][$val] = 1;
	}
}

printPageStart();

$sth = dbQuery("SELECT * FROM `minion`;");
while($row = $sth->fetch()) {
	updateDistribution("os", sprintf("%s %s", $row['os'], $row['osrelease']));
	updateDistribution("salt", $row['saltversion']);
	updateDistribution("selinux", $row['selinux_enforced']);
}
?>

<script type="text/javascript">
$(document).ready(function() {
	var chartColors = [
		'rgb(54, 162, 235)',
		'rgb(75, 192, 192)',
		'rgb(255, 159, 64)',
		'rgb(255, 99, 132)',
		'rgb(255, 205, 86)',
		'rgb(153, 102, 255)',
		'rgb(201, 203, 207)'
	];

<?php
foreach($distributions as $k => $v) {
	$title  = array_key_exists($k, $titles) ? $titles[$k] : $k;

	//printf("\t/* %s */\n", $title);
	printf("\t/*\n\t * %s\n\t */\n", $title);
	printf("\tvar %sCount  = [%s];\n", $k, join(",", array_values($v)));
	printf("\tvar %sLabels = ['%s'];\n", $k, join("','", array_keys($v)));

echo <<<EOT
	var {$k}Config = {
		type: 'pie',
		data: {
			datasets   : [{data: ${k}Count, backgroundColor: chartColors}],
			labels     : {$k}Labels,
		},
		options: {
			responsive : true,
			legend     : {position: 'right'},
			title      : {display: true, text: '{$title}'},
			animation  : {animateScale: true, animateRotate: true}
		}
	};

	var ${k}Context = document.getElementById("canvasChart_{$k}").getContext("2d");
	new Chart(${k}Context, ${k}Config);


EOT;

}
?>

});
</script>

<div class="row">
  <div class="col-md-4">
    <div class="card">
	  <div class="card-header">Server Information</div>
	  <div class="card-body"><p>Details of the salt master</p><p>Details of the salt master</p><p>Details of the salt master</p><p>Details of the salt master</p></div>
	</div>
  </div>
</div>
<div class="row" style="margin-top:20px;">
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="canvasChart_os" /></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="canvasChart_salt" /></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><canvas id="canvasChart_selinux" /></div></div></div>
</div>

<?php
printPageEnd();
?>