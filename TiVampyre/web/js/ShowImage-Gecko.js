ShowImageGecko = function()
{
    this.imageQueue = [];
    this.database = null;
    var self = this;
    var openRequest = window.indexedDB.open("TiVampyre", 9);
    openRequest.onupgradeneeded = function(event) { 
        var database = event.target.result;
        if(database.objectStoreNames.contains("logo")) {
            database.deleteObjectStore("logo");
        }
        database.createObjectStore("logo", { keyPath: "hash" });
    };
    openRequest.onsuccess = function(event) {
        self.database = event.target.result;  
        var queueCount = self.imageQueue.length;
        while(queueCount--) {
            self.findImage(self.imageQueue[queueCount]);
        }
    };
}

ShowImageGecko.prototype = new ShowImage();

ShowImageGecko.prototype.addShowImage = function(image)
{
    if (this.database === null) {
        this.imageQueue.unshift(image);
    } else {
        this.findImage(image);
    }
}

ShowImageGecko.prototype.findImage = function(image)
{
    var self = this;
    var title = image.getAttribute("data-title"); 
    
    var request = this.getObjectStore().get(this.getHash(title));
    request.onsuccess = function(event) {
        if (request.result) {
            self.setImage(image, request.result.hex);
        } else {
            self.loadImageRemote(image);
        }
    };
}

ShowImageGecko.prototype.saveImage = function(title, base64)
{
    var titleHash = this.getHash(title);
    this.getObjectStore().add({ "hash": titleHash, "hex": base64});
}

ShowImageGecko.prototype.getObjectStore = function()
{
    var transaction = this.database.transaction("logo", "readwrite");
    return transaction.objectStore("logo"); 
}