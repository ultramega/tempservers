<?php
require('includes/common.php');
$mailer = new mailer('stevotvr@sbcglobal.net', 'admin@stevotvr.com', 'Test');
$mailer->send('Test');
?>