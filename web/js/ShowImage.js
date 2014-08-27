ShowImage = function(){}

ShowImage.prototype.addShowImage = function(image)
{
    this.loadImageRemote(image);
};

ShowImage.prototype.loadImageRemote = function(image)
{
    var self = this;
    var title = image.getAttribute("data-title");
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/image", true);
    xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xhr.onload = function() {
        var response = JSON.parse(this.responseText);
        var base64 = response["base64"];
        self.setImage(image, base64);
        self.saveImage(title, base64);
    };
    xhr.send("title=" + title);
};

ShowImage.prototype.saveImage = function() {}

ShowImage.prototype.setImage = function(image, base64)
{
    image.src="data:image/png;base64," + base64;
};

ShowImage.prototype.getHash = function(inputString)
{
    var inputArray = inputString.split("");
    var hashed = inputArray.reduce(function(a, b) {
        a = ((a << 5) - a) + b.charCodeAt(0);
        return a & a;
    }, 0);
    return hashed;
};