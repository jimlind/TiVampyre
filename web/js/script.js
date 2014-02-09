// Wait for the DOM to be ready.
document.addEventListener('DOMContentLoaded', function () {

    // Open connection to database.
    var database = null;
    if (typeof(openDatabase) === typeof(Function)) {
        database = openDatabase('TiVampyre', '1', 'TV Manager', 5242880); // 5MB
        database.transaction(function(tx) {
            $create = 'CREATE TABLE IF NOT EXISTS logo(hash TEXT, base64 TEXT)';
            tx.executeSql($create);
        });
    }

    var shows = document.getElementsByClassName('showImage');
    for (var i = 0; i < shows.length; ++i) {
        new ShowImage(database, shows[i]);
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