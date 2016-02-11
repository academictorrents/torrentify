<?php

/*
 query:
 1. Takes in GET variable "url".
 2. Reads status of torrent pertaining to "url".
 3. Returns it
*/
function safe_b64encode($string) {
  $data = base64_encode($string);
  $data = str_replace(array('+','/','='),array('-','_',''),$data);
  return $data;
}

if (isset($_GET["url"])) {
  # prevent ../ attacks
  $url = end(explode('/', $_GET["url"]));
  $path = getcwd() . "/log/" . safe_b64encode($_GET["url"]);
  if ( file_exists($path) && ($log = fopen($path, "r"))!==false ) {
    $str = stream_get_contents($log);
    fclose($log);
    echo(json_encode(array(
      'status' => 'OK',
      'progress' => $str)));
  } else {
    echo(json_encode(array(
      'status' => 'ERROR',
      'msg' => "No torrent for 'url' exists.")));
  }
} else {
  echo(json_encode(array(
    'status' => 'ERROR',
    'msg' => "Must supply GET variable 'url'.")));
};
?>
