#!/usr/bin/php 
<?php
	// run ./vmtest.php *EXT and it will leave a random length voicemail message
	require 'stgagi.php';

	if (!$_SERVER['AST_AGI_DIR'])
	{
		if (empty($_SERVER['argv'][1])) {
			die('use: vmtest (extension)'."\n");
		}
		callagi('vmtest '.date('h:i a'),$_SERVER['argv'][1],'vmtest.php');
		exit(0);
	}

	agi("Answer");
	agi("stream file silence/7 #");
	agi("stream file hello #");
	agi("say time ".time()." #");

	$count=rand(1,30);
	while ($count)
	{
		agi("stream file silence/1 #");
		agi("say number $count #");
		$count--;
	}
	agi("hangup");

