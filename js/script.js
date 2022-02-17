//why was i even using _.object(_.map( ??
//var some_map = _.object(_.map(some_object_array, function(item) {
//   return [item.id, item]
//}));

//var some_map = mapo(some_object_array, function(item) {
//   return [item.id, item]
//});

function searchselect() {
    //newquery('');
    $('a#permalink > div > span').html('C R O O G L E');
    $('input#superbar').focus();
    $('input#superbar').select();
}

/* this enables jquery-ui tooltips */
/* just need to add title attribute to elements */

// far too distracting

$(function() {
    $(document).tooltip();
});

$(document).ready(function() {
    $.contextMenu({
        selector: ".folder-menu",
        items: {
            bar: {name: "open"                , callback: function(key , opt){ alert("Opening") }},
            foo: {name: "Search within files" , callback: function(key , opt){ alert("Searching"); }},
            baz: {name: "Stash neww"          , callback: function(key , opt){ window.open('http://stash.rtc.crownlift.net/projects/PROJECTS/repos/core/browse/tests/MakeUniqueTest.h'); }},
            qux: {name: "vim"                 , callback: function(key , opt){ window.location.href = 'croogle://vimsh#m#/slam/projectionFactorGeneration/dataAssociation/ComputeLaserJcbbFromGraph.m'; }}
        }
        // there's more, have a look at the demos and docs...
    });

    // this has to happen after underscore has loaded
    // mapping over objects is so useful that I always define
    mapo = _.compose(_.object, _.map);

    // do not want mouseup because can't select
    //$('#q').live('mouseup', function() { $(this).select(); });
    $('form#search').submit(function() {
        $.ajax('search.php', {
            data: $(this).serialize(), // get the form data
            type: $(this).attr('GET'),
            success: function(response) {
                $('#results').html(
                    response);
                $('p.pages').fadeTo(100,
                    0.8,
                    function() {
                        $('p.pages').fadeTo(
                            200,
                            1.0);
                    });
                $('ol.results li').fadeTo(
                    500, 1.0,
                    function() {
                        //$("#q").focus();
                    });

                //$(jqid).live('focus', function() { $(this).select(); });
                //$(jqid).live('mouseup', function() { $(this).select(); });
            }
        });

        return false; // cancel original event to prevent the form submitting
    });
    //p = $('#pagenum').val()
    //if (!(p == '')) {
    //    changepage(p);
    //} else if (!($('#q').val() == '')) {
    //    changequery();
    //}
    // the following is for IE. But IE doesn't work anyway.
    $("#superbar").bind($.browser.msie ? 'propertychange' : 'change',
        function(e) {
            changequery();
        });

    $("#superbar").focus(function() {
        $(this).data("hasfocus", true);
        $(this).fadeTo(200, 1.0);
    });

    $("#superbar").blur(function() {
        $(this).data("hasfocus", false);
        $(this).fadeTo(200, 0.5);
    });

    //$("#indextype").change(function() {
    //    changequery("fullsearch");
    //});

    $(document.body).keyup(function(ev) {
        if (ev.which === 83) {                           /// s
            $('input#superbar').focus();
        } else if (ev.which === 13) {                    /// enter
            if ($("#superbar").data("hasfocus")) {
                $("ol.results li").fadeTo(10, 0.2);
                changequery("fullsearch");
                $("#superbar").blur();
            }
        } else if (ev.which === 87 || ev.which === 80) { /// w
            // 87 is W
            if ($("#superbar").data("hasfocus")) {
                true;
            } else if ($("#cls").data("hasfocus")) {
                // Maybe this doesn't work because
                // 'select' isn't an
                // input box. Use a different key.
                true;
                //$('input#superbar').focus();
                //$('input#superbar').select();
            } else {
                var qpath = $('#qpath').val();
                var q = $('#q').val();
                $('#qpath').val(q);
                $('#q').val(qpath);
                $("ol.results li").fadeTo(10, 0.2);
                setsuperbar();
                changequery("fullsearch");
                $("#superbar").blur();
            }
        }
    });
    $('input#superbar').focus();
    $('input#superbar').select();

    if ($('#superbar').val() == '') {
        $("#superbar").focus();
    } else {
        $("#superbar").blur();
        $("#superbar").data("hasfocus", false);
    }
});

function copyToClipboard(text) {
    window.prompt("Copy to clipboard: Ctrl+C, Enter", text);
}

crooglesign =
    "<div class='rainbow'><span class='rainbow'>http://croogle/";

function changeindex(indextypepara) {
    getfromsuperbar();
    window.scrollTo(0, 0);
    $("ol.results li").fadeTo(80, 0.7);
    $('#superbar').val($('#superbar').val().replace(/^;[a-z]+/, ';' + indextypepara));
    $('#indextype').val(indextypepara);
    setsuperbar();
    changequery("fullsearch");
}

// Encode/decode htmlentities
function krEncodeEntities(s){
	return $("<div/>").text(s).html();
}
function krDencodeEntities(s){
	return $("<div/>").html(s).text();
}

function setsuperbar() {
    var indext = $('#indextype').val();
    var q      = $('#q').val();
    var qpath  = $('#qpath').val();
    if (! (indext ||  qpath)) {
        $('#superbar').val(q);
    } else {
        $('#superbar').val(";" + indext + ";" + qpath + ";" + q);
    }
}

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function completesuperbar() {
    var indices = [ 'packages', 'rtm', 'logs', 'jenkins', 'shane' ];

    // $NOTES/ws/javascript-js/capture-group.js

    var barstring = $('#superbar').val();

    var indexregex = /^;([^;])+$/;
    var matches = indexregex.exec(barstring);

    if (matches && matches[0]) {
        var nummatching = _.reduce(indices, function(memo, index) {
            if (index.indexOf(matches[0]) >= 0) {
                return memo + 1;
            }
            return memo;
        });

        console.log(_.map(indices, function(item) {
            return item + 'hi-' + nummatching;
        }));

        // string.indexOf(substring) > -1

        //// if ;r then complete to ;rtm
        //var barstring = $('#superbar').val();
    }
}

function getfromsuperbar() {
    var barstring = $('#superbar').val();
    var arr = barstring.split(';');
    var indext = $('#indextype').val();
    var q = $('#q').val();
    var qpath = $('#qpath').val();
    if (arr[0] != '') {
        q = barstring;
        $('#q').val(q);
        return;
    }
    arr.shift();
    if (arr.length == 1) {
        indext = arr[0];
        qpath = '';
        q = '';
    }
    if (arr.length == 2) {
        indext = arr[0];
        qpath = arr[1];
        q = '';
    }
    if (arr.length >= 3) {
        indext = arr[0];
        qpath = arr[1];
        arr.shift();
        arr.shift();
        q = arr.join(';');
    }
    $('#indextype').val(indext);
    $('#q').val(q);
    $('#qpath').val(qpath); // this is correct
}

function newquery(q) {
    //getfromsuperbar();
    window.scrollTo(0, 0);
    var indext = $('#indextype').val();
    var qpath = $('#qpath').val();
    $("ol.results li").fadeTo(80, 0.7);
    $('#q').val(q);
    $('#qpath').val("");
    $('#pagenum').val("");
    $('#qtype').val("");
    //$('#search').trigger('submit');
    $('#search').submit();

    var url;
    if (q == '') {
        url = "?";
    } else {
        url = "?q=" + q;
    }

    var indexp = '';
    if (indext != '') {
        if (url != '') {
            indexp = "&index=" + indext;
        } else {
            indexp = "?index=" + indext;
        }
    }

    var qpathp = '';
    if (qpath != '') {
        if (url != '') {
            qpathp = "&qpath=" + qpath;
        } else {
            qpathp = "?qpath=" + qpath;
        }
    }

    url = url + indexp;
    url = url + qpathp;
    //url = krEncodeEntities(url);
    //url = url.replace('#', '&#35;'); // this is to stop # from breaking the url
    url = url.replace('#', ' '); // this is to stop # from breaking the url

    $('#permalink').attr("href", url);
    $('#permalink').attr("alt", q);
    $('#permalink').html(crooglesign + url + "</span></div>");
    setsuperbar();
}

function newpathquery(q) {
    //getfromsuperbar();
    window.scrollTo(0, 0);
    var indext = $('#indextype').val();
    var qpath = $('#qpath').val(q);
    $("ol.results li").fadeTo(80, 0.7);
    $('#q').val("");
    var q = "";
    $('#pagenum').val("");
    $('#qtype').val("");
    //$('#search').trigger('submit');
    $('#search').submit();

    var url;
    if (q == '') {
        url = "?";
    } else {
        url = "?q=" + q;
    }

    var indexp = '';
    if (indext != '') {
        if (url != '') {
            indexp = "&index=" + indext;
        } else {
            indexp = "?index=" + indext;
        }
    }

    var qpathp = '';
    if (qpath != '') {
        if (url != '') {
            qpathp = "&qpath=" + qpath;
        } else {
            qpathp = "?qpath=" + qpath;
        }
    }

    url = url + indexp;
    url = url + qpathp;
    //url = krEncodeEntities(url);
    //url = url.replace('#', '&#35;'); // this is to stop # from breaking the url
    url = url.replace('#', ' '); // this is to stop # from breaking the url

    $('#permalink').attr("href", url);
    $('#permalink').attr("alt", q);
    $('#permalink').html(crooglesign + url + "</span></div>");
    setsuperbar();
}

function togglemaxresults() {
    /*$("ol.results li").fadeTo(200, 0.05);*/
    //if ($("ol.results > div#maxresults").data("maxresultson") == "true") {
    //    $("ol.results > div#maxresults").data("maxresultson", "false");
    //    $("ol.results > div#maxresults").css("border", "2px outset #cdf");
    //    $("#pagesizenum").val("8");
    //    changequery("fullsearch");
    //} else {
     //   $("ol.results > div#maxresults").data("maxresultson", "true");
     $("ol.results li").fadeTo(80, 0.5);
     $("ol.results > div#maxresults").css("border", "2px inset #0f0");
     changequery("maxsearch");
     //sickunfaderesults();
    //}
    /*$("ol.results > div#sick").fadeTo(200, 0.05);*/
}

function togglegraphonly() {
    /*$("ol.results li").fadeTo(200, 0.05);*/
    if ($("ol.results > div#sick").data("graphonly") == "true") {
        $("ol.results > div#sick").data("graphonly", "false");
        $("ol.results > div#sick").css("border", "2px outset #fdc");
        $("ol.results svg").css("width", "auto");
        $("ol.results svg").css("top", "auto");
        sickunfaderesults();
    } else {
        $("ol.results > div#sick").data("graphonly", "true");
        $("ol.results > div#sick").css("border", "2px inset #f00");
        $("ol.results svg").css("width", "100%");
        $("ol.results svg").css("top", "0");
    }
    /*$("ol.results > div#sick").fadeTo(200, 0.05);*/
}

function sickfaderesults() {
    if ($("ol.results > div#sick").data("graphonly") != "true") {
        $("ol.results li").fadeTo(200, 0.05);
    }
}

function sickunfaderesults() {
    if ($("ol.results > div#sick").data("graphonly") != "true") {
        $("ol.results li").fadeTo(200, 1);
    }
}

function resultsfaderesults() {
}

function resultsunfaderesults() {
    /*
    if ($("ol.results > div#sick").data("graphonly") == "true") {
        $("ol.results > div#sick").data("graphonly", "false");
        $("ol.results > div#sick").css("border", "2px outset #fdc");
        $("ol.results svg").css("width", "auto");
        $("ol.results svg").css("top", "auto");
        $("ol.results li").fadeTo(200, 1);
    }
    */
    /*
    if ($("ol.results > div#sick").data("graphonly") == "true") {
        $("ol.results li").fadeTo(200, 0.2);
    }
    */
}

function changequery(flags) {
    completesuperbar()
    getfromsuperbar();
    window.scrollTo(0, 0);
    var indext = $('#indextype').val();
    var qpath = $('#qpath').val();
    q = $('#q').val();
    $('#pagenum').val("");
    if (flags == 'fullsearch') {
        $('#qtype').val("");
        $("#pagesizenum").val("8");
    } else if (flags == 'maxsearch') {
        $('#qtype').val("");
        $("#pagesizenum").val("1000");
    } else {
        $('#qtype').val("change");
        $("#pagesizenum").val("8");
    }
    //$('#search').trigger('submit');
    $('#search').submit();

    var url;
    if (q == '') {
        url = "?";
    } else {
        url = "?q=" + q;
    }

    var indexp = '';
    if (indext != '') {
        if (url != '') {
            indexp = "&index=" + indext;
        } else {
            indexp = "?index=" + indext;
        }
    }

    var qpathp = '';
    if (qpath != '') {
        if (url != '') {
            qpathp = "&qpath=" + qpath;
        } else {
            qpathp = "?qpath=" + qpath;
        }
    }

    url = url + indexp;
    url = url + qpathp;
    //url = krEncodeEntities(url);
    //url = url.replace('#', '&#35;'); // this is to stop # from breaking the url
    url = url.replace('#', ' '); // this is to stop # from breaking the url

    $('#permalink').attr("href", url);
    $('#permalink').attr("alt", q);
    $('#permalink').html(crooglesign + url + "</span></div>");
}

function changepage(page) {
    //getfromsuperbar();
    window.scrollTo(0, 0);
    var indext = $('#indextype').val();
    $('#pagenum').val(page);
    $('#qtype').val("");
    var qpath = $('#qpath').val();
    //$('#search').trigger('submit');
    $('#search').submit();
    q = $('#q').val();
    var url;
    if (q == '') {
        url = "?";
    } else {
        url = "?q=" + q + "&page=" + page;
    }
    var indexp = '';
    if (indext != '') {
        if (url != '') {
            indexp = "&index=" + indext;
        } else {
            indexp = "?index=" + indext;
        }
    }

    var indexp = '';
    if (indext != '') {
        if (url != '') {
            indexp = "&index=" + indext;
        } else {
            indexp = "?index=" + indext;
        }
    }

    url = url + indexp;
    url = url + qpathp;
    //url = krEncodeEntities(url);
    //url = url.replace('#', '&#35;'); // this is to stop # from breaking the url
    url = url.replace('#', ' '); // this is to stop # from breaking the url

    $('#permalink').attr("href", url);
    $('#permalink').attr("alt", q);
    $('#permalink').html(crooglesign + url + "</span></div>");
    //setsuperbar();
}

function goToUrl(from, to) {
    window.open('geturl.php?from=' + from + '&to=' + to, '_blank');
}

function processAjaxData(response, urlPath) {
    document.getElementById("content").innerHTML = response.html;
    document.title = response.pageTitle;
    window.history.pushState({
        "html": response.html,
        "pageTitle": response.pageTitle
    }, "", urlPath);
}

// http://stackoverflow.com/questions/9975707/use-jquery-select-to-select-contents-of-a-div
jQuery.fn.selectText = function() {
    this.find('input').each(function() {
        if ($(this).prev().length == 0 || !$(this).prev()
            .hasClass('p_copy')) {
            $(
                '<p class="p_copy" style="position: absolute; z-index: -1;"></p>'
            ).insertBefore($(this));
        }
        $(this).prev().html($(this).val());
    });
    var doc = document;
    var element = this[0];
    console.log(this, element);
    if (doc.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};
