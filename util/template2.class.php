<?php
class Template2 {
	public $data;
	private $jquery = 'jquery-1.3.2.min';
	private $ui = 'jquery-ui-1.7.2.custom.min';
	private $ui_css = 'ui-lightness/jquery-ui-1.7.2.custom';
	public function __construct($template) {
		if(self::loadMain()) {
			return self::parseTemplate($template);
		}
		return false;
	}
	public function loadMain() {
		$file = sprintf('%s/template/%s.htm', PATH, 'default');
		if(file_exists($file)) {
			$data = file_get_contents($file);
			if(isset($_SESSION['user'])) {
				$status = 'Logged in as <strong>' . htmlspecialchars($_SESSION['user']) . '</strong>.<br /><a href="account">My Account</a> - <a href="logout">Logout</a>';
			}
			else {
				$status = 'Not logged in.<br /><a href="register">Register</a> - <a href="login">Login</a>';
			}
			$data = str_replace('{STATUS}', $status, $data);
			$this->data = $data;
			return true;
		}
		return false;
	}
	public function parseTemplate($template) {
		$file = sprintf("%s/template/%s.htm", PATH, $template);
		if(file_exists($file)) {
			$data = file_get_contents($file);
			$data = explode('{{', $data, 2);
			$meta = $data[0];
			$data = $data[1];
			preg_match_all('/\{\_([A-Z]+)\:(.*?)\}/i', $meta, $meta_tags, PREG_SET_ORDER);
			foreach($meta_tags as $item) {
				$tag = sprintf('{%s}', $item[1]);
				$value = $item[2];
				$this->data = str_replace($tag, $value, $this->data);
			}
			preg_match_all('/\{\#([A-Z]+)\:(.*?)\}/i', $meta, $required, PREG_SET_ORDER);
			foreach($required as $item) {
				$content = $item[2];
				$line = '';
				switch($item[1]) {
					case 'SCRIPT':
						if($content == '[JQUERY]') {
							$content = $this->jquery;
						}
						elseif($content == '[UI]') {
							$content = $this->ui;
						}
						$line = sprintf('<script type="text/javascript" src="js/%s.js"></script>', $content);
					break;
					case 'CSS':
						if($content == '[UI]') {
							$content = $this->ui_css;
						}
						$line = sprintf('<link href="css/%s.css" rel="stylesheet" type="text/css" />', $content);
					break;
				}
				self::addHeader($line);
			}
			$this->data = str_replace('{CONTENT}', $data, $this->data);
		}
	}
	public function addHeader($str) {
		$temp = explode('</head>', $this->data, 2);
		$temp[0] .= $str . "\n";
		$this->data = implode('</head>', $temp);
	}
	public function insertValue($tagname, $tagvalue) {
		if(isset($tagname, $tagvalue)) {
			if(is_array($tagvalue)) {
				return self::insertValueArray($tagname, $tagvalue);
			}
			else {
				return self::insertValueString($tagname, $tagvalue);
			}
		}
		return false;
	}
	public function insertValueString($tagname, $tagvalue) {
		$tag = sprintf('{%s}', $tagname);
		$this->data = str_replace($tag, $tagvalue, $this->data);
		return true;
	}
	public function insertValueArray($tagname, $tagvalue) {
		$pattern = sprintf('/\{\=%s\:(.*?)\}/s', $tagname);
		if(preg_match($pattern, $this->data, $match) > 0) {
			$subtemplate = $match[1];
			$subsection = '';
			foreach($tagvalue as $item) {
				if(is_array($item)) {
					$temp = $subtemplate;
					foreach($item as $tag => $value) {
						$tag = sprintf('[%s]', $tag);
						$temp = str_replace($tag, $value, $temp);
					}
					$subsection .= sprintf('%s%s', "\n", $temp);
				}
			}
			$this->data = preg_replace($pattern, $subsection, $this->data);
			return true;
		}
		return false;
	}
	public function output() {
		echo $this->data;
	}
}
?>