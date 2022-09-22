#!/bin/bash

path=$(cd `dirname $0`; pwd)
cd $path
hook="$path/hook.sh"
cmd="/usr/bin/certbot"

$cmd certonly \
  -d domain.com -d *.domain.com \
  -d domain2.com -d *.domain2.com \
  --manual -m xxxxxxxx@domain.com \
  --preferred-challenges dns \
  --manual-auth-hook "$hook add" \
  --manual-cleanup-hook "$hook delete" \
  --server https://acme-v02.api.letsencrypt.org/directory