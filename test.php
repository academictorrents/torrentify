<?php

function bencode(&$d, $tempChar = null){
    if(is_array($d)){
        $ret="l";
        $isDict=!empty($d["isDct"]);
        if($isDict){
            $ret="d";
            // this is required by the specs, and BitTornado actualy chokes on unsorted dictionaries
            ksort($d, SORT_STRING);
        }
        foreach($d as $key=>$value) {
            if($isDict){
                // skip the isDct element, only if it's set by us
                if (!bskip($key, $value, $tempChar)) {
                    $ret .= strlen($key).":$key";
                }
            } elseif (!is_int($key) and !is_float($key) and trim($key, '0..9') !== '') {
                die('Cannot bencode() a list - it contains a non-numeric key "'.$key.'".');
            }

            if (is_string($value)) {
                $ret.=strlen($value).":".$value;
            } elseif (is_int($value) or is_float($value)){
                $ret.="i${value}e";
            } else {
                $ret.=bencode ($value);
            }
        }
        return $ret."e";
    } elseif (is_string($d)) { // fallback if we're given a single bencoded string or int
        return strlen($d).":".$d;
    } elseif (is_int($d) or is_float($d)) {
        return "i${d}e";
    } else {
        return null;
    }
}


function create_torrent($url) {
    $head = array_change_key_case(get_headers($url, TRUE));
    $filesize = $head['content-length'];
    
    // 512 KB is the default maximum piece length
    //
    // From http://wiki.theory.org/BitTorrentSpecification:
    //  "Current best-practice is to keep the piece size to 512KB or less, for
    //   torrents around 8-10GB, even if that results in a larger .torrent file.
    //   This results in a more efficient swarm for sharing files.""
    $piece_length = 524288;
    
    // Keep making the piece length:
    // - smaller until there is >1 piece, or;
    // - larger until there are <1500 pieces
    // From http://wiki.vuze.com/w/Torrent_Piece_Size:
    //  "All in all, a torrent should have around 1000-1500 pieces, to get a
    //   reasonably small torrent file and an efficient client and swarm
    //   download."
    while (ceil($filesize / $piece_length) == 1) {
        $piece_length /= 2;
    }
    while (ceil($filesize / $piece_length) > 1500) {
        $piece_length *= 2;
    }
    
    // Build pieces.
    if (($fp = fopen($url, 'r')) == FALSE) {
        return FALSE;
    }
    $pieces = '';
    $i = 0;
    while (!feof($fp)) {
        $pieces .= pack('H*', sha1(fread($fp, $piece_length)));
    }
    fclose($fp);
    
    // Build the torrent data structure.
    $torrent = array(
        'isDct' => TRUE,
        'announce' => 'http://academictorrents.com/announce.php',
        'url-list' => array($url),
        'info' => array(
            'length'       => $filesize,
            'piece length' => $piece_length,
            'pieces'       => $pieces,
        ),
    );
    
    return bencode($torrent);
}

if (isset($_POST['url'])) {
    $torrent = create_torrent($_POST['url']);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="test.torrent"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    echo $torrent;
    exit;
}
?>

<form action="test.php" method="post">
    enter url
    <input type="text" name="url">
    <input type="submit" value="make torrent" name="submit">
</form>