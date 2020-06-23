(function (w, d) {
	// If there's a hash in URL, try to match it to an active bridge,
	// expand its settings and scroll to it
	function exposeBridgeOnHash () {
		var bridge = d.querySelector(window.location.hash || 'none')
		if (bridge) {
			bridge.querySelector('.showmore-box').checked = true // Show settings
			w.scrollTo(0, bridge.offsetTop - 10) // Scroll into view
		}
	}
	w.addEventListener('load', exposeBridgeOnHash)

	// Intercept clicks on "format" submit buttons and render the feed URL
	// in a copy-paste-able textarea.
	function renderFeedUrlOnSubmit (e) {
		if (e.target.name === 'format') { // Intercept clicks on button[name="format"]
			e.preventDefault()
			var form = e.target.parentNode
			var inputs = form.elements

			var params = ['format=' + e.target.value]
			for (var i = 0; i < inputs.length; i++) {
				if (['format', 'copyfield'].includes(inputs[i].name)) continue
				params.push(inputs[i].name + '=' + encodeURIComponent(inputs[i].value))
			}

			var url = w.location.origin + w.location.pathname + '?' + params.join('&')

			var copyField = form.querySelector('.copyfield')
			if (!copyField) {
				copyField = d.createElement('textarea')
				copyField.className = 'copyfield'
				copyField.name = 'copyfield'
				copyField.readonly = 'readonly'
				copyField.addEventListener('click', function () {
					copyField.select()
				})
				form.appendChild(copyField)
			}
			copyField.value = url
		}
	}
	d.addEventListener('click', renderFeedUrlOnSubmit, false)
})(window, document)
