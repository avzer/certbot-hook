#!/bin/bash

path=$(cd `dirname $0`; pwd)
cmd="/usr/bin/certbot"
hook="$path/hook.sh"

$cmd renew --preferred-challenges dns --manual-auth-hook "$hook add" --manual-cleanup-hook "$hook delete"
