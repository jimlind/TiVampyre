// Wait for the DOM to be ready.
document.addEventListener('DOMContentLoaded', function () {

    var showImageService = null;
    if (typeof(openDatabase) === typeof(Function)) {
        showImageService = new ShowImageWebKit();
    } else if ("indexedDB" in window) {
        showImageService = new ShowImageGecko();
    } else {
        showImageService = new ShowImage();
    }

    var shows = document.getElementsByClassName('showImage');
    if (showImageService !== null && shows.length > 0) {
        for (var i = 0; i < shows.length; ++i) {
            showImageService.addShowImage(shows[i]);
        }
    }
    
    // Dynamically resize the show width.
    var shows = document.getElementsByClassName('show');
    if (shows.length > 0) {
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
        }
    };
    // Force the resize event to be triggered.
    window.dispatchEvent(new Event('resize'));
});