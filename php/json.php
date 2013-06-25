<?php

// server needs a good cpu!  Might need to make timeout higher if server chokes on really really big models...
set_time_limit(3000);

//http://www.thingiverse.com/download:75151
//$file = file_get_contents('http://www.thingiverse.com/download:75151');
//echo $file;exit;


include('convert.php');

$file = $_GET['file'];

$file_parts = pathinfo($file);

$handle = fopen($file, 'rb');

if ($handle == FALSE) {
  trigger_error("Failed to open file $file");
}

$contents = "";

while (!feof($handle)) {
  $contents .= fgets($handle);
}

$contents = preg_replace('/$\s+.*/', '', $contents);

if (stripos($contents, 'solid') === FALSE) {
	$result = parse_stl_binary($handle);
} else {
	$result = parse_stl_string($contents);
}
/*
switch($file_parts['extension']){
  case 'stl':
    if (stripos($contents, 'solid') === FALSE) {
      $result = parse_stl_binary($handle);
    } else {
    	echo $contents;exit;
      $result = parse_stl_string($contents);
    }  
    break;
  case 'obj':
    $result = parse_obj_string($contents);
    break;
}
*/
echo json_encode($result);

?>