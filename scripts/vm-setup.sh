#!/bin/sh

DEBIAN_FRONTEND=noninteractive apt-get -y update
DEBIAN_FRONTEND=noninteractive apt-get -y upgrade
DEBIAN_FRONTEND=noninteractive apt-get -y install php5-cli php5-memcache php5-mysql php-pear memcached mysql-server-5.1

/usr/bin/pear upgrade PEAR
/usr/bin/pear channel-discover pear.phpunit.de
/usr/bin/pear config-set auto_discover 1
/usr/bin/pear install --alldeps phpunit/phpunit
