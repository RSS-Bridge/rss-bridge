function search() {

	var searchTerm = document.getElementById('searchfield').value;
	var searchableElements = document.getElementsByTagName('section');

	var regexMatch = new RegExp(searchTerm, "i");

	for(var i = 0; i < searchableElements.length; i++) {

		var textValue = searchableElements[i].getAttribute('data-ref');
		if(textValue != null) {

			if(textValue.match(regexMatch) == null && searchableElements[i].style.display != "none") {

				searchableElements[i].style.display = "none";

			} else if(textValue.match(regexMatch) != null) {

				searchableElements[i].style.display = "block";

			}

		}

	}

}
