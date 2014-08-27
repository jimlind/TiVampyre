ShowImageGecko = function()
{
    this.imageQueue = [];
    this.objectStore = null;
    var self = this;
    var openRequest = window.indexedDB.open("TiVampyre", 6);
    openRequest.onupgradeneeded = function(event) { 
        var database = event.target.result;
        if(database.objectStoreNames.contains("logo")) {
            database.deleteObjectStore("logo");
        }
        database.createObjectStore("logo", { keyPath: "hash" });
    };
    openRequest.onsuccess = function(event) {
        var database = event.target.result;
        var transaction = database.transaction("logo", "readwrite");
        self.objectStore = transaction.objectStore("logo");
        
        var queueCount = self.imageQueue.length;
        while(queueCount--) {
            self.findImage(self.imageQueue[queueCount]);
        }
    };
}

ShowImageGecko.prototype = new ShowImage();

ShowImageGecko.prototype.addShowImage = function(image)
{
    if (this.objectStore === null) {
        this.imageQueue.push(image);
    } else {
        this.findImage(image);
    }
}

ShowImageGecko.prototype.findImage = function(image)
{
    var self = this;
    var title = image.getAttribute("data-title");
    var hashedTitle = this.getHash(title);    
    var request = this.objectStore.get(hashedTitle);
    request.onsuccess = function(event) {
        if (request.result) {
            console.log("found data");
            console.log(request.result);
        } else {
            console.log("store data");
            self.objectStore.add({ "hash": hashedTitle, "title": title});
        }
    };
}