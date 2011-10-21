<?
require('../include/common.php');
if(isset($_POST['data'])) {
	$cfg = new ServerConfig(0);
	$cfg->parseCfg($_POST['data']);
	print_r($cfg->get(true));
}
?>
<form method="post" action="cfgparse.php">
<textarea name="data" id="data" rows="50" cols="200"><?php if(isset($cfg)) { echo $cfg->get(); } ?></textarea>
<input type="submit" name="submit" id="submit" />
</form>