#!/bin/bash

# $0 path word
path="$1"
word="$2"
#cat "$path" | grep -C 2 "$word"
#echo "$path"
#cat "$path" | iconv -f utf-8 -t utf-8 | ack-grep -1 -i "$word"
head -n 500 "$path" | ./uniqnosort.sh | iconv -f utf-8 -t utf-8 | grep -n -i "$word"