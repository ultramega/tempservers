<?php
require('include/common.php');

$req = 'cmd=_notify-validate';
foreach($_POST as $key => $value) {
    $value = urlencode(stripslashes($value));
    $req .= '&' . $key . '=' . $value;
}
$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen('ssl://' . PP_URL, 443, $errno, $errstr, 30);

$item_number = $_POST['item_number'];
$payment_status = $_POST['payment_status'];
$payment_amount = $_POST['mc_gross'];
$txn_id = $_POST['txn_id'];
$receiver_id = $_POST['receiver_id'];
$payer_email = $_POST['payer_email'];

if($fp) {
    fputs($fp, $header . $req);
    while(!feof($fp)) {
        $res = fgets($fp, 1024);
        $db = DB::get();
        if(strcmp($res, "VERIFIED") == 0 && $receiver_id == PP_ID && strcmp($payment_status, "Completed") == 0) {
            if($result = $db->query("SELECT * FROM `trans` WHERE `transID` = '" . $db->escape_string($item_number) . "'")) {
                $row = $result->fetch_assoc();
                $result->close();

                if($row['txn_id'] != $txn_id && $row['amount'] == $payment_amount) {
                    $db->query("UPDATE `trans` SET `email` = '" . $db->escape_string($payer_email) . "', `txn_id` = '" . $db->escape_string($txn_id) . "' WHERE `transID` = '" . $db->escape_string($item_number) . "'");

                    $res = new Reservation($row['sid']);
                    $res->activate();
                }
            }
        }
        else {
            log_action('PPERROR', $item_number, $txn_id, $receiver_id, $payer_email, $payment_amount, $payment_status);
        }
    }
    fclose($fp);
}
?>