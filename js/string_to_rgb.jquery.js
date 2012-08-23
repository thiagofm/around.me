$.extend({
  stringToRGB: function(param){
    function hashCode(str) { // java String#hashCode
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
           hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        return hash;
    } 

    function intToARGB(i){
        return ((i>>24)&0xFF).toString(16) + 
               ((i>>16)&0xFF).toString(16) + 
               ((i>>8)&0xFF).toString(16) + 
               (i&0xFF).toString(16);
    }

    function stringToRGB(hexa){
      return '#' + intToARGB(hashCode(hexa)).substring(0,6);
    }
    
    var string = stringToRGB(param);
    while(string.length <= 6){
      string += '0'
    }

    return string;
  }
});
