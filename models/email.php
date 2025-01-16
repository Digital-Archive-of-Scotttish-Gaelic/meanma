<?php

namespace models;

use PHPMailer\PHPMailer\PHPMailer;

class email
{
  private $to, $subject, $message, $from;
  private $cc = array();

  private $mail;

  public function __construct($to, $subject, $message, $from) {

      // To send HTML mail, the Content-type header must be set
      $headers[] = 'MIME-Version: 1.0';
      $headers[] = 'Content-type: text/html; charset=iso-8859-1';
      $headers[] = $to;
      $headers[] = 'From: DASG <mail@dasg.ac.uk>';

      $message = '<html><body>' . $message . '</body></html>';

      if (mail($to, $subject, $message, implode("\r\n", $headers))) {
          echo "Email sent successfully!";
      } else {
          echo "Failed to send email.";
      }

      die();

      $this->mail = new PHPMailer;

	  $this->mail->SMTPDebug = 3;
	  $this->mail->isSMTP();
	  $this->mail->Host = getenv("SMTPHost");
	  $this->mail->SMTPAuth = true;
//Provide username and password
	  $this->mail->Username = getenv("SMTPUser");
	  $this->mail->Password = getenv("SMTPPass");
//If SMTP requires TLS encryption then set it
	  $this->mail->SMTPSecure = "tls";
//Set TCP port to connect to
	  $this->mail->Port = 587;

	  $this->mail->addAddress($to);
      $this->mail->Subject = $subject;
      $this->mail->Body = $message;
      $this->mail->From = $from;
	  $this->mail->isHTML(true);
  }

  public function getTo() {
    return $this->mail->getToAddresses();
  }

  public function getSubject() {
    return $this->mail->Subject;
  }

  public function getMessage() {
    return $this->mail->Body;
  }

  public function getFrom() {
    return $this->mail->From;
  }

  public function setCc($cc) {
    $this->mail->addCC($cc);
  }

  public function getCc() {
    return $this->mail->getCcAddresses();
  }

  public function getCcList() {
    return implode(",", $this->mail->getCc());
  }
/*
  public function getHeaders() {
    $headers[] = "From: " . $this->mail->getFrom();
    $headers[] = "Reply-To: " . $this->mail->getFrom();
    if($this->mail->getCc())
      $headers[] = "CC: " . $this->mail->getCcList();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=ISO-8859-1";
    return implode("\r\n", $headers);
  }
*/
  public function send() {
    if (!$this->mail->send()) {
		echo 'Mail send error : ' . $this->mail->ErrorInfo;
    } else {
		echo 'Email sent successfully';
    }
  }
}