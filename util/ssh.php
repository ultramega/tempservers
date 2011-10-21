<?php
require('../include/common.php');
$fname = substr(md5(mt_rand()), 0, 8);
$ret = ssh('touch ' . $fname, 0);
printf('Creating %s - %s', $fname, $ret);
?>