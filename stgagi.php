<?php
	// STG AGI for PHP
	// Library functions for Asterisk AGI done in PHP
	// Scott Griepentrog scott@stg.net

	// we don't want errors mussing the AGI stream!
	ini_set("log_errors","1");
	ini_set("error_log","syslog");
	ini_set("display_errors","0");

	// deprecated: define_syslog_variables();
	openlog(preg_replace('_.*/_','',$argv[0]),LOG_PID,LOG_LOCAL0);
	//syslog(LOG_NOTICE,"STARTUP: ".$_SERVER['SCRIPT_FILENAME']);

	// use like this:
	//$amp_conf = parse_conf("/etc/amportal.conf");
	function parse_conf($filename) 
	{
		$file = file($filename);
		foreach ($file as $line) 
		{
				if (preg_match("/^\s*(\S+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) 
				{
					$conf[ $matches[1] ] = $matches[2];
				}
		}
		return $conf;
	}

	function agilog($msg)
	{
/*
		$msg.="\n";
		$file=fopen("/tmp/stgagi.log","a");
		fwrite($file,$msg);
		fclose($file);
*/
		syslog(LOG_NOTICE,$msg);
	}


	function callagi($from,$to,$agi=null,$delay=0)
	{
		if (!$agi)
			$agi=$GLOBALS['agi_request'];

		$tempfile="inbound-".posix_getpid().".call";
		$tempdir="/var/spool/asterisk/tmp";
		$temppath=$tempdir."/".$tempfile;

		$outdir="/var/spool/asterisk/outgoing";
		$outpath=$outdir."/".$tempfile;

		$call=<<<EOF
channel: Local/$to@from-internal
maxretries: 0
waittime: 60
callerid: $from
context: from-internal
application: AGI
data: $agi
EOF;

		file_put_contents($temppath,$call);

		// make it owned by asterisk!
		chown($temppath,"asterisk");

		// adjust call time + delay seconds
		$time=time()+$delay;
		touch($temppath,$time);

		rename($temppath,$outpath);
	}

	function agi_ValidReturnCode($s)
	{
		$code=substr($s,0,3);
		if ($code<"000" || $code>"999")
				return(false);
		$dash=substr($s,3,1);
		if ($dash=="-")
				return(false);
		return(true);
	}
	function agi($command)
	{
		if (!array_key_exists('agi_enhanced',$GLOBALS)) //!$GLOBALS['agi_enhanced'])
		{
			while (!feof(STDIN))
			{
				$line=trim(fgets(STDIN));
				if ($line==='') break;
				$agivar=explode(':',$line);
				if ($agivar[0]!='')
				{
					agilog($agivar[0]."=".trim($agivar[1]));
					$GLOBALS[$agivar[0]]=trim($agivar[1]);
					$GLOBALS['AGI'][$agivar[0]]=trim($agivar[1]);
				}
			}
		}
		if (!$command || $command=="")
			return;

		agilog("CMD: ".$command);
		fwrite(STDOUT,"$command\n");
		fflush(STDOUT);
		$result=fgets(STDIN);

		while (!agi_ValidReturnCode($result))
		{
				agilog("ERROR: ".$result);
				$result=fgets(STDIN);
				if (result=="")
					break;
		}

		agilog("GOT: ".$result);

//		$results=explode(" ",trim("code=".$result));
		$res0=explode(" (",trim("code=".$result));
		$results=explode(" ",$res0[0]);

		if (array_key_exists(1,$res0) && $res0[1])
		{
			$GLOBALS['data']=substr($res0[1],0,strlen($res0[1])-1);
			$GLOBALS['AGI']['data']=substr($res0[1],0,strlen($res0[1])-1);
		}

		foreach ($results as $assignment)
		{
			$pair=explode("=",$assignment);
			if (isset($pair[1]))
			{
				$GLOBALS[$pair[0]]=trim($pair[1]);
				$GLOBALS['AGI'][$pair[0]]=trim($pair[1]);
			}
			else
				agilog("INVALID ASSIGNMENT: ".$assignment);
		}
	}

	// use for both tts output and input prompts
	function agisay($message,$secs=0)
	{
		// there is a limit of 100 characters, so split at opportune points
		$parts=preg_split("/(.*[\.\,\!]) /",$message,-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		$message=trim(array_pop($parts));
		foreach ($parts as $part)
			agisay(trim($part));

//		$file="/tmp/agisay-".md5($message);
		$md5=md5($message);
		$path="/var/lib/asterisk/sounds/agisay/".substr($md5,0,1).substr($md5,-1,1);
		if (!file_exists($path))
			mkdir($path,0775,true);
		$file="$path/$md5";
		$file_ext="{$file}.sln";
		$url="http://translate.google.com/translate_tts?q=".urlencode($message);

		if (!file_exists($file_ext))
			system("mpg123 -r 8000 -mono -s $url >$file_ext");

		if (!filesize($file_ext))
		{
			// file did not get created?
			agilog("AGISAY ERROR zero length file from $url");
			unlink($file_ext);
//			$file="something-terribly-wrong";
			$file="beeperr";
		}

		$timeout=$secs*1000;
		if (!$timeout)
			$timeout=10;

		agi("get data $file $timeout");
		return($GLOBALS['result']);
	}

	function agidie($message)
	{
		agilog("AGIDIE: $message");
		agi('exec NoOp "'.$message.'"'); // put msg in Asterisk's log too
		agi("stream file something-terribly-wrong #");
		agi('verbose "'.$message.'"');
		agi('say alpha "'.$message.'" #');
		exit(0);
	}

?>
