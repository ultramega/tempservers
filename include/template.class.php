<?php
/**
 * Template class
 *
 * Contains functions for handling template output.
 */
class Template {
    /**
     * Stores the template HTML
     *
     * @var <string> Raw HTML date
     */
    public $htmlData;
    /**
     * Initialize the template
     *
     * @param <string> $title Page title
     * @param <string> $template Name of template to use
     */
    public function __construct($title, $template = 'default') {
        $templateFile = sprintf("template/%s.htm", $template);
        $this->htmlData = file_get_contents($templateFile);
        $this->htmlData = str_replace('{TITLE}', $title, $this->htmlData);
        if(isset($_SESSION['user'])) {
            $statusText = 'Logged in as <strong>' . htmlspecialchars($_SESSION['user']) . '</strong>.<br /><a href="account">My Account</a> - <a href="logout">Logout</a>';
            if(!$_SESSION['active']) {
                $statusText .= '<br /><a href="account?resend=1">Resend Activation</a>';
            }
        }
        else {
            $statusText = 'Not logged in.<br /><a href="register">Register</a> - <a href="login">Login</a>';
        }
        $this->htmlData = str_replace('{STATUS}', $statusText, $this->htmlData);
    }
    /**
     * Append data to the head block
     *
     * @param <string> $text Text to append
     */
    public function appendToHead($text) {
        $temp = explode('</head>', $this->htmlData, 2);
        $temp[0] .= $text . "\n";
        $this->htmlData = implode('</head>', $temp);
    }
    /**
     * Output top half of template
     */
    public function head() {
        $this->htmlData = explode('{CONTENT}', $this->htmlData, 2);
        echo $this->htmlData[0];
    }
    /**
     * Output bottom half of template
     */
    public function foot() {
        echo $this->htmlData[1];
    }
}