#!/bin/bash

indices=(
    "home"
    "system"
    "libraries"
)

# indices=(
#     "packages"
#     "logs"
#     "jenkins"
#     "rtm"
#     "thirdparty"
#     "shane"
#     )

count=0
while [ "x${indices[count]}" != "x" ]; do
    cat /var/www/croogle/${indices[count]}-freqs.txt | sed -n '/ / s/^\([[:alnum:]_-.]\{4,\}\) .*$/\1/p' | sed '/^[0-9a-fA-F]\+$/d' | sed '/^0x.\+$/d' > /var/www/croogle/${indices[count]}-suggestions.txt
    count=$(( $count + 1 ))
done