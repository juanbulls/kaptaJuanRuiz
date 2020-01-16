function id(ident){
    return document.getElementById(ident);
}
function xml(){
    var xmle = new XMLHttpRequest();
    xmle.onreadystatechange = function(){
        var r = this.responseText;
        if (this.readyState == 4 && this.status == 200){
           id("pj").innerHTML = r; 
        }
    };
    xmle.open("POST", "fcapik.php", true);
    xmle.send();
}
xml();
