# torrentify

A simple web app that turns a URL into torrent.  Consists of two endpoints:

- `create.php`, an endpoint which initates the PHP download and torrent creation
- `query.php`, an endpoint for querying the status of a torrent being created.

Also included is a front-end interface:

- `index.html`, a simple web form which creates torrent for download

Features
--------

- Torrent is created from HTTP stream; server does not store data set.
- Automatic piece size optimization.
- URL becomes web seed!
