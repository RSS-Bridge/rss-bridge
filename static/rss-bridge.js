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
        var bridgeShortName = bridgeCards[i].getAttribute('data-short-name');
        var bridgeDescription = bridgeCards[i].querySelector('.description');
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
        if (bridgeShortName.match(searchRegex)) {
            bridgeCards[i].style.display = 'block';
        }
        if (bridgeDescription.textContent.match(searchRegex)) {
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

function rssbridge_toggle_bridge(){
    var fragment = window.location.hash.substr(1);
    var bridge = document.getElementById(fragment);

    if(bridge !== null) {
        bridge.getElementsByClassName('showmore-box')[0].checked = true;
    }
}

var rssbridge_feed_finder = (function() {
    /*
     * Code for "Find feed by URL" feature
     */

    // Start the Feed search
    async function rssbridge_feed_search(event) {
        const input = document.getElementById('searchfield');
        let content = encodeURIComponent(input.value);
        if (content) {
            const findfeedresults = document.getElementById('findfeedresults');
            findfeedresults.innerHTML = 'Searching for matching feeds ...';
            let baseurl = window.location.protocol + window.location.pathname;
            let url = baseurl + '?action=findfeed&format=Html&url=' + content;
            const response = await fetch(url);
            if (response.ok) {
                const data = await response.json();
                rss_bridge_feed_display_found_feed(data);
            } else {
                rss_bridge_feed_display_feed_search_fail();
            }
        } else {
            rss_bridge_feed_display_find_feed_empty();
        }
    }

    // Display the found feeds
    function rss_bridge_feed_display_found_feed(obj) {
        const findfeedresults = document.getElementById('findfeedresults');

        let content = 'Found Feed(s) :';

        // Let's go throug every Feed found
        for (const element of obj) {
            content += `<div class="search-result">
                        <div class="icon">
                            <img src="${element.bridgeMeta.icon}" width="60" />
                        </div>
                        <div class="content">
                        <h2><a href="${element.url}">${element.bridgeMeta.name}</a></h2>
                        <p>
                        <span class="description"><a href="${element.url}">${element.bridgeMeta.description}</a></span>
                        </p>
                        <div>
                            <ul>`;

            // Now display every Feed parameter
            for (const param in element.bridgeData) {
                content += `<li>${element.bridgeData[param].name} : ${element.bridgeData[param].value}</li>`;
            }
            content += `</div>
              </div>
            </div>`;
        }
        content += '<p><div class="alert alert-info" role="alert">This feed may be only one of the possible feeds. You may find more feeds using one of the bridges with different parameters, for example.</div></p>';
        findfeedresults.innerHTML = content;
    }

    // Display an error if no feed were found
    function rss_bridge_feed_display_feed_search_fail() {
        const findfeedresults = document.getElementById('findfeedresults');
        findfeedresults.innerHTML = 'No Feed found !<div class="alert alert-info" role="alert">Not every bridge supports feed detection. You can check below within the bridge parameters to create a feed.</div>';
    }

    // Empty the Found Feed section
    function rss_bridge_feed_display_find_feed_empty() {
        const findfeedresults = document.getElementById('findfeedresults');
        findfeedresults.innerHTML = '';
    }

    // Add Event to 'Detect Feed" button
    var rssbridge_feed_finder = function() {
        const button = document.getElementById('findfeed');
        button.addEventListener("click", rssbridge_feed_search);
        button.addEventListener("keyup", rssbridge_feed_search);
    };
    return rssbridge_feed_finder;
}());
