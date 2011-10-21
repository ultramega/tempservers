<?php
require('include/common.php');
check_mm();

if(!$userData->loggedIn()) {
	if(isset($_GET['tid'])) {
		header('Location: login?redir=support-view?tid=' . $_GET['tid']);
	}
	else {
		header('Location: login?redir=support');
	}
}
else {
	$db = DB::get();
	$admin = $userData->isAdmin();
	
	$dtZone = new DateTimeZone($userData->tz);
	
	$status_list = array('New', 'Open', 'Closed');
	$template = new Template('Support');
	$template->appendToHead('<script type="text/javascript" src="js/support.js"></script>');
	$template->head();
	$a = '';
	if(isset($_GET['a'])) {
		$a = $_GET['a'];
	}
?>
<div id="body-wide">
  <div class="block">
<?php
	switch($a) {
		case 'new':
			if(isset($_POST['title'], $_POST['message'], $_POST['category'])) {
				if(empty($_POST['title']) || empty($_POST['message']) || !is_numeric($_POST['category'])) {
					$msg = 'Please complete all fields';
				}
				else {
					$support = new Support();
					if($support->createTicket($userData->getUid(), $_POST['category'], $_POST['title'], $_POST['message'])) {
						$mailer = new Mailer('admins', ADMIN_EMAIL, 'New Ticket: ' . $_POST['title']);
						$mailer->writeTemplate('support_new', array('user' => $userData->getUser(), 'title' => $_POST['title'], 'message' => $_POST['message'], 'tid' => $support->tid));
						$mailer->send();
						
						header('Location: support-view?tid=' . $support->tid);
						exit;
					}
				}
			}
?>
    <div class="path"><a href="support">Support Main</a> -&gt; New Ticket</div>
    <h1>New Ticket</h1>
<?php
			if(isset($msg)) {
				$userData->showMessage();
				echo '<div class="error">' . $msg . '</div>';
			}
?>
    <div id="error" class="error hidden"></div>
    <form id="newthread" method="post" action="support-new">
      <div>
        <label for="category" class="float">Category: </label>
        <select id="category" name="category">
          <option<?php if(!isset($_POST['category']) || !is_numeric($_POST['category'])) { echo ' selected="selected"'; } ?>>--Select Category--</option>
<?php
			if($result = $db->query("SELECT * FROM `support_cat` ORDER BY `category` ASC")) {
				while($row = $result->fetch_assoc()) {
?>
          <option value="<?php echo $row['catID']; ?>"<?php if(isset($_POST['category']) && $_POST['category'] == $row['catID']) { echo ' selected="selected"'; } ?>><?php echo htmlspecialchars($row['category']); ?></option>
<?php
				}
				$result->close();
			}
?>
        </select><br />
        <label for="title" class="float">Title: </label>
        <input type="text" name="title" id="title" size="60" maxlength="128"<?php if(!empty($_POST['title'])) { echo ' value="' . htmlspecialchars($_POST['title']) . '"'; } ?> /><br />
        <label for="message" class="float">Message: </label><br />
        <textarea name="message" id="message" rows="10" cols="50" class="fullwide"><?php if(!empty($_POST['message'])) { echo htmlspecialchars($_POST['message']); } ?></textarea>
      </div>
      <div class="submit">
        <input type="submit" id="submit" name="submit" value="Post Message" />
      </div>
    </form>
<?php
		break;
		case 'view':
			if(isset($_GET['tid'])) {
				$support = new Support($_GET['tid']);
				if($admin || $support->checkAccess($userData->getUid())) {
					if(isset($_POST['submit'])) {
						if(empty($_POST['message'])) {
							$msg = 'Please complete all fields';
						}
						else {
							$support->addMessage($userData->getUid(), $_POST['message']);
							$status = 1;
							if(isset($_POST['close'])) {
								$status = 2;
							}
							$support->setStatus($status);
							
							$ticket = $support->getTicket();
							if(!$admin) {
								$to = 'admins';
							}
							else {
								$to = $ticket[0]['email'];
							}
							$mailer = new Mailer($to, ADMIN_EMAIL, 'Reply to Ticket: ' . $ticket[0]['title']);
							$mailer->writeTemplate('support_reply', array('title' => $ticket[0]['title'], 'tid' => $_GET['tid'], 'message' => $_POST['message']));
							$mailer->send();
						}
					}
					if(!isset($ticket)) {
						$ticket = $support->getTicket();
					}
					$title = htmlspecialchars($ticket[0]['title']);
					$category = htmlspecialchars($ticket[0]['category']);
					$poster = htmlspecialchars($ticket[0]['user']);
					$message = nl2br(htmlspecialchars($ticket[0]['content']));
					$dt = new DateTime(date('r', $ticket[0]['time']));
					$dt->setTimeZone($dtZone);
					$time = $dt->format(DFORMAT . ' ' . TFORMAT);
					$status = $status_list[$ticket[0]['status']];
?>
    <div class="path"><a href="support">Support Main</a> -&gt; <a href="support-list">Ticket List</a> -&gt; View Ticket</div>
<?php
					$userData->showMessage();
?>
    <h1>View Ticket</h1>
    <p>Title: <strong><?php echo $title; ?></strong><br />
    Category: <?php echo $category; ?><br />
    Status: <?php echo $status; ?></p>
    <div class="post" id="top">
      <div class="meta">Posted on <?php echo $time; ?> by <?php echo $poster; ?></div>
      <p><?php echo $message; ?></p>
    </div>
<?php
					$num = count($ticket);
					for($i = 1; $i < $num; $i++) {
						$poster = htmlspecialchars($ticket[$i]['user']);
						$message = nl2br(htmlspecialchars($ticket[$i]['content']));
						$dt = new DateTime(date('r', $ticket[$i]['time']));
						$dt->setTimeZone($dtZone);
						$time = $dt->format(DFORMAT . ' ' . TFORMAT);
?>
    <div class="post">
      <div class="meta">Reply posted on <?php echo $time; ?> by <?php echo $poster; ?></div>
      <p><?php echo $message; ?></p>
    </div>
<?php
					}
				}
?>
  </div>
  <div class="block">
    <h1>Add Reply</h1>
    <?php if(isset($msg)) { echo '<div class="error">' . $msg . '</div>'; } ?>
    <div id="error" class="error hidden"></div>
    <form id="reply" method="post" action="support-view?tid=<?php echo $_GET['tid']; ?>">
      <div>
        <textarea name="message" id="message" rows="4" cols="50" class="fullwide"></textarea><br />
        <label><input type="checkbox" id="close" name="close" value="close" /> Close ticket</label>
      </div>
      <div class="submit">
        <input type="submit" id="submit" name="submit" value="Post Reply" />
      </div>
    </form>
<?php
			}
		break;
		case 'list':
?>
    <div class="path"><a href="support">Support Main</a> -&gt; Ticket List</div>
    <h1>Tickets</h1>
<?php
			if($admin) {
				$uid = 0;
			}
			else {
				$uid = $userData->getUid();
			}
			if($tickets = Support::listTickets($uid)) {
				echo '    <table><tr><th>Title</th><th>Category</th><th>Replies</th><th>Status</th></tr>';
				foreach($tickets as $row) {
					$title = htmlspecialchars($row['title']);
					$category = htmlspecialchars($row['category']);
					echo '<tr><td><a href="support-view?tid=' . $row['id'] . '">' . $title . '</a></td><td>' . $category . '</td><td>' . $row['replies'] . '</td><td>' . $status_list[$row['status']] . '</td></tr>';
				}
				echo '</table>';
			}
			else {
				echo '<p>None</p>';
			}
		break;
		default:
?>
    <h1>TempServers Support</h1>
<?php
			$userData->showMessage();
?>
    <p class="attn">Welcome to TempServers support.</p>
    <div id="support-menu">
      <a href="support-new">
        <img src="images/support_new.png" alt="Create New Ticket" height="65" width="65" />
        <h2>Create New Ticket</h2>
        <p>Have a problem or request? Go here.</p>
      </a>
      <a href="support-list">
        <img src="images/support_list.png" alt="View Tickets" height="65" width="65" />
        <h2>View Tickets</h2>
        <p>Go here to check on your existing support tickets.</p>
      </a>
    </div>
<?php
		break;
	}
?>
  </div>
</div>
<?php
	$template->foot();
}
?>