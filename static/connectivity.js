var remote = location.href.substring(0, location.href.lastIndexOf("/"));
var stop = false;

window.onload = function() {

	fetch(remote + '/index.php?action=list').then(function(response) {
		return response.text().then(function(data){
			processBridgeList(data);
		});
	});

}

function processBridgeList(data) {

	var list = JSON.parse(data);

	buildTable(list);
	runConnectivityChecks(list);

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

		// Link to the actual bridge on index.php
		var a = document.createElement('a');
		a.href = remote + "/index.php#bridge-" + bridge;
		a.target = '_blank';
		a.innerText = '[Show]';
		a.style.marginLeft = '5px';
		a.style.color = 'black';

		td_bridge.appendChild(a);
		tr.appendChild(td_bridge);

		var td_result = document.createElement('td');

		if (bridgeList.bridges[bridge].status === 'active') {
			td_result.innerHTML = '<i class="fas fa-hourglass-start"></i>';
		} else {
			td_result.innerHTML = '<i class="fas fa-times-circle"></i>';
		}

		tr.appendChild(td_result);
		tbody.appendChild(tr);

	}

	table.appendChild(thead);
	table.appendChild(tbody);

	var content = document.getElementById('main-content');
	content.appendChild(table);

}

async function runConnectivityChecks(bridgeList) {

	var msg = document.getElementById('status-message').getElementsByTagName('span')[0];

	for (var bridge in bridgeList.bridges) {

		if (stop) break;

		msg.innerText = 'Processing ' + bridgeList.bridges[bridge].name + '...';

		if (bridgeList.bridges[bridge].status !== 'active') {
			continue;
		}

		var xhr = new XMLHttpRequest()

		xhr.open("GET", "http://localhost/rss-bridge/index.php?action=Connectivity&bridge=" + bridge, false)
		xhr.send();

		var tr = document.getElementById(bridge);
		tr.classList.remove('bg-secondary');

		try {
			var result = JSON.parse(xhr.responseText);
		} catch {
			tr.classList.add('bg-danger');
			tr.children[1].innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
			return;
		}

		if (result.successful) {
			tr.classList.add('bg-success');
			tr.children[1].innerHTML = '<i class="fas fa-check"></i>';
		} else {
			tr.classList.add('bg-danger');
			tr.children[1].innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
		}

	}

	msg.classList.remove('alert-primary');
	msg.classList.add('alert-success');
	msg.textContent = 'Done';

}

function stopConnectivityChecks() {
	stop = true;
}

function search() {

	var input = document.getElementById('search');
	var filter = input.value.toUpperCase();
	var table = document.getElementsByTagName('table')[0];
	var tr = table.getElementsByTagName('tr');

	for (var i = 0; i < tr.length; i++) {

		var td = tr[i].getElementsByTagName('td')[0];

		if (td) {

			var txtValue = td.textContent || td.innerText;

			if (txtValue.toUpperCase().indexOf(filter) > -1) {
				tr[i].style.display = '';
			} else {
				tr[i].style.display = 'none';
			}

		}

	}

}