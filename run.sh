#!/bin/bash

rm /var/cache/lighttpd/compress/css -rf
cd `dirname $0`
cp setnetwork.sh /usr/local/sbin/ -rf
cp *.php /var/www_ok/ -rf

sync
