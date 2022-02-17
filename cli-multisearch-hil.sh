#!/bin/bash
# /media/www/html/croogle/q.txt
#curl "http://cyclops/html/croogle/cli-search.php?s=;pack;rtm"
#curl "http://cyclops/html/croogle/cli-search.php?s="
queries="$(cat)"
echo "$queries" | while read q; do
    s="$(urlencode "$q")"
    #s="$(urlencode "ERROR: test_ArchiveLogs (tests.continuous.test_5_input_logging.eblInputLoggingTest)")"
    #curl "http://cyclops/html/croogle/cli-search.php?s=;jenkins;auto;$s"
    #curl "http://cyclops/html/croogle/cli-search.php?s=$s" 2>/dev/null
    #echo "http://cyclops/html/croogle/?s=$s"

    curl "http://croogle/cli-search-hil.php?s=$s" 2>/dev/null
    echo
done