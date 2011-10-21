<?php
require('include/common.php');

if($userData->loggedIn()) {
	$db = DB::get();
	if(isset($_POST['sid'])) {
		$sid = $db->escape_string($_POST['sid']);
		if($db->query("SELECT `sid` FROM `trans` WHERE `sid` = '" . $sid . "' AND `amount` = '0.00'")->num_rows > 0) {
			$res = new Reservation($sid);
			$res->activate();
			header('Location: panel');
			exit;
		}
	}
	if($result = $db->query("SELECT a.`transID`, a.`amount`, a.`credits_used`, b.* FROM `trans` a LEFT JOIN `schedule` b ON a.`sid` = b.`sid` WHERE a.`sid` = '" . $userData->sid . "'")) {
		$row = $result->fetch_assoc();
		$result->close();
		
		$dtZone = new DateTimeZone($userData->tz);
		
		$dtStart = new DateTime(date('r', $row['start']));
		$dtStart->setTimeZone($dtZone);
		$start = $dtStart->format(DFORMAT . ' \a\t ' . TFORMAT);
		
		$dtEnd = new DateTime(date('r', $row['end']));
		$dtEnd->setTimeZone($dtZone);
		$end = $dtEnd->format(DFORMAT . ' \a\t ' . TFORMAT);
		
		$hours = ($row['end']-$row['start'])/3600;
		
		$subtotal = number_format($row['amount']+($row['credits_used']*PRICE), 2);
		$discount = number_format($row['credits_used']*PRICE, 2);
		
		if($row['amount'] > 0) {
			$buttontext = 'Pay Now';
		}
		else {
			$buttontext = 'Confirm Reservation';
		}
		
		
		$template = new Template('Checkout');
		$template->head();
?>
<div id="body-wide">
  <div class="block">
<?php
		$userData->showMessage();
?>
    <h1>Checkout</h1>
    <p>Please review your details below and click <strong><?php echo $buttontext; ?></strong>.</p>
    <div class="total">
      Sub Total: &nbsp;$<?php echo $subtotal; ?><br />
      Credits (<?php echo $row['credits_used']; ?>): -$<?php echo $discount; ?>
      <hr />
      Total: &nbsp;$<?php echo $row['amount']; ?>
    </div>
    <table cellspacing="0" class="order-details">
      <tr><td>Game Title: </td><td><?php echo $gamelist[$row['game']]; ?></td></tr>
      <tr><td>Rcon Password: </td><td><?php echo htmlspecialchars($row['rcon']); ?></td></tr>
      <tr><td>Start Time: </td><td><?php echo $start; ?></td></tr>
      <tr><td>End Time: </td><td><?php echo $end; ?></td></tr>
      <tr><td>Length: </td><td><?php echo $hours; ?> hours</td></tr>
    </table>
    <hr />
<?php
		if($row['amount'] > 0) {
?>
    <form action="https://<?php echo PP_URL; ?>/cgi-bin/webscr" method="post">
      <div class="submit">
        <input type="hidden" name="cmd" value="_xclick" />
        <input type="hidden" name="business" value="<?php echo PP_ID; ?>" />
        <input type="hidden" name="lc" value="US" />
        <input type="hidden" name="item_name" value="TempServers Game Server Reservation (<?php echo $hours; ?> hours)" />
        <input type="hidden" name="item_number" value="<?php echo $row['transID']; ?>" />
        <input type="hidden" name="amount" value="<?php echo $row['amount']; ?>" />
        <input type="hidden" name="currency_code" value="USD" />
        <input type="hidden" name="no_note" value="1" />
        <input type="hidden" name="no_shipping" value="1" />
        <input type="hidden" name="rm" value="1" />
        <input type="hidden" name="return" value="<?php echo BASEURL; ?>panel" />
        <input type="hidden" name="notify_url" value="<?php echo BASEURL; ?>ipn" />
        <input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynow_SM.gif:NonHosted" />
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_paynow_SM.gif" name="submit" alt="Pay with PayPal" />
      </div>
    </form>
<?php
		}
		else {
?>
    <form action="order" method="post">
    <div class="submit">
      <input type="hidden" id="sid" name="sid" value="<?php echo $userData->sid; ?>" />
      <input type="submit" id="confirm" name="confirm" value="Confirm Reservation" />
    </div>
    </form>
<?php
		}
?>
  </div>
</div>
<?php
		$template->foot();
	}
}
?>