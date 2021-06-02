<?php

namespace models;

class email
{
  private $to, $subject, $message, $from;
  private $cc = array();

  public function __construct($to, $subject, $message, $from) {
    $this->to = $to;
    $this->subject = $subject;
    $this->message = $message;
    $this->from = $from;
  }

  public function getTo() {
    return $this->to;
  }

  public function getSubject() {
    return $this->subject;
  }

  public function getMessage() {
    return $this->message;
  }

  public function getFrom() {
    return $this->from;
  }

  public function setCc($cc) {
    $this->cc = $cc;
  }

  public function getCc() {
    return $this->cc;
  }

  public function getCcList() {
    return implode(",", $this->getCc());
  }

  public function getHeaders() {
    $headers[] = "From: " . $this->getFrom();
    $headers[] = "Reply-To: " . $this->getFrom();
    if($this->getCc())
      $headers[] = "CC: " . $this->getCcList();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=ISO-8859-1";
    return implode("\r\n", $headers);
  }

  public function send() {
    mail($this->getTo(), $this->getSubject(), $this->getMessage(), $this->getHeaders());
  }
}