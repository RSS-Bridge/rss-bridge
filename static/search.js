function search() {

	var searchTerm = document.getElementById('searchfield').value;
	var searchableElements = document.getElementsByTagName('section');

	var regexMatch = new RegExp(searchTerm, 'i');

	// Attempt to create anchor from search term (will default to 'localhost' on failure)
	var searchTermUri = document.createElement('a');
	searchTermUri.href = searchTerm;

	if(searchTermUri.hostname == 'localhost') {
		searchTermUri = null;
	} else {

		// Ignore "www."
		if(searchTermUri.hostname.indexOf('www.') === 0) {
			searchTermUri.hostname = searchTermUri.hostname.substr(4);
		}

	}

	for(var i = 0; i < searchableElements.length; i++) {

		var textValue = searchableElements[i].getAttribute('data-ref');
		var anchors = searchableElements[i].getElementsByTagName('a');

		if(anchors != null && anchors.length > 0) {

			var uriValue = anchors[0]; // First anchor is bridge URI

			// Ignore "www."
			if(uriValue.hostname.indexOf('www.') === 0) {
				uriValue.hostname = uriValue.hostname.substr(4);
			}

		}

		if(textValue != null || uriValue != null) {

			if(textValue.match(regexMatch) != null ||
				uriValue.hostname.match(regexMatch) ||
				searchTermUri != null &&
				uriValue.hostname != 'localhost' && (
					uriValue.href.match(regexMatch) != null ||
					uriValue.hostname == searchTermUri.hostname)) {

				searchableElements[i].style.display = 'block';

			} else {

				searchableElements[i].style.display = 'none';

			}

		}

	}

}
