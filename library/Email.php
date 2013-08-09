<?php
class Email
{
	var $_to;
	var $_replyTo;
	var $_from;
	var $_ccs;
	var $_bccs;
	
	var $subject;
	var $content;
	var $_lastContent;
	var $_formatedContent;
	var $_boundary;
	
	var $attachImages;
	var $forceReformatContent;
	var $debug;
	
	var $_smtpConnection;
	var $_smtpTimeout;
	
	var $_lf;

	function __construct()
	{
		$this->_lf = ((strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "\r\n" : "\n");
		
		$this->_to = array("", "");
		$this->_replyTo = array("", "");
		$this->_from = array("", "");
		$this->_ccs = array();
		$this->_bccs = array();
		
		$this->subject = "";
		$this->content = "";
		
		$this->_lastContent = "";
		$this->_formattedContent = "";
		
		$this->attachImages = false;
		$this->forceReformatContent = false;
		$this->debug = false;
	}
	
	function loadTemplate($path)
	{
		$this->content = File::get($path);
	}
	
	function setTemplateValue($mask, $value)
	{
		$this->content = str_replace($mask, $value, $this->content);
	}
	
	public function from($email, $name = "")
	{
		$this->_from[0] = $this->_extractEmail($email);
		$this->_from[1] = $name;
	}
	
	public function replyTo($email, $name = "")
	{
		$this->_replyTo[0] = $this->_extractEmail($email);
		$this->_replyTo[1] = $name;
	}
	
	public function to($email, $name = "")
	{
		$this->_to[0] = $this->_extractEmail($email);
		$this->_to[1] = $name;
	}
	
	public function addCC($email, $name = "")
	{
		$this->_ccs[] = array($this->_extractEmail($email), $name);
	}
	
	public function addBCC($email, $name = "")
	{
		$this->_bccs[] = array($this->_extractEmail($email), $name);
	}
	
	public function send()
	{
		$this->_formatContent();
		
		$headers = $this->_getHeaders();
		
		$to = $this->_formattedEmail($this->_to);
		$subject = $this->subject;
		$content = $this->_formattedContent;
		$headers = $this->_getHeaders();
		
		if (mail($to, $subject, $content, $headers, "-r " . $this->_from[0]))
		{
			$headers .= "Return-Path: " . $this->_from[0] . $this->_lf;
		
			$r = mail($to, $subject, $content, $headers);
		}
		else
		{
			$r = true;
		}
		
		return $r;
	}
	
	public function sendSMTP($server, $user, $password, $port = 25)
	{
		$this->_formatContent();
		
		$this->_smtpTimeout = 10;
		$this->_smtpConnection = fsockopen($server, $port, $errno, $errstr, $this->_smtpTimeout);
		stream_set_timeout($this->_smtpConnection, 2);
		
		if (is_resource($this->_smtpConnection))
		{		
			if ($user != "")
			{			
				$this->_smtpPut("EHLO " . $server);
			}
			else
			{
				$this->_smtpPut("HELO " . $server);
			}
			
			$this->_smtpGets();
			
			if ($user != "")
			{
				$this->_smtpPut("AUTH LOGIN");
				$this->_smtpGet();
				$this->_smtpPut(base64_encode($user));
				$this->_smtpGet();
				$this->_smtpPut(base64_encode($password));
				$this->_smtpGet();
			}
			
			if (strpos($user, "@") > 0)
			{
				$from = $user;
			}
			else
			{
				$from = $this->_from[0];
			}
			
			$this->_smtpPut("MAIL FROM: " . $from);
			$this->_smtpGet();
			
			$this->_smtpPut("RCPT TO: " . $this->_to[0]);
			$this->_smtpGet();
			
			$this->_smtpPut("DATA");
			$this->_smtpGet();
			
			$this->_smtpPut("To: " . $this->_formattedEmail($this->_to));
			$this->_smtpGet();
			
			$this->_smtpPut($this->_getHeaders());
			$this->_smtpPut("\r\n");
			
			$this->_smtpPut($this->_formattedContent);
			
			$this->_smtpPut(".");
			$r = $this->_smtpGet();
			
			$this->_smtpPut("QUIT");
			//$this->_smtpGet();
			
			if (is_resource($this->_smtpConnection))
			{
				fclose($this->_smtpConnection);
			}
			
			if (substr($r, 0, 1) == "2")
			{
				$r = true;
			}
			else
			{
				$r = false;
			}
		} else {
			$r = false;
		}
		
		return $r;
	}
	
	function _smtpPut($string)
	{	
		if (substr($string, -2) != "\r\n")
		{
			$string .= "\r\n";
		}
		
		if ($this->debug) {
			echo ">>> " . $string . "<br>";
		}
		
		$string = str_replace("\r\n", "#####*#####", $string);
		$string = str_replace("\n", "#####*#####", $string);
		$string = str_replace("#####*#####", "\r\n", $string);
		
		return @fputs($this->_smtpConnection, $string);
	}
	
	function _smtpGet()
	{
		$v = @fgets($this->_smtpConnection, 512);
		if ($this->debug) {
			echo "<<< " . $v . "<br>";
		}
		
		return substr($v, 0, 3);
	}
	
	function _smtpGets() 
	{
		//stream_set_timeout($this->_smtpConnection, 2);
		
		$r = "";
		while (($c = fgetc($this->_smtpConnection)) !== false)
		{
			$r .= $c;
		}
		
		if ($this->debug)
		{
			echo "<<< " . nl2br($r);
		}
		
		//stream_set_timeout($this->_smtpConnection, $this->_smtpTimeout);
				
		return $r;
	}
	
	private function _extractEmail($email)
	{
		if (preg_match('/\<(.*)\>/', $email, $match))
		{
			return $match['1'];
		}
		else
		{
			return $email;
		}
	}
	
	private function _formattedEmail($pair)
	{
		if ($pair[1] != "")
		{
			return $pair[1] . " <" . $pair[0] . ">";
		}
		else
		{
			return $pair[0];
		}
	}
	
	private function _formatContent()
	{
		global $C;
		
		$lf = $this->_lf;
		
		if (($this->_lastContent != $this->content && $this->_formattedContent != $this->content) || $this->forceReformatContent)
		{
			$this->forceReformatContent = false;
			
			$this->_boundary = "_=======" . @date('YmdHms'). time() . "=======_";
				
			$this->_lastContent = $this->content;
			$c = $this->content;
			$cImgs = "";
			$formattedContent = "";
			
			//Get server from 'from' e-mail for imgs cids. eg: server.com.br
			$arr = explode("@", $this->_from[0]);
			$server = $arr[1];
			
			if ($this->attachImages)
			{
				//Find: <img... src=""...> and <... url() ...>
				$c = $this->content;
				$i = 0;
				$imgs = array();
				
				while ((preg_match('#<img(.+?)'.preg_quote("src", '/').'(.+?)>|<(.+?)'.preg_quote("background=", '/').'(.+?)>#i', $c, $m)) && ($i < 150)) {
					
					if (strpos($m[0], "background=") > 0) {
						$imgs[] = array($m[0], str_replace(array("'", "\""), "", $m[4]));
						
						$pos = strpos($c, $m[0]) + strlen($m[0]);
					} else {
						$p2 = (int)strpos($m[2], '"', 2);
						$p1 = (int)strpos($m[2], "'", 2);
						if ($p1 == 0) { $p1 = $p2; }
		
						$imgs[] = array($m[0], substr($m[2], 2, ($p1 - 2)));
						$pos = strpos($c, $m[0]) + strlen($m[0]);
					}
					
					$c = substr($c, $pos);
					
					$i++;
				}
				
				//Replace imgs urls to imgs cids and generate contents.
				$c = $this->content;
				$imgTags = array();
				$imgFiles = array();
				$allowedExtensions = array("jpg", "gif", "png");
				foreach ($imgs as $v) {
					$tag = $v[0];
					$path = $v[1];

					if ((array_search(File::extension($path), $allowedExtensions) !== false) && (array_search($tag, $imgTags) === false))
					{
						$fileName = File::fileName($path);
						$id = "IMG_" . str_replace(array("." . $ext, " "), "", $fileName) . "@" . $server;
						
						$img = str_replace($path, "cid:" . $id, $tag);
						
						if (strpos($c, $tag) !== false) {
							$imgTags[] = $tag;
							
							if ((strpos($img, "moz-do-not-send=\"false\"") == false) && (strpos($img, "<img") !== false)) {
								$img = substr($img, 0, (strlen($img) - 1)) . " moz-do-not-send=\"false\">";
							} elseif ((strpos($img, "url(") !== false)) {
								
							}
							
							$c = str_replace($tag, $img, $c);
							
							if (array_search($path, $imgFiles) === false) {
								$imgFiles[] = $path;
								
								$cImgs .= "--" . $this->_boundary . $lf;
								
								$mime = File::mime($ext);
								
								$cImgs .= "Content-type: " . $mime . "; name=\"" . $fileName . "\"" . $lf;
								$cImgs .= "Content-Transfer-Encoding: base64" . $lf;
								$cImgs .= "Content-ID: <" . $id . ">" . $lf . $lf;
								$cImgs .= chunk_split(base64_encode($file->readFile($v[1]))) . $lf . $lf;
							}
						}
					}
				}
			}
			
			
			//Text plain content
			/*$formattedContent = "--" . $this->_boundary . "\n";
			$formattedContent .= "Content-Type: text/plan; charset=iso-8859-1\n\n";
			$formattedContent .= strip_tags(str_replace(array("\r\n", "\n\r", "\n", "<br>"), array("", "", "", "\n"), str_replace(array("<br/>", "<br />"), "<br>", substr($c, (int)strpos($c, "<body"))))) . "\n";*/
			
			//echo $c;
			
			//Html content
			if ($cImgs != "")
			{
				$formattedContent .= "--" . $this->_boundary . $lf;
				$formattedContent .= "Content-Type: text/html; charset=UTF-8" . $lf . $lf;
				$formattedContent .= $c . $lf . $lf;
			}
			else
			{
				$formattedContent = $c;
			}
			
			//Images contents
			if ($cImgs != "")
			{
				$formattedContent .= $cImgs;
			
				$formattedContent .= "--" . $this->_boundary . "--" . $lf;
			}
			
			$this->_formattedContent = $formattedContent;
		}
	}
	
	private function _getHeaders()
	{
		$headers = "";
		$lf = $this->_lf;
		
		$headers .= "Message-Id: <" . @date('YmdHis') . "." . md5(microtime()) . "." . strtoupper($this->_from[0]) . ">" . $lf;
		if ($this->attachImages) {
			$headers .= "Content-Type: multipart/related; boundary=\"" . $this->_boundary . "\"" . $lf;
		} else {
			$headers .= "Content-Type: text/html; charset=UTF-8" . $lf;
		}
		
		$headers .= "Subject: " . $this->subject . $lf;
		$headers .= "MIME-version: 1.0" . $lf;
		$headers .= "Date: ". @date('D, d M Y H:i:s O') . $lf;
		
		if ($this->_from[0] != "")
		{
			$headers .= "FROM: " . $this->_formattedEmail($this->_from) . $lf;
		}
		
		if ($this->_replyTo[0] != "")
		{
			$headers .= "Reply-To: " . $this->_formattedEmail($this->_replyTo) . $lf;
		}
		
		foreach ($this->_ccs as $v)
		{
			$headers .= "{CC}: " . $this->_formattedEmail($v) . $lf;
		}
		
		foreach ($this->_bccs as $v)
		{
			$headers .= "{BCC}: " . $this->_formattedEmail($v) . $lf;
		}
		
		return $headers;
	}
	
	
}
?>