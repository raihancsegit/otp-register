<?php
class SendMail {
    private $to = '';
    private $from = '';
    private $subject = '';
    private $body = '';

    public function to($email) {
        $this->to = $email;
        return $this;
    }
    
    public function from($email) {
        $this->from = $email;
        return $this;
    }

    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    public function body($body) {
        $this->body = $body;
        return $this;
    }

    public function send() {
        //todo: site from & from name should be add on header.
        $headers = 'Content-Type: text/html; charset=UTF-8';
        return wp_mail( $this->to, $this->subject, $this->body, $headers );
    }
}