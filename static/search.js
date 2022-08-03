function rssbridge_list_search() {
    function remove_www_from_url(url) {
        if (url.hostname.indexOf('www.') === 0) {
            url.hostname = url.hostname.substr(4);
        }
    }

    var search = document.getElementById('searchfield').value;
    var searchAsUrl = document.createElement('a');
    searchAsUrl.href = search;
    remove_www_from_url(searchAsUrl);
    var bridgeCards = document.querySelectorAll('section.bridge-card');
    for (var i = 0; i < bridgeCards.length; i++) {
        var bridgeName = bridgeCards[i].getAttribute('data-ref');
        var bridgeUrl = bridgeCards[i].getElementsByTagName('a')[0];
        remove_www_from_url(bridgeUrl);
        bridgeCards[i].style.display = 'none';
        if (!bridgeName || !bridgeUrl) {
            continue;
        }
        var searchRegex = new RegExp(search, 'i');
        if (bridgeName.match(searchRegex)) {
            bridgeCards[i].style.display = 'block';
        }
        if (bridgeUrl.toString().match(searchRegex)) {
            bridgeCards[i].style.display = 'block';
        }
        if (bridgeUrl.hostname === searchAsUrl.hostname) {
            bridgeCards[i].style.display = 'block';
        }
    }
}
