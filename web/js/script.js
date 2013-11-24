// Wait for the DOM to be ready.
document.addEventListener('DOMContentLoaded', function () {

        // Collect shows.
        var shows = document.getElementsByClassName('show');
        for (var i = 0; i < shows.length; ++i) {
            var show = shows[i];
            // Collect show image placeholders and load.
            var logos = show.getElementsByClassName('showLogo');
            if (logos.length === 1) {
                loadImage(logos[0]);
            }
            // Collect episodes count and display.
            var episodes = show.getElementsByClassName('episode').length;
            var showingText = episodes + " showing";
            if (episodes > 1) {
                showingText += "s";
            }
            // Fill showing count text.
            var countSpans = show.getElementsByClassName('showingCount')
            if (countSpans.length === 1) {
                countSpans[0].innerHTML = showingText;
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

function loadImage(img) {
	var title = img.getAttribute("data-title");
	var hashed = hash(title);
	var stored = localStorage.getItem(hashed);
	if (!stored) {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '/image', true);
		xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xhr.onload = function() {
			var response = JSON.parse(this.responseText);
			localStorage.setItem(hashed, response['base64']);
			img.src="data:image/png;base64," + response['base64'];
		}
		xhr.send('title='+title);
	} else {
		img.src="data:image/png;base64," + stored;
	}
}

function hash(inputString) {
	inputArray = inputString.split("");
	hashed = inputArray.reduce(function(a, b) {
		a = ((a << 5) - a) + b.charCodeAt(0);
		return a & a;
	}, 0);
	return hashed;
}