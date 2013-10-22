// Wait for the DOM to be ready.
document.addEventListener('DOMContentLoaded', function () {

	// Collect show image placeholders
	var logos = document.getElementsByClassName('showLogo');
	for (var i = 0; i < logos.length; ++i) {
		loadFromGoogle(logos[i]);
		// TODO: Load All The Images;
		if (i > 2) {
			break;
		}
	}

	// Collect clickable shows and loop over them.
	var shows = document.getElementsByClassName('clickableShow');
	for (var i = 0; i < shows.length; ++i) {
		// Perform action when clicked.
		shows[i].onclick = function(x){
			var id = this.getAttribute("data-id");
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '/submit', true);
			xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			xhr.onload = function() {
				console.log(this.responseText);
			}
			xhr.send('id='+id);
		};
	}
});

function loadFromGoogle(img){
	var title = img.getAttribute("data-title");
	var xhr = new XMLHttpRequest();
	xhr.open('POST', '/image', true);
	xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xhr.onload = function() {
		var response = JSON.parse(this.responseText);
		img.src="data:image/png;base64," + response['base64'];
	}
	xhr.send('title='+title);
}