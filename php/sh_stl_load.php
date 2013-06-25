<?php

function getPointKey($p){
	return implode(" ",$p);
}
function getFaceKey($p1,$p2,$p3){
	$key = "";
	$key.=      getPointKey($p1);
	$key.=" " . getPointKey($p2);
	$key.=" " . getPointKey($p3);
	return $key;
}

function sd_parse_stl_string($str) {
	//TODO:make more efficient
  $lines = split("\n", $str);
  
  $vertexes = array();
  $normals  = array();
  $faces    = array();
  
  $face_vertexes = array();

  $normal_count = -1;

  foreach($lines as $line) {
    // TODO: maybe faster if you strip spaces on whole contents instead of every line
    $line = preg_replace('/\s+/', ' ', $line);
    $line = preg_replace('/\s+$/', '', $line);
    // echo $line . "<br/>\n";
    
    // TODO: probably don't need to parse normals anyway
    preg_match('/facet normal (.*) (.*) (.*)$/', $line, $matches);
    if (count($matches) > 1) {
      $normals[] = array((float)$matches[1], (float)$matches[2], (float)$matches[3]);
      $normal_count++;
    } else {
      preg_match('/vertex (.*) (.*) (.*)$/', $line, $matches);
      if (count($matches) > 1) {
        $vertex = array((float)$matches[1], (float)$matches[2], (float)$matches[3]);
        
        $face_vertexes[$normal_count][] = $vertex;
      }
    }
  }
  
  $sd_faces=[];
  foreach($face_vertexes as $face) {
  	$sd_faces[getFaceKey($face[0],$face[1],$face[2])] = $face;
  }
  
  return $sd_faces;
}

function sd_parse_stl_binary($fp) {
	$vertexes = array();
	$faces    = array();

	$face_vertexes = array();

	// $fp = fopen($filename, "rb");
	rewind($fp);

	// skip header
	$data = fread($fp, 80);
	$header = unpack("c*", $data);

	// get number of faces
	$data = fread($fp, 4);
	$count = unpack("i", $data);

	for ($i = 0; $i < $count[1]; $i++) {
		// skip normals
		$data = fread($fp, 12);
		$normal = unpack("fff", $data);

		for ($v_count = 0; $v_count < 3; $v_count++) {
			$points = array();

			for ($v_index = 0; $v_index < 3; $v_index ++) {
				$data = fread($fp, 4);
				$points[] = unpack("f", $data);
			}

			$vertex = array($points[0][1], $points[1][1], $points[2][1]);

			if (!in_array($vertex, $vertexes)) {
				$vertexes[] = $vertex;
			}

			$face_vertexes[$i][] = $vertex;

		}

		$data = fread($fp, 2);
		$attribute = unpack("S", $data);
	}

    $sd_faces=[];
    foreach($face_vertexes as $face) {
    	$sd_faces[getFaceKey($face[0],$face[1],$face[2])] = $face;
    }
  
  return $sd_faces;
}

function SDloadSTL($file){
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

	switch($file_parts['extension']){
		case 'stl':
			if (stripos($contents, 'solid') === FALSE) {
				$result = sd_parse_stl_binary($handle);
			} else {
				//$result = parse_stl_string($contents);
				$result = sd_parse_stl_string($contents);
			}
			break;
		case 'obj':
			$result = parse_obj_string($contents);
			break;
	}


	return $result;
}

?>