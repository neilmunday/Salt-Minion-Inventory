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
printPageStart();

?>

<h1>About</h1>

<p>The Salt Minion Inventory was created by <a href="https://www.mundayweb.com">Neil Munday</a> and <a href="https://www.cupofbeans.com">David Murray</a> to provide a user friendly way to view the status of SaltStack minons.</p>

<p>The latest version can always be found at: <a href="https://github.com/neilmunday/Salt-Minion-Inventory/releases">https://github.com/neilmunday/Salt-Minion-Inventory/releases</a></p>

<p>If you think you have found a bug or would like to propose a new feature then please use our <a href="https://github.com/neilmunday/Salt-Minion-Inventory/issues">GitHub issue tracker</a>.</p>

<p>Pull requests are welcome also!</p>

<?php
printPageEnd();
?>
