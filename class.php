<?php

class EmailObject {
  
  var $saved_files        = array();
  var $filename_aliases   = array();
  var $inline_image_types = array("png","gif","jpg","jpeg","bmp");
  
  function __construct($mysqli,$uniqid,$source,$file_store,$mailbox_name="INBOX") {
    
    $this->imap_mailbox = $mailbox_name;
    $this->mysqli      = $mysqli;
    $this->uniqid     = $uniqid;
    $this->source     = $source;
    $this->file_store = $file_store;
  }
  
  function readEmail(){
    
    // Decode email message into parts
    $decoder = new Mail_mimeDecode($this->source);

    $this->decoded = $decoder->decode(
      Array(
        "decode_headers" => TRUE,
        "include_bodies" => TRUE,
        "decode_bodies"  => TRUE,
      )
    );
    
    // Get from name and email
    $this->from  = $this->decoded->headers["from"];
	  
    // Get more headers not specified in the forked repository	  
    $this->to  = $this->decoded->headers["delivered-to"];
    $this->date  = $this->decoded->headers["date"];

    // Message ID
    $this->message_id  = $this->decoded->headers["message-id"];

    // Skip duplicate
    $mysqli  = $this->mysqli;
    $result = mysqli_query ($mysqli, "SELECT message_id FROM load_imap_emails WHERE message_id='".$this->message_id."'");
    if($result->num_rows == 1){
      return 0;
    }
	  
    if (preg_match("/.* <.*@.*\..*>/i",$this->from,$matches)) {
      $this->name  = preg_replace("/ <(.*)>$/", "", $this->from);
      $this->email = preg_replace("/.*<(.*)>.*/","$1",$this->from);
    } else {
      $this->email = $this->from;
    }
      
    // Get subject
    $this->subject = trim($this->decoded->headers["subject"]);

    // Get body & attachments (if available)
    if (is_array($this->decoded->parts)) {
      foreach($this->decoded->parts as $arItem => $body_part){
        $this->decodePart($body_part);
      }
    } else {
      $this->bodyText = $this->decoded->body;
    }

	// Save Message to mysqli
    $this->saveToDb();
  }

  // Decode body part
  private function decodePart($body_part){
    
    // Get file and file name
    if (isset($body_part->d_parameters["filename"])) {
     
      // Set file name
      $filename = $body_part->d_parameters["filename"];

      // Save the file
      $this->saveFile($filename,$body_part->body);
      
      // Get content ID for image (as some mailers use CID instead of file name)
      if (isset($body_part->headers["content-id"])) {
        $cid = $body_part->headers["content-id"];
        $cid = str_replace("<","",$cid);
        $cid = str_replace(">","",$cid);

        // Replace the image src reference with the path to saved file in HTML
        $this->bodyHtml = preg_replace("/src=\"(cid:)?".$cid."\"/i", "src=\"[filePath]/".$this->uniqid."/".$filename."\"", $this->bodyHtml);
        
        // Replace the image CID reference in plain text
        $this->bodyText = preg_replace("/\[cid:".$cid."\]/i", "", $this->bodyText);
      }
      
      // Replace the image name reference in plain text
      $this->bodyText = preg_replace("/\[".$filename."\]/i", "", $this->bodyText);
    }
    
    $mimeType = "{$body_part->ctype_primary}/{$body_part->ctype_secondary}";

    // Decode sub-parts
    if ($body_part->ctype_primary == "multipart") {
      if (is_array($body_part->parts)) {
        foreach($body_part->parts as $arItem => $sub_part) {
          $this->decodePart($sub_part);
        }
      }
    }
    
    // Get plain text version
    if ($mimeType == "text/plain") {
      if (!isset($body_part->disposition)) {
        $this->bodyText .= $body_part->body;
      }
    }
    
    // Get HTML version
    if ($mimeType == "text/html") {
      if (!isset($body_part->disposition)) {
        $this->bodyHtml .= $body_part->body;
      }
    }
    echo "<P>".$body_part->ctype_primary;
    if ($body_part->ctype_primary == "body")
      echo $body_part->body;
  }
  
  // Save file
  private function saveFile($filename,$contents) {
    
    // Check if uniqid folder exists
    if (!file_exists($this->file_store."/".$this->uniqid))
      mkdir($this->file_store."/".$this->uniqid);
    
    // Save file
    file_put_contents($this->file_store."/".$this->uniqid."/".$filename, $contents);
    $this->saved_files[] = $filename;
  }
  
  // Save message & files to mysqli
  private function saveToDb() {
    
    $mysqli  = $this->mysqli;
    $uniqid = $this->uniqid;
    
    if (isset($this->bodyText)) {
      $body_text = $this->bodyText;
      $body_text = mysqli_real_escape_string($mysqli, mb_convert_encoding(trim($body_text),'UTF-8','UTF-8'));
    } else {
      $body_text = "";
    }
    
    if (isset($this->bodyHtml)) {

      $body_html = $this->bodyHtml;
    
      // Strip header tag (some email clients)
      $body_html = preg_replace("/<!DOCTYPE(.*?)>(\\r\\n)?/i","",$body_html);
    
      // Strip HTML tags (Yahoo, Mozilla)
      $body_html = preg_replace("/<\/?html(.*?)>(\\r\\n)?/i","",$body_html);
      $body_html = preg_replace("/<\/?head(.*?)>(\\r\\n)?/i","",$body_html);
      $body_html = preg_replace("/<\/?body(.*?)>(\\r\\n)?/i","",$body_html);
      $body_html = preg_replace("/<meta(.*?)>(\\r\\n)?/i","",$body_html);
      $body_html = preg_replace("/<style(.*?)<\/style>(\\r\\n)?/i","",$body_html);

      // Replace superfluous inline image meta
      $body_html = preg_replace("/ id=\"(.*?)\"/i","",$body_html);
      $body_html = preg_replace("/ alt=\"(.*?)\"/i","",$body_html);
      $body_html = preg_replace("/ title=\"(.*?)\"/i","",$body_html);
      $body_html = preg_replace("/ class=\"(.*?)\"/i","",$body_html);
      $body_html = preg_replace("/ data-id=\"(.*?)\"/i","",$body_html);
      $body_html = preg_replace("/ apple-inline=\"yes\"/i","",$body_html);
      
      $body_html = mysqli_real_escape_string($mysqli, mb_convert_encoding(trim($body_html),'UTF-8','UTF-8'));
    } else {
      $body_html = "";
    }
        
    // Prepare data for mysqli
    if (isset($this->name))
      $name = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->name,'UTF-8','UTF-8'));
    else
      $name = "";
    if (isset($this->email))
      $email = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->email,'UTF-8','UTF-8'));
    else
      $email = "";
    if (isset($this->subject))
      $subject = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->subject,'UTF-8','UTF-8'));
    else
      $subject = "";
	  
    // More headers 
    if (isset($this->to))
      //$to = mysqli_real_escape_string(mb_convert_encoding($this->to,'UTF-8','UTF-8'), $mysqli);
      $to = $this->to;
    else
      $to = "";
    if (isset($this->date))
      $message_date = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->date,'UTF-8','UTF-8'));
    else
      $message_date = "";
    if (isset($this->message_id))
      $message_id = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->message_id,'UTF-8','UTF-8'));
    else
      $message_id = "";
    if (isset($this->imap_mailbox))
      $imap_mailbox = mysqli_real_escape_string($mysqli, mb_convert_encoding($this->imap_mailbox,'UTF-8','UTF-8'));
    else
      $imap_mailbox = "INBOX";

	  
    // Insert message to mysqli
    global $imap_user;
    global $imap_host;
    $dateobj = DateTime::createFromFormat( 'D, d M Y H:i:s O', $message_date);
    if (!$dateobj) $dateobj = DateTime::createFromFormat( 'd M Y H:i:s O', $message_date);
    if (!$dateobj) {
	$substring = substr($message_date,0, strpos($message_date,"(")-2);
	$dateobj = DateTime::createFromFormat( 'D, d M Y H:i:s O', $substring);
	}
    $msg_time="";
    if ($dateobj) {
	$msg_time=$dateobj->format( 'Y-m-d H:i:s');
	}
    mysqli_query($mysqli, "INSERT INTO load_imap_emails (uniqid,time,name,email,subject,body_text,body_html,mailto,message_date,message_id,imap_username,imap_host,imap_mailbox) VALUES ('".$uniqid."','".$msg_time."','".$name."','".$email."','".$subject."','".$body_text."','".$body_html."','".$to."','".$message_date."','".$message_id."','".$imap_user."','".$imap_host."','".$imap_mailbox."')");
	  
    // Get the AI ID from mysqli
    $result = mysqli_query ($mysqli, "SELECT id FROM load_imap_emails WHERE uniqid='$uniqid'");
    $row = mysqli_fetch_array($result);
    $email_id = mysqli_real_escape_string($mysqli, $row["id"]);
    
    // Insert all the attached file names to mysqli
    if (sizeof($this->saved_files) > 0) {
      foreach($this->saved_files as $filename){
        $filename = mysqli_real_escape_string($mysqli, mb_convert_encoding($filename,'UTF-8','UTF-8'));
        mysqli_query($mysqli, "INSERT INTO load_imap_files (`email_id`, `filename`) VALUES ('$email_id', '$filename')");
      }
    }
  }
}
