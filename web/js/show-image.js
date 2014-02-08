function ShowImage(database, img) {
    this.db = database;
    this.image = img;
    this.title = img.getAttribute("data-title");
    this.hashed = null;
    
    if (this.db) {
        this.hashed = this.getHash();
        this.lookupImage();
    } else {
        this.loadImage();
    }
};
ShowImage.prototype.getHash = function() {
    var inputArray = this.title.split("");
    var hashed = inputArray.reduce(function(a, b) {
        a = ((a << 5) - a) + b.charCodeAt(0);
        return a & a;
    }, 0);
    return hashed;
};
ShowImage.prototype.lookupImage = function() {
    var self = this;
    this.db.transaction(function(tx) {
        var select = 'SELECT * FROM logo WHERE hash = ?';
        tx.executeSql(select, [self.hashed], function(tx, rs) {
            if (rs.rows.length === 1) {
                var data = rs.rows.item(0);
                self.setImage(data['base64']);
            } else {
                self.loadImage();
            }
        });
    });
};
ShowImage.prototype.setImage = function(base64) {
    this.image.src="data:image/png;base64," + base64;
};
ShowImage.prototype.loadImage = function() {
    var self = this;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/image', true);
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xhr.onload = function() {
        var response = JSON.parse(this.responseText);
        var base64 = response['base64'];
        self.setImage(base64);
        if (self.db) {
            self.saveImage(base64);
        }
    };
    xhr.send('title=' + this.title);
};
ShowImage.prototype.saveImage = function(base64) {
    self = this;
    this.db.transaction(function(tx){
        var insert = 'INSERT INTO logo(hash, base64) VALUES (?,?)';
        tx.executeSql(insert, [self.hashed, base64]);
    });
}