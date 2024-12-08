var remote = location.href.substring(0, location.href.lastIndexOf("/"));
var bridges = [];
var abort = false;

window.onload = function() {

	fetch(remote + '/?action=list').then(function(response) {
		return response.text()
	}).then(function(data){
		processBridgeList(data);
	}).catch(console.log.bind(console)
	);

}

function processBridgeList(data) {

	var list = JSON.parse(data);

	buildTable(list);
	buildBridgeQueue(list);
	checkNextBridgeAsync();

}

function buildTable(bridgeList) {

	var table = document.createElement('table');
	table.classList.add('table');

	var thead = document.createElement('thead');
	thead.innerHTML = `
	<tr>
		<th scope="col">Bridge</th>
		<th scope="col">Result</th>
	</tr>`;

	var tbody = document.createElement('tbody');

	for (var bridge in bridgeList.bridges) {

		var tr = document.createElement('tr');
		tr.classList.add('bg-secondary');
		tr.id = bridge;

		var td_bridge = document.createElement('td');
		td_bridge.innerText = bridgeList.bridges[bridge].name;

		// Link to the actual bridge on frontpage
		var a = document.createElement('a');
		a.href = remote + "/?#bridge-" + bridge;
		a.target = '_blank';
		a.innerText = '[Show]';
		a.style.marginLeft = '5px';
		a.style.color = 'black';

		td_bridge.appendChild(a);
		tr.appendChild(td_bridge);

		var td_result = document.createElement('td');

		if (bridgeList.bridges[bridge].status === 'active') {
			td_result.innerHTML = '<i title="Scheduled" class="fas fa-hourglass-start"></i>';
		} else {
			td_result.innerHTML = '<i title="Inactive" class="fas fa-times-circle"></i>';
		}

		tr.appendChild(td_result);
		tbody.appendChild(tr);

	}

	table.appendChild(thead);
	table.appendChild(tbody);

	var content = document.getElementById('main-content');
	content.appendChild(table);

}

function buildBridgeQueue(bridgeList) {
	for (var bridge in bridgeList.bridges) {
		if (bridgeList.bridges[bridge].status !== 'active')
			continue;
		bridges.push(bridge);
	}
}


function checkNextBridgeAsync() {
	return new Promise((resolve) => {
		var msg = document.getElementById('status-message');
		var icon = document.getElementById('status-icon');

		if (bridges.length === 0) {
			msg.classList.remove('alert-primary');
			msg.classList.add('alert-success');
			msg.getElementsByTagName('span')[0].textContent = 'Done';

			icon.classList.remove('fa-sync');
			icon.classList.add('fa-check');
		} else {
			var bridge = bridges.shift();

			msg.getElementsByTagName('span')[0].textContent = 'Processing ' + bridge + '...';

			fetch(remote + '/?action=Connectivity&bridge=' + bridge)
			.then(function(response) { return response.text() })
			.then(JSON.parse)
			.then(processBridgeResultAsync)
			.then(markBridgeSuccessful, markBridgeFailed)
			.then(checkAbortAsync)
			.then(checkNextBridgeAsync, abortChecks)
			.catch(console.log.bind(console));

			search(); // Dynamically update search results
			updateProgressBar();

		}

		resolve();
	});
}

function abortChecks() {
	return new Promise((resolve) => {
		var msg = document.getElementById('status-message');

		msg.classList.remove('alert-primary');
		msg.classList.add('alert-warning');
		msg.getElementsByTagName('span')[0].textContent = 'Aborted';

		var icon = document.getElementById('status-icon');
		icon.classList.remove('fa-sync');
		icon.classList.add('fa-ban');

		bridges.forEach((bridge) => {
			markBridgeAborted(bridge);
		})

		resolve();
	});
}

function processBridgeResultAsync(result) {
	return new Promise((resolve, reject) => {
		if (result.successful) {
			resolve(result);
		} else {
			reject(result);
		}
	});
}

function markBridgeSuccessful(result) {
	return new Promise((resolve) => {
		var tr = document.getElementById(result.bridge);
		tr.classList.remove('bg-secondary');
		if (result.http_code == 200) {
			tr.classList.add('bg-success');
			tr.children[1].innerHTML = '<i title="Successful" class="fas fa-check"></i>';
		} else {
			tr.classList.add('bg-primary');
			tr.children[1].innerHTML = '<i title="Redirected" class="fas fa-directions"></i>';
		}

		resolve();
	});
}

function markBridgeFailed(result) {
	return new Promise((resolve) => {
		var tr = document.getElementById(result.bridge);
		tr.classList.remove('bg-secondary');
		tr.classList.add('bg-danger');
		tr.children[1].innerHTML = '<i title="Failed" class="fas fa-exclamation-triangle"></i>';

		resolve();
	});
}

function markBridgeAborted(bridge) {
	return new Promise((resolve) => {
		var tr = document.getElementById(bridge);
		tr.classList.remove('bg-secondary');
		tr.classList.add('bg-warning');
		tr.children[1].innerHTML = '<i title="Aborted" class="fas fa-ban"></i>';

		resolve();
	});
}

function checkAbortAsync() {
	return new Promise((resolve, reject) => {
		if (abort) {
			reject();
			return;
		}

		resolve();
	});
}

function updateProgressBar() {

	// This will break if the table changes
	var total = document.getElementsByTagName('tr').length - 1;
	var current = bridges.length;
	var progress = (total - current) * 100 / total;

	var progressBar = document.getElementsByClassName('progress-bar')[0];

	if(progressBar){
		progressBar.setAttribute('aria-valuenow', progress.toFixed(0));
		progressBar.style.width = progress.toFixed(0) + '%';
	}

}

function stopConnectivityChecks() {
	abort = true;
}

function search() {

	var input = document.getElementById('search');
	var filter = input.value.toUpperCase();
	var table = document.getElementsByTagName('table')[0];
	var tr = table.getElementsByTagName('tr');

	for (var i = 0; i < tr.length; i++) {

		var td1 = tr[i].getElementsByTagName('td')[0];
		var td2 = tr[i].getElementsByTagName('td')[1];

		if (td1) {

			var txtValue = td1.textContent || td1.innerText;

			var title = '';
			if(td2.getElementsByTagName('i')[0]) {
				title = td2.getElementsByTagName('i')[0].title;
			}

			if (txtValue.toUpperCase().indexOf(filter) > -1
			|| title.toUpperCase().indexOf(filter) > -1) {
				tr[i].style.display = '';
			} else {
				tr[i].style.display = 'none';
			}

		}

	}

}