<?php

class Torrent {
  public $metadata;
  public $name;

  function __construct ($url_or_file) {
    $filesize = (int) array_change_key_case(
      get_headers($url_or_file, TRUE))['content-length'];
    $piece_size = $this->get_piece_size($filesize);
    $this->name = end(explode('/', $url_or_file));
    $log = getcwd() . "/log/" . base64_encode($url_or_file);

    # now create the actual torrent
    $this->metadata = array(
        'announce' => 'http://academictorrents.com/announce.php',
        'encoding' => 'UTF-8',
        'info' => array(
            'length'       => $filesize,
            'name'         => $this->name,
            'pieces'       => $this->make_pieces($url_or_file, $piece_size,
                                                 $filesize, $log),
            'piece length' => $piece_size,
        ),
        'url-list' => array($url_or_file),
    );
    $this->save();
  }

  static function bencode($var) {
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
            $ret .= Torrent::bencode($key) . Torrent::bencode($value);
          }
          return $ret . 'e';
        }
      }
      $ret = 'l';
      foreach ($var as $value) {
        $ret .= Torrent::bencode($value);
      }
      return $ret . 'e';
    }
  }

  private function get_piece_size($filesize) {
    $piece_length = 524288;
    while (ceil($filesize / $piece_length) == 1) {
        $piece_length /= 2;
    }
    while (ceil($filesize / $piece_length) > 1500) {
        $piece_length *= 2;
    }
    return $piece_length;
  }

  private function make_pieces($url, $piece_length, $filesize, $logfile) {
    if (($fp = fopen($url, 'r')) == FALSE) {
        return FALSE;
    }
    $pieces = '';
    $part = '';

    $position = 0;
    while ($position < $filesize) {
      $bytes_read = 0;
      # fread doesn't actually read in the correct number of bytes
      # piece together multiple freads
      while ($bytes_read < $piece_length && $position < $filesize) {
        $this_part = fread($fp, min($piece_length, $filesize - $position));
        $bytes_read += strlen($this_part);
        $position += strlen($this_part);
        $part .= $this_part;
      }
      $next_part = substr($part, $piece_length);
      $part = substr($part, 0, $piece_length);
      $piece = sha1($part, $raw_output=TRUE);
      $pieces .= $piece;

      $part = $next_part;

      if ($position > $filesize) {
        $position = $filesize;
      }
      # log progress every 5 pieces
      if ($position == $filesize || $position % (5*$piece_length) == 0) {
        $log = fopen($logfile, "w");
        fwrite($log, $position . '/' . $filesize);
        fclose($log);
      }
    }
    fclose($fp);
    return $pieces;
  }

  private function save() {
    $f = fopen(getcwd() . "/torrents/" . $this->name . ".torrent", "w");
    fwrite($f, Torrent::bencode($this->metadata));
    fclose($f);
  }
}

/*
 create:
 1. Input: GET variables url-encoded string "url".
 2. Creates log for "id" in ./log/ for the download.
 3. Streamingly creates torrent, updating log.
 4. Dumps finished torrent file in ./torrents/
*/

# Main
if (isset($_GET["url"]) && filter_var($_GET["url"], FILTER_VALIDATE_URL)) {
  $log = getcwd() . "/log/" . base64_encode($_GET["url"]);

  # if log exists, don't start new torrent
  if (!file_exists($log))
    $torrent = new Torrent($_GET["url"]);
} else {
  echo(json_encode(array(
    'status' => 'ERROR',
    'msg' => "Requires GET variable 'url'")));
};
?>
