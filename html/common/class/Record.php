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

require_once("Database.php");

class Record {
	protected $db         = NULL;
	protected $fields     = array();
	protected $idField    = NULL;
	protected $idVal      = NULL;
	protected $properties = array();
	protected $tableName  = NULL;

	public function __construct($db, $tableName, $idField, $fields, $idVal, $row = NULL) {
		$this->db        = $db;
		$this->tableName = $tableName;
		$this->idField   = $idField;
		$this->fields    = $fields;
		$this->idVal     = $idVal;

		if ($row === NULL) {
			$this->loadValues();
		}
		else {
			foreach($this->fields as $f) {
				$this->properties[$f] = array_key_exists($f, $row) ? $row[$f] : NULL;
			}
		}
	}

	protected function getProperty($key) {
		return $this->properties[$key];
	}

	protected function loadValues() {
		$qry = sprintf("SELECT * FROM `%s` WHERE `%s` = '%s'", $this->tableName, $this->idField, $this->idVal);
		$sth = $this->db->query($qry);

		if($sth->rowCount() != 1) {
			throw new Exception(sprintf("Unable to load '%s' from database where '%s' = '%s'.", get_class($this), $this->idField, $this->idVal));
			die(sprintf("Unable to load %s. No record in database where '%s' = %s.", get_class($this), $this->idField, $this->idVal));
		}

		$row = $sth->fetch();

		foreach($this->fields as $f) {
			$this->properties[$f] = array_key_exists($f, $row) ? $row[$f] : NULL;
		}
	}

	protected function setProperty($key, $val) {
		if(in_array($key, $this->fields)) {
			$this->properties[$key] = $val;
			return true;
		}

		return false;
	}

	public function debug() {
		printf("<div style=\"background:#f0f0f0;border:1px solid #a0a0a0;padding:10px;width:300px;\">\n");
		printf("<table border=\"1\" style=\"width:100%%\">\n");
		printf("<tr><td colspan=\"2\">%s</td></tr>\n", get_class($this));

		printf("<tr>\n<td>%s</td><td>%s</td></tr>\n", $this->idField, $this->idVal);

		foreach($this->fields as $f) {
			$val = $this->getProperty($f);

			if($val == NULL) {
				$val = "NOT SET";
			}

			printf("<tr>\n<td>%s</td><td>%s</td></tr>\n", $f, $val);
		}

		printf("</table>\n");
		printf("</div>");
	}

	public function getId() {
		return $this->idVal;
	}
}

?>
