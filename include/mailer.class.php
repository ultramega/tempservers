<?php
/**
 * Mailer class
 *
 * Contains all functionality for building and sending emails.
 */
class Mailer {
    public $headers, $to, $subject, $body;
    /**
     * Initialize common email options
     *
     * @param <string> $to Valid email address to send to, or 'admins' to send to all admins
     * @param <string> $from Contents of the FROM header
     * @param <string> $subject Subject of the email
     */
    public function __construct($to, $from, $subject) {
        if($to == 'admins') {
            if($result = DB::get()->query("SELECT `email` FROM `users` WHERE `admin` = '1'")) {
                while($row = $result->fetch_assoc()) {
                    self::addTo($row['email']);
                }
                $result->close();
            }
        }
        elseif(self::isValidEmail($to)) {
            $this->to[] = $to;
        }
        $this->headers = array('From' => $from, 'Return-Path' => $from);
        $this->subject = $subject;
    }
    /**
     * Add a recipient to the email
     *
     * @param <string> $email Valid email address
     */
    public function addTo($email) {
        if(self::isValidEmail($email)) {
            $this->to[] = $email;
        }
    }
    /**
     * Write raw text into the body
     *
     * @param <string> $text The body of the message
     */
    public function writeText($text) {
        $this->body = $text;
    }
    /**
     * Write the body from a template
     *
     * @param <string> $template Name of the template file
     * @param <array> $vars Associative array of template variables
     */
    public function writeTemplate($template, $vars) {
        if(!empty($template) && is_array($vars)) {
            $templateFile = sprintf("template/email/%s.txt", strtolower($template));
            if(is_file($templateFile)) {
                $templateData = file_get_contents($templateFile);
                foreach($vars as $key => $val) {
                    $templateData = str_ireplace('{' . $key . '}', $val, $templateData);
                }
                $signatureFile = 'template/email/signature.txt';
                if(is_file($signatureFile)) {
                    $signatureData = file_get_contents($signatureFile);
                    $templateData = str_replace('{SIGNATURE}', $signatureData, $templateData);
                }
                $templateData = str_replace('{BASEURL}', BASEURL, $templateData);
                $this->body = $templateData;
            }
        }
    }
    /**
     * Send the email
     */
    public function send() {
        if(is_array($this->headers) && is_array($this->to) && !empty($this->subject) && !empty($this->body)) {
            $headers = '';
            $mailBody = wordwrap($this->body, 70);
            foreach($this->headers as $key => $val) {
                $headers .= $key . ': ' . $val . "\r\n";
            }
            $headers .= "\r\n";
            foreach($this->to as $to) {
                mail($to, $this->subject, $mailBody, $headers);
            }
        }
    }
    /**
     * Validate an email address
     *
     * @param <string> $email Email address
     * @return <bool> TRUE if email is valid
     */
    public static function isValidEmail($email) {
        return (filter_var($email, FILTER_VALIDATE_EMAIL) !==  false);
    }
}