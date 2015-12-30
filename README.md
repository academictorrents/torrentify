# torrentify

A simple web app that turns a URL into torrent.  Consists of two components:

- `create.php`, an endpoint which initates the PHP download and torrent creation
- `index.html`, a simple web form which creates torrent for download

`query.php` was intended to be an endpoint for querying the status of torrent creation.

Features
--------

- The data is never actually downloaded; only the resulting torrent is saved on the server.
- Automatic piece size optimization.
- URL becomes web seed!

Limitations
-----------

- Only tested using PHP dev server (php -S localhost:8000)
- PHP response is blocked by torrent creation.
- Status is not written until torrent is completed; querying status does not work.
- Ideally torrent creation is separate job, but this requires exec.
