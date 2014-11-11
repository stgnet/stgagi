#!/usr/bin/php 
<?php
	// after 2 secs, will repeat back dtmf sequence keyed

	require 'stgagi.php';

	if (!$_ENV['AST_AGI_DIR'])
	{
		print("error: this is an agi program\n");
		exit(1);
	}

	agi("Answer");
	agi("stream file silence/1 #");
	agi("stream file welcome #");

	// this just says "Dtmf Test Version 2"
	agi("stream file custom/dtmftestv2 #");

	$play="silence/2";
	$digits="";
	while (1)
	{
		extract(agi("stream file ".$play." 1234567890#*"));

		if ($result==0 && $digits!="")
		{
			agi("say alpha ".$digits." #");
			$digits="";
			continue;
		}

		if ($result<=0)
			continue;

		$digits.=chr($result);
	}

	agi("hangup");

?>
