<?php
/*
  This file is part of the Salt Minion Inventory.

  Salt Minion Inventory provides a web based interface to your
  SaltStack minions to view their state.

  Copyright (C) 2019-2023 Neil Munday (neil@mundayweb.com)

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

class Database {
	protected $dbHandle = NULL;
	protected $dbName   = NULL;

	public function __construct($dbHost, $dbUser, $dbPass, $dbName = NULL) {
		if ($dbName === NULL) {
			$this->dbHandle = new PDO('mysql:host=' . $dbHost . ';charset=utf8mb4', $dbUser, $dbPass);
		}
		else {
			$this->dbName = $dbName;
			$this->dbHandle = new PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4', $dbUser, $dbPass);
		}
		$this->dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//$this->dbHandle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->dbHandle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	public function prepare($qry) {
		$sth = $this->dbHandle->prepare($qry);
		return $sth;
	}

	public function query($qry) {
		$sth = $this->prepare($qry);
		$sth->execute();
		//error_log(sprintf("QUERY = %s", $qry));
		return $sth;
	}

	public function useDatabase($dbName) {
		$this->dbName = $dbName;
		$this->query("USE `" . $dbName . "`");
	}
}

?>
