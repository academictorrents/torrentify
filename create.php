<?php

/*
 create:
 1. Takes in GET variable "url", which is url-encoded.
 2. Creates status file in ./log/ for the download, returns to user in JSON.
 3. Streamingly creates torrent, updating status as needed.
 4. When done, dumps torrent file in ./torrents/
*/

// Convenience functions for torrent making
function get_piece_size($filesize) {
  $piece_length = 524288;
  while (ceil($filesize / $piece_length) == 1) {
      $piece_length /= 2;
  }
  while (ceil($filesize / $piece_length) > 1500) {
      $piece_length *= 2;
  }
  return $piece_length;
}

function make_pieces($url, $piece_length, $filesize, $logfile) {
  if (($fp = fopen($url, 'r')) == FALSE) {
      return FALSE;
  }
  $pieces = '';
  $num_pieces = $filesize / $piece_length;
  for ($i=0; $i<=$num_pieces; $i++) {
    $log = fopen($logfile, "w");
    fwrite($log, $i . '/' . (int)$num_pieces);
    fclose($log);
    # if last piece, don't read the whole thing
    if ($i==$num_pieces)
      $pieces .= pack('H*', sha1(fread($fp, $filesize % $piece_length)));
    else
      $pieces .= pack('H*', sha1(fread($fp, $piece_length)));
  }
  fclose($fp);
  return $pieces;
}

function bencode($var) {
  if (is_int($var)) {
    return 'i' . $var . 'e';
  } else if (is_string($var)) {
    return strlen($var) . ':' . $var;
  } else if (is_array($var)) {
    # must distinguish between dict and list
    for ($i = 0; $i < count($var); $i++) {
      # if dict, cannot index using ints?
      if (!isset($var[$i])) {
        $dictionary = $var;
        ksort($dictionary);
        $ret = 'd';
        foreach ($dictionary as $key => $value) {
          $ret .= bencode($key) . bencode($value);
        }
        return $ret . 'e';
      }
    }
    $ret = 'l';
    foreach ($var as $value) {
      $ret .= bencode($value);
    }
    return $ret . 'e';
  }
  # should throw some kind of error here
  #throw new InvalidArgumentException('Type ' . gettype($var) . ' can not be encoded.');
}

# Main

# this validates the URL
$url = urldecode($_GET["url"]);
if (isset($_GET["url"]) && filter_var($url, FILTER_VALIDATE_URL)) {
  $length = (int) array_change_key_case(
    get_headers($url, TRUE))['content-length'];
  $piece_size = get_piece_size($length);
  $name = end(explode('/', $url));
  $log = tempnam(getcwd() . "/log", "stat");

  # stuff to close connection early
  ignore_user_abort(true); // just to be safe
  ob_start();

  # return the log ID, for querying later
  echo(json_encode(array(
    'status' => 'OK',
    'id' => end(explode('/', $log)))));

  $contents = ob_get_contents();
  $len = strlen($contents);
  header("Connection: close");
  header("Content-Length: $len");
  echo $contents;
  flush();

  # now create the actual torrent
  $torrent = array(
      'announce' => 'http://academictorrents.com/announce.php',
      'encoding' => 'UTF-8',
      'info' => array(
          'length'       => $length,
          'name'         => $name,
          'pieces'       => make_pieces($url, $piece_size, $length, $log),
          'piece length' => $piece_size,
      ),
      'url-list' => array($url),
  );
  $torrent = bencode($torrent);

  # done creating, write the torrent file
  $f = fopen(getcwd() . "/torrents/" . $name . ".torrent", "w");
  fwrite($f, $torrent);
  fclose($f);

/*
  # make the download
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . $name . '.torrent"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  echo $torrent;
  exit;*/
} else {
  echo(json_encode(array(
    'status' => 'ERROR',
    'msg' => "Type in a URL!")));
};
?>
