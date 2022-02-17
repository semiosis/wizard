#!/bin/bash
content="$(cat)"
fullcontent="$( cat /var/www/croogle/dirsheader.gv; echo "$content";)"
#echo "$fullcontent" | unflatten -l3 | /usr/bin/dot -Tsvg
echo "$fullcontent" | /usr/bin/dot -Tsvg
# Unflatten causes ugly overlapping nodes and doesn't seem to make much.
# I think its settings need to be played around with.
# of an improvement
#echo "$fullcontent"