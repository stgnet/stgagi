#!/usr/bin/php 
<?php
//	require 'DB.php';

	require 'stgagi.php';

	function email($from,$to,$subject,$message)
	{
		$handle=popen("/usr/sbin/sendmail -t","w");
		$headers="From: ".$from."\nTo: ".$to."\nX-Mailer: PHP/".phpversion()."\nSubject: ".$subject."\n\n";
		fwrite($handle,$headers.$message);
		pclose($handle);
	}

	// this function is a direct steal from:
	// AGI directory Copyright (C) 2005 Greg MacLellan (greg@mtechsolutions.ca)
	function parse_voicemailconf($filename, &$vmconf, &$section) {
		if (is_null($vmconf)) {
			$vmconf = array();
		}
		if (is_null($section)) {
			$section = "general";
		}
		
		if (file_exists($filename)) {
			$fd = fopen($filename, "r");
			while ($line = fgets($fd, 1024)) {
				if (preg_match("/^\s*(\d+)\s*=>\s*(\d+),(.*),(.*),(.*),(.*)\s*([;#].*)?/",$line,$matches)) {
					// "mailbox=>password,name,email,pager,options"
					// this is a voicemail line	
					$vmconf[$section][ $matches[1] ] = array("mailbox"=>$matches[1],
							"pwd"=>$matches[2],
							"name"=>$matches[3],
							"email"=>$matches[4],
							"pager"=>$matches[5],
							);
					
					// parse options
					foreach (explode("|",$matches[6]) as $opt) {
						$temp = explode("=",$opt);
						if (isset($temp[1])) {
							list($key,$value) = $temp;
							$vmconf[$section][ $matches[1] ]["options"][$key] = $value;
						}
					}
				} else if (preg_match("/^\s*(\d+)\s*=>\s*dup,(.*)\s*([;#].*)?/",$line,$matches)) {
					// "mailbox=>dup,name"
					// duplace name line
					$vmconf[$section][ $matches[1] ]["dups"][] = $matches[2];
				} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
					// include another file
					
					if ($matches[1][0] == "/") {
						// absolute path
						$filename = $matches[1];
					} else {
						// relative path
						$filename =  dirname($filename)."/".$matches[1];
					}
					
					parse_voicemailconf($filename, $vmconf, $section);
					
				} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
					// section name
					$section = strtolower($matches[1]);
				} else if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
						// name = value
						// option line
						$vmconf[$section][ $matches[1] ] = $matches[2];
					}
			}
		}
	}
	// also this one:
	function sound_file_exists($file) {
		global $agi;
		
		foreach (array("gsm","GSM","wav","WAV") as $ext) {
			if (file_exists($file.".".$ext)) {
				return true;
			}
		}
		return false;
	}

	$debug=0;
	if (array_key_exists(1,$argv))
	{
		if ($argv[1]=="install")
		{
			$handle=fopen("/etc/crontab","a") or die("unable to append /etc/crontab");
			fwrite($handle,"0,5,10,15,20,25,30,35,40,45,50,55 * * * * root /var/lib/asterisk/agi-bin/vmpager.php\n") or die("unable to write");
			fclose($handle);
			print("Added crontab entry\n");
			exit(0);
		}
		if ($argv[1]=="debug")
			$debug=1;
	}

	$amp_conf = parse_conf("/etc/amportal.conf");
		
	$directory_file = $amp_conf['ASTETCDIR']."/voicemail.conf";
		
	$vmconf = array();
	$null = null;
	parse_voicemailconf($directory_file, $vmconf, $null);

//print_r($vmconf);

	$users=$vmconf['default'];
		
	$vm_dir = $amp_conf['ASTSPOOLDIR']."/voicemail/";

	foreach ($users as $ext => $user)
	{

		$user_dir=$vm_dir."default/".$user['mailbox'];

		if (!$user['pager'])
			continue;

		$INBOX=$user_dir."/INBOX";
		$count=0;
		if (file_exists($INBOX))
		{
			$dir=@opendir($INBOX);
			while ($file=readdir($dir))
			{
				if (substr($file,0,1)==".")
					continue;

				$ext=explode(".",$file);
				if ($ext[1]!="txt")
					continue;

				if ($debug)
				{
					print($user['mailbox']." => ".$file."\n");
/*
;
; Message Information file
;
[message]
origmailbox=320
context=macro-vm
macrocontext=ext-local
exten=s-NOANSWER
priority=2
callerchan=SIP/apbx-b7d019f8
callerid="AXIA TECHNOLOGY" <3174895544>
origdate=Fri Jun 19 03:30:15 PM EDT 2009
origtime=1245439815
category=
duration=5
*/

					$data=file_get_contents($INBOX."/".$file);
					$lines=explode("\n",$data);
					$message=array();
					foreach ($lines as $line)
					{
						$pair=explode("=",$line);
						if (array_key_exists(1,$pair))
							$message[$pair[0]]=$pair[1];
					}
					print_r($message);
				}
				$count++;
			}
		}
if ($debug)
		print($ext.": ".$user['mailbox']." => ".$user['name']." pager=".$user['pager']." has $count messages\n");

		if ($count && !$debug)
		email("voicemail@".$HOSTNAME,$user['pager'],"VM","You have $count messages in ".$user['mailbox']);

	}
	//print_r($users);
		
?>
