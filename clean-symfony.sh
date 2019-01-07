#!/bin/sh
path=$1
[ -n "$path" ] || path=.
rm -rf `find "$path" \( -iname tests -o -iname doc -o -iname docs \) -xtype d`
find "$path" \( -iname LICENSE -o -iname 'README*' -o -iname phpunit.xml.dist -o -iname 'CHANGELOG*' -o -iname '*.md' -o -iname phpbench.json -o -iname phpstan.neon \) -xtype f -delete 
