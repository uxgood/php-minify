#!/bin/sh
path=$1
[ -n "$path" ] || path=.

HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX $path/var
setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX $path/var
