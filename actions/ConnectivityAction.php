<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

 /**
  * Checks if the base URL for all whitelisted bridges are reachable
  */
class ConnectivityAction extends ActionAbstract {
	public function execute() {

		$bridgeName = $this->userData['bridge'];

		if (!$bridgeName)
			return $this->returnEntryPage();

		if(!Bridge::isWhitelisted($bridgeName)) {
			header('Content-Type: text/html');
			returnServerError('Bridge is not whitelisted!');
		}

		header('Content-Type: text/json');

		$bridge = Bridge::create($bridgeName);

		$retVal = array(
			'bridge' => $bridgeName,
			'successful' => false
		);

		if($bridge === false) {
			die(json_encode($retVal));
		}

		$opts = array(
			CURLOPT_CONNECTTIMEOUT => 5 // Don't spend too much time on each request
		);

		// Suppress errors as we handle them explicitly
		try {
			$html = getContents($bridge::URI, array(), $opts);

			if ($html)
				$retVal['successful'] = true;
			//else
				//echo 'Unable to reach ' . $bridge::URI . '!';
		} catch (Exception $e) {
			// $retVal['successful'] = false;
		}

		echo json_encode($retVal);
	}

	private function returnEntryPage() {
	echo <<<EOD
		<!DOCTYPE html>

		<html>
			<head>
				<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
				<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
				<script type="text/javascript">

		// This only works if the current document is in the same folder as index.php
		var remote = location.href.substring(0, location.href.lastIndexOf("/"));;

		window.onload = function() {
		var xhr = new XMLHttpRequest()
			xhr.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					// Typical action to be performed when the document is ready:
					process(xhr.responseText);
				}
			};
			console.log(remote);
		  xhr.open("GET", remote + "/index.php?action=list", false)
		  xhr.send();
		}

		function process(data) {

			var list = JSON.parse(data);

			var table = document.createElement('table');
			table.classList.add('table');

			var thead = document.createElement('thead');
			thead.innerHTML = '<tr><th scope="col">Bridge</th><th scope="col">Result</th></tr>';

			var tbody = document.createElement('tbody');

			for (var bridge in list.bridges) {
				var tr = document.createElement('tr');
				tr.classList.add('bg-secondary');
				tr.id = bridge;

				var td = document.createElement('td');
				td.innerText = list.bridges[bridge].name;

				// Let's link to the actual bridge on the front page; open in new tab
				var a = document.createElement('a');
				a.href = remote + "/index.php#bridge-" + bridge;
				a.target = '_blank';
				a.innerText = '[Show]';
				a.style.marginLeft = '5px';
				a.style.color = 'black';
				td.appendChild(a);
				tr.appendChild(td);

				var td = document.createElement('td');

				if (list.bridges[bridge].status === 'active') {
					td.innerHTML = '<i class="fas fa-hourglass-start"></i>';
				} else {
					td.innerHTML = '<i class="fas fa-times-circle"></i>';
				}

				tr.appendChild(td);

				tbody.appendChild(tr);
			}

			table.appendChild(thead);
			table.appendChild(tbody);
			document.body.appendChild(table);

			// Walk through the list
			for (var bridge in list.bridges) {
				console.log(list.bridges[bridge].uri)

				document.getElementById('status-message').innerText = 'Processing ' + list.bridges[bridge].name + '...';

				if (list.bridges[bridge].status !== 'active') {
					console.log('inactive, skip');
					continue;
				}

				var xhr = new XMLHttpRequest()
				xhr.open("GET", "http://localhost/rss-bridge/index.php?action=Connectivity&bridge=" + bridge, false)
				xhr.send();
				console.log(xhr.responseText);

				var tr = document.getElementById(bridge);
				tr.classList.remove('bg-secondary');

				try {
					var result = JSON.parse(xhr.responseText);
				} catch {
					tr.classList.add('bg-danger');
					tr.children[1].innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
					continue;
				}
				if (result.successful) {
					tr.classList.add('bg-success');
					tr.children[1].innerHTML = '<i class="fas fa-check"></i>';
				} else {
					tr.classList.add('bg-danger');
					tr.children[1].innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
				}

			}

			var msg = document.getElementById('status-message');
			msg.classList.remove('alert-primary');
			msg.classList.add('alert-success');
			msg.innerText = 'Done';
		}
				</script>
				<script>
						function search() {
						  // Declare variables
						  var input, filter, table, tr, td, i, txtValue;
						  input = document.getElementById("search");
						  filter = input.value.toUpperCase();
						  table = document.getElementsByTagName("table")[0];
						  tr = table.getElementsByTagName("tr");

						  // Loop through all table rows, and hide those who don't match the search query
						  for (i = 0; i < tr.length; i++) {
							td = tr[i].getElementsByTagName("td")[0];
							if (td) {
							  txtValue = td.textContent || td.innerText;
							  if (txtValue.toUpperCase().indexOf(filter) > -1) {
								tr[i].style.display = "";
							  } else {
								tr[i].style.display = "none";
							  }
							}
						  }
						}
				</script>
			</head>
			<body>
				<div class="alert alert-primary" id="status-message"></div>
				<input type="text" class="form-control" id="search" onkeyup="search()" placeholder="Search for bridge..">
			</body>
		</html>
EOD;
	}
}
