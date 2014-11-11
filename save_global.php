<?php

	function save_global_tree($name,$var)
	{
		if (!is_array($var))
			return($name." = '".$var."'; // ".gettype($var)."\n");
		foreach ($var as $key => $value)
			$output.=save_global_tree($name.'[\''.$key.'\']',$var[$key]);
		return($output);
	}
	function save_global($vars,$file)
	{
		$output='<'.'?'."php\n";
		foreach (explode(",",$vars) as $var)
			$output.=save_global_tree('$TEST[\''.$var.'\']',$GLOBALS[$var]);
		$output.='?'.'>'."\n";
		file_put_contents($file,$output);
	}

	save_global('_SERVER,argv','/dev/stdout');
?>
