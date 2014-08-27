ShowImageWebKit = function()
{
    this.db = openDatabase('TiVampyre', '1', 'TV Manager', 5242880); // 5MB
    this.db.transaction(function(tx) {
        create = 'CREATE TABLE IF NOT EXISTS logo(hash TEXT, base64 TEXT)';
        tx.executeSql(create);
    });;
}

ShowImageWebKit.prototype = new ShowImage();

ShowImageWebKit.prototype.addShowImage = function(image)
{
    var self = this;
    var hashedTitle = this.getHash(image.getAttribute("data-title"));
    this.db.transaction(function(tx) {
        var select = 'SELECT * FROM logo WHERE hash = ?';
        tx.executeSql(select, [hashedTitle], function(tx, rs) {
            if (rs.rows.length === 1) {
                var data = rs.rows.item(0);
                self.setImage(image, data['base64']);
            } else {
                self.loadImageRemote(image);
            }
        });
    });
};

ShowImageWebKit.prototype.saveImage = function(title, base64)
{
    var hashedTitle = this.getHash(title);
    this.db.transaction(function(tx){
        var insert = 'INSERT INTO logo(hash, base64) VALUES (?,?)';
        tx.executeSql(insert, [hashedTitle, base64]);
    });
};