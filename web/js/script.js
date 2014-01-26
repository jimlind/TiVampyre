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

    // Collect shows.
    var shows = document.getElementsByClassName('show');
    for (var i = 0; i < shows.length; ++i) {
        var show = shows[i];
        // Collect show image placeholders and load.
        var logos = show.getElementsByClassName('showLogo');
        if (logos.length === 1) {
            findLogoImage(logos[0], database);
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
    
});

function findLogoImage(img, database) {
    if (database) {
        database.transaction(function(tx) {
            select = 'SELECT * FROM logo WHERE hash = ?';
            hashed = hash(img.getAttribute("data-title"));
            tx.executeSql(select, [hashed], function(tx, rs) {
                if (rs.rows.length === 1) {
                    data = rs.rows.item(0);
                    img.src="data:image/png;base64," + data['base64'];
                } else {
                    loadImage(img, database);
                }
            });
        });
    } else {
        loadImage(img);
    }
}

function loadImage(img, database) {
    var title = img.getAttribute("data-title");
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/image', true);
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xhr.onload = function() {
        var response = JSON.parse(this.responseText);
        var base64 = response['base64'];
        img.src="data:image/png;base64," + base64;
        if (database) {
            database.transaction(function(tx){
                $insert = 'INSERT INTO logo(hash, base64) VALUES (?,?)';
                tx.executeSql($insert, [hash(title), base64]);
            });
        }
    }
    xhr.send('title='+title);
}

function hash(inputString) {
    inputArray = inputString.split("");
    hashed = inputArray.reduce(function(a, b) {
        a = ((a << 5) - a) + b.charCodeAt(0);
        return a & a;
    }, 0);
    return hashed;
}