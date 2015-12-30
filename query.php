<?php

/*
 query:
 1. Takes in GET variable "id".
 2. Reads status of torrent pertaining to "id".
 3. Returns it
*/

if (isset($_GET["id"])) {
  # prevent ../ attacks
  $id = end(explode('/', $_GET["id"]));
  $path = getcwd() . "/log/" . $id;
  if ( file_exists($path) && ($log = fopen($path, "r"))!==false ) {
    $str = stream_get_contents($log);
    fclose($log);
    echo(json_encode(array(
      'status' => 'OK',
      'progress' => $str)));
  } else {
    echo(json_encode(array(
      'status' => 'ERROR',
      'msg' => "Bad 'id'.")));
  }
} else {
  echo(json_encode(array(
    'status' => 'ERROR',
    'msg' => "Must supply GET variable 'id'.")));
};
?>
