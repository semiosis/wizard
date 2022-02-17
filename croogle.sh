#!/bin/bash

# Added this:
# x-scheme-handler/croogle=userapp-Firefox-WUWO5X.desktop;
#
# To:
# $HOME/.local/share/applications/mimeapps.list
#
# This way, firefox tries to open and presents you with a dialog on
# what application I want to open it.

#export YCMD=packages/localisation/environmentalLocalisation
#export YCMD3P=environmentalLocalisation
#export VIMBUNDLE=environmentalLocalisation
#
#unminimise() {
#    sed $SEDOPTS -e "s#\$LOCENVLOC/#${LOCENVLOC}/#g" \
#        -e "s#\$ENVLOC/#${ENVLOC}/#g"
#}
#
#url="$(echo $@ | unminimise | sed 's/^croogle:\/\///' | sed 's/%20/ /')"
echo "$@" > /tmp/tmp.txt
url="$(echo "$@" | sed 's/^croogle:\/\///' | sed 's/%20/ /g')"
editor="$(echo "$url" | cut -d '#' -f 1)"
search="$(echo "$url" | cut -d '#' -f 2)"
ppath="$(echo "$url" | cut -d '#' -f 3)"
fullpath="$(locate "$ppath" | head -n 1)"
export DISPLAY=":0"
case "$editor" in
    vim)
        eval "gvim +/\\\c$search \"$fullpath\" -c 'set hls' -c 'normal! <Del>n'"
        break;
        ;;
    vimsh)
        eval "gvim.sh \"+/$search '$fullpath' -c 'set hls' -c 'normal! <Del>n'\""
        break;
        ;;
    eclipse)
        eval "/usr/local/bin/eclipse --launcher.openFile \"$fullpath\"" >/dev/null
        break;
        ;;
    xdg)
        eval "/usr/bin/xdg-open \"$fullpath\"" >/dev/null
        break;
        ;;
    eclipseclient)
        eval "eclipse-client.sh \"$fullpath\"" >/dev/null
        break;
        ;;
    *)
        eval "notify-send \"$editor /$search $fullpath\""
        break
        ;;
esac
