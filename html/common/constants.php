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

ini_set("date.timezone", "Europe/London");
ini_set("display_errors", "on");

define("DB_HOST",			"localhost");
define("DB_NAME",			"salt_minion");
define("DB_PASSWD_FILE",	sprintf("%s/dbpasswd", dirname(__FILE__)));
define("DB_USER",			"salt_minion");
define("HOMEPAGE",			"/overview.php");
define("SITE_NAME",			"Salt Minion Inventory");
define("WWW_INSTALL_DIR",	"/salt");
?>
