let _throttleTimer = null;
let _throttleDelay = 100;
let _processing = false;

let _nextPageUrl = Node;

function HidePagination() {
    $('#content .row:last').hide();
}

function ScrollHandler(e) {
    //throttle event:
    clearTimeout(_throttleTimer);

    _throttleTimer = setTimeout(function () {

        let lastProductTop = $('.product-layout:last')[0].getBoundingClientRect().top;

        //console.log([$(window).height(), lastProductTop]);

        if ((lastProductTop < $(window).height()) && !_processing) {

            //console.log("Getting next page...");

            getNextPageContent();

        }
    }, _throttleDelay);
}

function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";

    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    } else {
        return uri + separator + key + "=" + value;
    }
}

function getNextPageContent() {
    $.urlParam = function (name, url) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)')
            .exec(url);

        return (results !== null) ? results[1] || 0 : false;
    }

    var nextUrl = _nextPageUrl;
    if (nextUrl) {
        var pageNum = $.urlParam('page', nextUrl);

        nextUrl = updateQueryStringParameter(window.location.href, 'page', pageNum);

        $('.product-layout').parent().addClass("infscrlintro");
        $.ajax({
            url: nextUrl,
            dataType: 'html',
            cache: false,
            beforeSend: function () {
                _processing = true;
            },
            success: function (html) {
                _processing = false;

                var productClass = $('.product-layout').attr('class');

                var div = $('.product-layout', $(html));
                $('.infscrlintro').append(div);

                nextUrl = getNextPageUrlFromPagination($("<div/>").append(html).find('ul.pagination'));

                if (nextUrl) {
                    _nextPageUrl = nextUrl;
                } else {
                    _nextPageUrl = null;

                    $(window).off('scroll', ScrollHandler);
                }

                $('.product-layout').attr('class', productClass);
            }
        });
    } else {
        $(window).off('scroll', ScrollHandler);
    }
}

function getNextPageUrlFromPagination(pagination_elem) {
    let url = null;

    if (pagination_elem) {

        let activeElem = $(pagination_elem).find('.active');

        if (activeElem.length > 0) {
            url = $(activeElem[0].nextSibling).find('a').attr('href');
        }
    }

    return url;
}

$(document).ready(function () {

    _nextPageUrl = getNextPageUrlFromPagination($('ul.pagination'));
    //console.log(_nextPageUrl);

    HidePagination();

    if (_nextPageUrl) {
        $(window).off('scroll', ScrollHandler).on('scroll', ScrollHandler);
    }
});