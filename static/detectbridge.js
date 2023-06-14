// Ajax Help function
function doAjaxRequest(type, url, callback, callbackFailure) {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == XMLHttpRequest.DONE && xmlhttp.status == 200) {
            var data = xmlhttp.responseText;
            if (callback) callback(data);
        }
        else if (xmlhttp.readyState == XMLHttpRequest.DONE && xmlhttp.status != 200)
        {
            var data = xmlhttp.responseText;
            if (callbackFailure) callbackFailure(data);
        }
    };
    xmlhttp.open(type, url, true);
    xmlhttp.send();
}

// Start the Feed detection
function detectBridge(event)
{
    const input = document.getElementById('searchfield');
    let content = input.value;
    if(content)
    {
        const detectresults = document.getElementById('detectresults');
        detectresults.innerHTML = 'Searching for matching Feed';
        let url = window.location.href + '?action=detect&format=Html&output=json&url=' + content;
        doAjaxRequest('GET', url, displayDetectedFeed, displayDetectionFail);
    }
    else
    {
        displayDetectionEmpty();
    }
}

// Display the detected Feed
function displayDetectedFeed(data)
{
    const obj = JSON.parse(data);
    const detectresults = document.getElementById('detectresults');
    detectresults.innerHTML = 'Found a Feed : <a href="' + window.location.href + obj.url + '">Bridge : ' + obj.bridgeParams.bridge + ' - Context : ' + obj.bridgeParams.context + '</a> <div class="alert alert-info" role="alert">This feed may be only one of the possible feeds. You may find more feeds using one of the bridges with different parameters for example.</div>';
}

// Display an error if no feed were found
function displayDetectionFail(data)
{
    const detectresults = document.getElementById('detectresults');
    detectresults.innerHTML = 'No Feed found !<div class="alert alert-info" role="alert">Every Bridge does not support feed detection, you can check within the bridge below to create a Feed using the bridge parameters</div>';
}

// Empty the detected Feed section
function displayDetectionEmpty()
{
    const detectresults = document.getElementById('detectresults');
    detectresults.innerHTML = '';
}


// Add Event to 'Detect Feed" button
function addDetectionEvent()
{
    const button = document.getElementById('detectfeed');
    button.addEventListener("click", detectBridge);
    button.addEventListener("keyup", detectBridge);

}

// Wait the page to be loaded to add the Detection Event
document.addEventListener('DOMContentLoaded', addDetectionEvent);
