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

class Session {
    public static $sessionName = "salt";

    public function __construct() {
        session_start();

        if(!array_key_exists(self::$sessionName, $_SESSION)) {
            $this->init();
        }
    }

    public function debug() {
        printf("<pre>\n");
        print_r($_SESSION);
        printf("</pre>\n");
    }

    public function destroy() {
        $this->init();
        unset($_SESSION[self::$sessionName]);
    }

    public function destroyAll() {
        session_unset();
        session_destroy();
    }

    public function getUid() {
        return $this->getVal("uid");
    }

    public function getVal($key) {
        if(array_key_exists($key, $_SESSION[self::$sessionName])) {
            return $_SESSION[self::$sessionName][$key];
        }

        throw new Exception("No such session variable.");
    }

    public function init() {
        $_SESSION[self::$sessionName] = array();
        $this->reset();
    }

    public function isLoggedIn() {
        return ($this->getVal("loggedIn") > 0);
    }

    public function login($uid = 0) {
        if ($uid > 0) {
            $this->setVal("loggedIn", 1);
            $this->setVal("loggedInSince", time());
            $this->setVal("uid", $uid);
        }
    }

    public function logout() {
        $this->reset();
    }

    public function reset() {
        $this->setVal("loggedIn", 0);
        $this->setVal("loggedInSince", 0);
        $this->setVal("uid", 0);
    }

    public function setVal($key, $val) {
        $_SESSION[self::$sessionName][$key] = $val;
    }
}
?>
