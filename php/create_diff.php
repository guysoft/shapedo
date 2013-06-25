<?php
//../php/json.php?file=../examples/objects/cube.stl
//thingiview.loadJSON("../php/json.php?file=../examples/objects/cube.stl");

// server needs a good cpu!  Might need to make timeout higher if server chokes on really really big models...
set_time_limit(3000);

//include('convert.php');
include('config.php');
include('sh_stl_load.php');

function loadSTL($file){
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
	  $result = parse_stl_binary($handle);
	} else {
	  //$result = parse_stl_string($contents);
	  $result = parse_stl_string($contents);
	}  
	break;
      case 'obj':
	$result = parse_obj_string($contents);
	break;
    }


    return $result;
}

function print_stl_from_json($stl){
	/*
	 * Echo out an stl file
	 */

    //header
    echo "solid name\n";

    $vertexes = $stl[0];
    $face_vertexes = $stl[1];

    for ($i = 0; $i < count($face_vertexes); $i++) {
      echo "  facet normal 0 0 0\n";//TODO: we have no normal here
      
      echo "    outer loop\n";
      
      
      for ($j = 0; $j < count($face_vertexes[$i]); $j++) {
      echo "        vertex " . $vertexes[ $face_vertexes[$i][$j] ][0] . " " .
		      $vertexes[ $face_vertexes[$i][$j] ][1] . " " .
		      $vertexes[ $face_vertexes[$i][$j] ][2] . "\n";
      }
      echo "    endloop\n  endfacet\n";
    }

    //footer
    echo "endsolid name\n";
}

function shjson_to_stl($sdjson){
	/*
	 * Echo out an stl file as string
	*/
	$returnValue = "";
	//header
	$returnValue .= "solid name\n";

	foreach ($sdjson as &$face) {
		$returnValue .= "  facet normal 0 0 0\n";//TODO: we have no normal here
		
		$returnValue .= "    outer loop\n";
		
		for ($j = 0; $j < count($face); $j++) {
			$returnValue .= "        vertex " . $face[$j][0] ." " . $face[$j][1] . " " . $face[$j][2] . "\n";
		}
		$returnValue .= "    endloop\n  endfacet\n";
	}

	//footer
	$returnValue .= "endsolid name\n";
	return $returnValue;
}

//TODO:write
function sortVertecies($json_stl){
  $vertexes = $stl[0];
  $face_vertexes = $stl[1];
  function cmp($a, $b)
  {
      if ($a == $b) {
	  	return 0;
      }
      return ($a < $b) ? -1 : 1;
  }

$a = array(3, 2, 5, 6, 1);

usort($a, "cmp");
}


//TODO: HELP!!
function is_ascii($data){
    //Thanks to blender implementation, its all magic numbers
    
    $BINARY_HEADER = 80;
    $BINARY_STRIDE = 12 * 4 + 2;
    $s = unpack('I',$data); //unpack the data into a string - your machine has to be little-endian
    $size = sizeof($s);       // find the length of the data
    
    //echo print_r($s) . "\n";
    echo  $BINARY_HEADER + 4 + $BINARY_STRIDE * $size . "\n";
    return ! count($data)*8 == $BINARY_HEADER + 4 + $BINARY_STRIDE * $size;
}
$boolarray = Array(false => "false\n", true => "true\n");
//echo $boolarray[is_ascii(file_get_contents($fileName1))];

function json2sdjson($a){
	/*
	 *  Take a thingiview json and convert it to a shapedoo json format 
	 */
	$vertexes = $a[0];
	$face_vertexes = $a[1];
	$returnValue = array();
	for($i = 0; $i < count($face_vertexes); $i++){
		$key = getFaceKey($vertexes[$face_vertexes[$i][0]], $vertexes[$face_vertexes[$i][1]], $vertexes[$face_vertexes[$i][2]]);
		$returnValue[$key] = array();
		for($j = 0; $j < count($face_vertexes[$i]); $j++){
			$returnValue[$key][$j] = $vertexes[$face_vertexes[$i][$j]];
		}	
		
	}
	return $returnValue;
}

//TODO: make me be memory efficient
function sdjson_2_json($a){
	/*
	 *  Take a shapedoo json and convert it to a thingiview json format 
	 */
	$returnValue = array();
	$returnValue[0] = array();
	$returnValue[1] = array();
	$verticesCount = 0;
	for($i = 0; $i < count($a); $i++){
		$returnValue[1][$i] = array();
		$faceVerCount = 0;
		for($j = 0; $j < count($a[$i]); $j++){
			
			$returnValue[0][$verticesCount] = $a[$i][$j];
			$returnValue[1][$i][$faceVerCount] = $verticesCount;
			
			$faceVerCount++;
			$verticesCount++;
		}

	}
	return $returnValue;
}

function shjson_diff($a,$b){
	/*
	 * Returns the faces that are in $a but not in $b
	 */
	$diff = array();
	foreach ($a as $key => $value) {
		if ( ! array_key_exists($key,$b) ){
			$diff[$key] = $value;
		}
	}
	return $diff;
}


function isPermitation($a,$b){
	/*
	 * Returns true if list $a is a permitation of $b
	 */
	
	//TODO: handle the fact faces are 9 points
	if ( count($a) != count($b)){
		return false;
	} 
	
	//keep track of how many times each element occurs.
	$counts = array();
	foreach ($a as $keyA => $valueA) {
		$hashA = getPointKey($valueA);
		
		if ( array_key_exists($hashA,$counts) ){
			$counts[$hashA]++;
		}
		else{
			$counts[$hashA]=1;
		}
	}
	
	//if some element in B occurs too many times, not a permutation
	foreach ($b as $keyB => $valueB) {
		$hashB = getPointKey($valueB);
		if ( array_key_exists($hashB,$counts) ){
			if ($counts[$hashB] == 0){
			}
			else{
				$counts[$hashB]--;
			}
		}
		else{
			return false;
		}
	}
	
	return true;
}

function equalizePermitations(&$a,&$b,&$inA,&$inB){
	/*
	 * Go over diffs, if B has a permitation of A, make it equal, but keep key
	 * Takes diff results to make this go faster
	 */
	foreach ($inB as $keyB => $valueB) {
		foreach ($inA as $keyA => $valueA) {
			if ( isPermitation($valueB,$valueA)){
				$b[$keyB] = $valueA;
				unset($inB[$keyB]);
				unset($inA[$keyA]);
			}
		}
	}
}

echo "Running\n";
//$fileName1 = $basePath . "US_Map.stl";
//$fileName2 = $basePath . "US_Map_bin.stl";
//$fileName1 = $basePath . "xclip.stl";
//$fileName2 = $basePath . "xclip_bin.stl";
$fileName1 = "monkey.stl";
$fileName2 = "monkey_bump.stl";

// check that files exists
if (! file_exists($config['stlDir'] . $fileName1)) {
	echo 'File ' . $config['stlDir'] . $fileName1 . ' not exists';
	exit;
}

if (! file_exists($config['stlDir'] . $fileName2)) {
	echo 'File ' . $config['stlDir'] . $fileName2 . ' not exists';
	exit;
}

echo "load1\n";
$result1 = (SDloadSTL($config['stlDir'] . $fileName1));
echo "load2\n";
$result2 = (SDloadSTL($config['stlDir'] . $fileName2));

echo "Make diffs\n";
$inA = shjson_diff($result1,$result2);
$inB = shjson_diff($result2,$result1);

equalizePermitations($result1,$result2,$inA,$inB);

echo "Write fixed\n";
file_put_contents($config['tmpDir'] . $fileName1, shjson_to_stl($result1));
file_put_contents($config['tmpDir'] . $fileName2 , shjson_to_stl($result2));

echo "Write diffs\n";
$exploded1 = explode('.', $fileName1);
$exploded2 = explode('.', $fileName2);
file_put_contents($config['tmpDir'] . $exploded1[0] . '.diff.stl', shjson_to_stl($inA));
file_put_contents($config['tmpDir'] . $exploded2[0] . '.diff.stl', shjson_to_stl($inB));

//print_stl_from_json($result1);
//echo shjson_to_stl($result1);
echo "Done\n";
?>
