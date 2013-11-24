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
            // Clicking logo opens the episode list.
            logos[0].onclick = function(event){
                var parent = this.parentNode;
                // Close all opened episodes 
                allShows = document.getElementsByClassName('show');
                for (var i = 0; i < allShows.length; ++i) {
                    allShows[i].classList.remove('opened');
                }
                // If it one episode just click it.
                var episodeTitles = parent.getElementsByTagName('h3');
                if (episodeTitles.length === 1) {
                    episodeTitles[0].dispatchEvent(new Event('click'));
                    return true;
                }
                // Open the list of episodes.
                selectedEpisodes = parent.classList.add('opened');
                
            }
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
    
    // Dynamically resize the show width.
    window.onresize = function(){
        columns = Math.min(Math.floor(window.innerWidth / 205), 9);
        prevColStyle = shows[0].getAttribute('data-cols');
        // If the columns change.
        if (columns !== prevColStyle) {
            // Apply to all shows.
            for (var i = 0; i < shows.length; ++i) {
                show = shows[i];
                show.classList.remove('cols' + prevColStyle);;
                show.classList.add('cols' + columns);
                show.setAttribute('data-cols', columns);
            }
        }
    };
    // Force the resize event to be triggered.
    window.dispatchEvent(new Event('resize'));
        
    // Collect showing titles and loop over them.
    var titles = document.getElementsByTagName('h3');
    var modal =  document.getElementById('modal');
    var dialog =  document.getElementById('dialog');
    for (var i = 0; i < titles.length; ++i) {
        // Open modal when clicked.
        titles[i].onclick = function(event){
            var details = this.parentNode.getElementsByClassName('episodeDetails');
            if (details.length === 1) {
                dialog.innerHTML = details[0].innerHTML;
            }
            modal.classList.add('active');
        };
    }
    
    /*
    var id = this.getAttribute("data-id");
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/submit', true);
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xhr.send('id='+id);
     */
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