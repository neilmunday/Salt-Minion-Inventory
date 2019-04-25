<?php
$root = dirname(__FILE__);
require_once($root . "/common/common.php");

if ($_POST['action'] == "login") {
    $sth = dbQuery(sprintf("SELECT * FROM `drivers` WHERE `username` = '%s';", $_POST['u']));

    if ($sth->rowCount() == 1) {
        $row = $sth->fetch();

        if (password_verify($_POST['p'], $row['password'])) {
            $GLOBALS['session']->login($row['id']);
            printf("success");
            exit(0);
        }
    }

    printf("failure");
    exit(1);
}

/* If we've got this far assume logout */
$GLOBALS['session']->logout();
printf("success");
exit(0);

?>
