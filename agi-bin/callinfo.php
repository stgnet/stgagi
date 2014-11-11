#!/usr/bin/php 
<?php
	require 'DB.php';

	require 'stgagi.php';

	if (!$_ENV['AST_AGI_DIR'])
	{
		print("error: this is an agi program\n");
		exit(1);
	}

	agi("Answer");
	agi("stream file silence/1 #");
	agi("stream file welcome #");



/*
agi_request=callinfo.php
agi_channel=SIP/config-b6b4b798
agi_language=en
agi_type=SIP
agi_uniqueid=1244569790.190253
agi_callerid=3172232864
agi_calleridname=3172232864
agi_callingpres=0
agi_callingani2=0
agi_callington=0
agi_callingtns=0
agi_dnid=5550123
agi_rdnis=unknown
agi_context=from-customer-local
agi_extension=5550123
agi_priority=1
agi_enhanced=0.0
agi_accountcode=
*/

	agi("say alpha ".$agi_callerid." #");
	agi("stream file calls #");

//	agi("stream file silence/1 #");
//	agi("say alpha ".$agi_calleridname." #");
//	agi("stream file silence/1 #");

	agi("say alpha ".$agi_dnid." #");

	agi("stream file silence/1 #");
	agi("say alpha ".$agi_channel." #");
	agi("stream file silence/1 #");
	agi("hangup");

?>
