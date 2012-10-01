#!/bin/sh

MEMCACHE_VERSION=2.2.6
OPTS="--quiet"

install_composer() {
    wget http://getcomposer.org/composer.phar && php composer.phar install
}

install_ext_memcache() {
    wget "http://pecl.php.net/get/memcache-${MEMCACHE_VERSION}.tgz"
    tar -xzf "memcache-${MEMCACHE_VERSION}.tgz"
    sh -c "cd memcache-${MEMCACHE_VERSION} && phpize && ./configure --enable-memcache --disable-memcache-session $OPTS && make $OPTS && sudo make install"
    echo "extension=memcache.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
}

install_composer
install_ext_memcache
