// Start the Feed search
async function feedSearch(event)
{
    const input = document.getElementById('searchfield');
    let content = input.value;
    if(content)
    {
        const findfeedresults = document.getElementById('findfeedresults');
        findfeedresults.innerHTML = 'Searching for matching feeds ...';
        let url = window.location.href + '?action=findfeed&format=Html&url=' + content;
        const response = await fetch(url);
        if(response.ok)
        {
            const data = await response.json();
            displayFoundFeed(data);
        }
        else
        {
            displayFeedSearchFail();
        }
    }
    else
    {
        displayFindFeedEmpty();
    }
}

// Display the found feeds
function displayFoundFeed(obj)
{
    const findfeedresults = document.getElementById('findfeedresults');

    let content = 'Found Feed(s) :';
    
    // Let's go throug every Feed found
    for(const element of obj)
    {
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
        for(const param in element.bridgeData)
        {
            console.log(element[param]);
            content += `<li>${element.bridgeData[param].name} : ${element.bridgeData[param].value}</li>`;
        }
        content+= `</div>
              </div>
            </div>`;

    }
    content += '<p><div class="alert alert-info" role="alert">This feed may be only one of the possible feeds. You may find more feeds using one of the bridges with different parameters, for example.</div></p>';
    findfeedresults.innerHTML = content;
}

// Display an error if no feed were found
function displayFeedSearchFail()
{
    const findfeedresults = document.getElementById('findfeedresults');
    findfeedresults.innerHTML = 'No Feed found !<div class="alert alert-info" role="alert">Not every bridge supports feed detection. You can check below within the bridge parameters to create a feed.</div>';
}

// Empty the Found Feed section
function displayFindFeedEmpty()
{
    const findfeedresults = document.getElementById('findfeedresults');
    findfeedresults.innerHTML = '';
}


// Add Event to 'Detect Feed" button
function addDetectionEvent()
{
    const button = document.getElementById('findfeed');
    button.addEventListener("click", feedSearch);
    button.addEventListener("keyup", feedSearch);

}

// Wait the page to be loaded to add the Detection Event
document.addEventListener('DOMContentLoaded', addDetectionEvent);
