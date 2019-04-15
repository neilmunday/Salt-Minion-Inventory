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

$root = dirname(__FILE__) . "/..";
require_once($root . "/common/constants.php");
require_once($root . "/common/class/Database.php");

function addOrdinalNumberSuffix($num) {
	if(!in_array(($num % 100), array(11, 12, 13))) {
		switch($num % 10) {
			// Handle 1st, 2nd, 3rd
			case 1:  return $num.'st';
			case 2:  return $num.'nd';
			case 3:  return $num.'rd';
		}
	}
	return $num.'th';
}

function dbQuery($qry) {
    return getDatabase()->query($qry);
}

function getDatabase() {
    if (!array_key_exists('db', $GLOBALS) || $GLOBALS['db'] === NULL) {
        if (!file_exists(DB_PASSWD_FILE)) {
            die("No database password found.");
        }

        $GLOBALS['db'] = new Database(DB_HOST, DB_USER, trim(fgets(fopen(DB_PASSWD_FILE, 'r'))), DB_NAME);
    }

    return $GLOBALS['db'];
}

function getLinks() {
    if (!array_key_exists('links', $GLOBALS) || $GLOBALS['links'] === NULL) {
        $GLOBALS['links'] = array();
        $GLOBALS['links'][] = array('label' => 'Overview', 'url' => mkpath("/overview.php"), 'icon' => 'pie-chart');
        $GLOBALS['links'][] = array('label' => 'Minions', 'url' => mkpath("/minions.php"), 'icon' => 'server');
    }

    return $GLOBALS['links'];
}

function getPath() {
	return sprintf("//%s%s", $_SERVER['HTTP_HOST'], $_SERVER['SCRIPT_NAME']);
}

function isActivePage($url) {
	return (getPath() == $url);
}

function mkPath($path = "") {
	return sprintf("//%s%s%s", $_SERVER['HTTP_HOST'], WWW_INSTALL_DIR, $path);
}

function niceTime($secs = 89867) {
	$hours = floor($secs / 3600);
	$secs -= ($hours * 3600);
	$mins  = floor(($secs / 60) % 60);
	$secs -= ($mins * 60);

	return sprintf("%02d:%02d:%02d", $hours, $mins, $secs);
}

function printBodyEnd() {
    printf("    </main>\n");
    printf("    <!-- End of main body -->\n\n");

    printf("    <!-- Bootstrap core javascript placed at the end of the document so the pages load faster -->\n");
    printf("    <script src=\"%s\"></script>\n\n", mkpath("/static/js/bootstrap.bundle.min.js"));

    printf("    <!-- Graphs -->\n");
    printf("    <script src=\"%s\"></script>\n", mkpath("/static/js/Chart.bundle.min.js"));
    printf("  </body>\n");
    printf("</html>\n");
}

function printBodyStart() {
	$home = mkPath(HOMEPAGE);
    $site = SITE_NAME;

	$tabs = array();
	$tabs[] = array("href" => mkPath("/overview.php"), "title" => "Overview");
	$tabs[] = array("href" => mkPath("/minions.php"), "title" => "Minions");

echo <<<EOT
  <body>
	<!-- Start of navbar code -->
	<nav class="navbar navbar-dark fixed-top bg-dark navbar-expand-md shadow">
		<a class="navbar-brand" href="{$home}">{$site}</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#saltNavbar" aria-controls="saltNavbar" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="saltNavbar">
			<ul class="navbar-nav mr-auto">

EOT;

	foreach($tabs as $t) {
		printf("\t\t\t\t<li class=\"nav-item%s\">", (getPath() == $t['href']) ? " active" : "");
		printf("<a class=\"nav-link\" href=\"%s\">%s</a>", $t['href'], $t['title']);
		printf("</li>\n");
	}

echo <<<EOT
			</ul>
		</div>
	</nav>
	<!-- End of navbar code -->

	<!-- Start of main body -->
	<main role="main" class="container">
EOT;
}

function printHeadEnd() {
    printf("    <!-- Cookie consent code -->\n");
    printf("    <link rel=\"stylesheet\" type=\"text/css\" href=\"%s\" />\n", mkpath("/static/css/cookieconsent.min.css"));
    printf("    <script src=\"%s\"></script>\n", mkpath("/static/js/cookieconsent.min.js"));
	printf("    <script>window.addEventListener('load', function(){window.cookieconsent.initialise({'palette': {'popup': {'background': '#252e39'},'button': {'background': '#14a7d0'}},'theme': 'classic'})});</script>\n");
    printf("  </head>\n");
}

function printHeadStart($title = "Unknown") {
    $title = sprintf("%s :: %s", SITE_NAME, $title);

    printf("<!DOCTYPE html>\n");
    printf("<html lang=\"en\">\n");
    printf("  <head>\n");
    printf("    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n");
    printf("    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">\n");
    printf("    <meta name=\"description\" content=\"\">\n");
    printf("    <meta name=\"author\" content=\"\">\n");
    printf("    <!--<link rel=\"icon\" href=\"https://getbootstrap.com/favicon.ico\">-->\n\n");

    printf("    <title>%s</title>\n\n", $title);

    printf("    <!-- Bootstrap core CSS -->\n");
    printf("    <link href=\"%s\" rel=\"stylesheet\">\n\n", mkpath("/static/css/bootstrap.min.css"));

    printf("    <!-- JQuery stuff -->\n");
    printf("    <script src=\"%s\"></script>\n\n", mkpath("/static/js/jquery-3.3.1.min.js"));

    printf("    <!-- Moment code -->\n");
    printf("    <script src=\"%s\"></script>\n\n", mkpath("/static/js/moment.min.js"));

    printf("    <!-- Datatables -->\n");
    printf("    <link href=\"%s\" rel=\"stylesheet\">\n", mkpath("/static/datatables/datatables.min.css"));
    printf("    <script src=\"%s\"></script>\n", mkpath("/static/datatables/datatables.min.js"));
    printf("    <script src=\"%s\"></script>\n\n", mkpath("/static/datatables/datetime.js"));
}

function printPageEnd() {
    printBodyEnd();
}

function printPageStart($title = NULL) {
    printHeadStart($title);

    printf("    <!-- Custom styles for this template -->\n");
    printf("    <link href=\"%s\" rel=\"stylesheet\">\n", mkpath("/static/css/dashboard.css"));

    printHeadEnd();
    printBodyStart();

	if($title !== NULL) {
		printf("<h1 class=\"h2 border-bottom\" style=\"margin-bottom:20px;\">%s</h1>\n", $title);
	}
}

?>
