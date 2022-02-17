#!/bin/bash
path="$(cat | sed 's/\(.*\/[0-9]\+\/\).*/\1/')"
#cat "$path" | sed -n '4s/.*hil-node\(.*\)-autotest.*/\1/p'
# first occurance because it's not always on line 4
#cat "$path" | sed -n '0,/.*hil-node.*-autotest.*/s/.*hil-node\(.*\)-autotest.*/\1/p'
head -n 100 "$path/log.txt" | sed -n '0,/hil-node[^ ]/ s/.*hil-node\([0-9]\+\).*/\1/p'