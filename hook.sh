#!/bin/bash

path=$(cd `dirname $0`; pwd)

/usr/local/php/bin/php $path/hook.php $CERTBOT_DOMAIN $1 $CERTBOT_VALIDATION
