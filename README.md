# TVGuidend
An application to show the epg data of TVHeadend in a VDRadmin like manner

This application is heavily based on the layout created in TVHadmin (https://github.com/dave-p/TVHadmin) which is based on VDRAdmin (http://andreas.vdr-developer.org/vdradmin-am/index.html)

## Requirements:
- TVHeadend, preferably a recent version (4.2+)
- A webserver with PHP 7.2+

## Installation
- Clone the repo on a webserver running PHP
- Or: run `docker-compose up`
- Add your ip/port/user/password to `.env.local` (copy from `.env`)
- Run `composer install` on your webserver or in the docker container
- Access TVGuidend on your webserver, or through docker (for example http://localhost:8087)
