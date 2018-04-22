function select(){
	var fragment = window.location.hash.substr(1);
	var bridge = document.getElementById(fragment);

	if(bridge !== null) {
		bridge.getElementsByClassName('showmore-box')[0].checked = true;
	}
}

document.addEventListener('DOMContentLoaded', select);