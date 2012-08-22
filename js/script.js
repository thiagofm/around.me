/*
 * Javascript code for "around.me" website.
 *
 * Version: 0.1, 22 august 2012
 *
 */ 

$(document).ready(function() {
	//Use funções de start para cada função do site
	//start_contact();
	start_form();
	start_map();
	start_utils();
  
});

/*#########################################
 * FORM
 *########################################*/

function start_form() {
	$("#message").focusin(function() {
		if ($(this).val() == "Type your message here...") {
			$(this).val("");
		}
	});

	$("#message").focusout(function() {
		if ($(this).val() == "") {
			$(this).val("Type your message here...");
		}
	});	
}

/*#########################################
 * MAP
 *########################################*/

var mapOptions;
var map;

function start_map() {
	mapOptions = {
          zoom: 12,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };

    map = new google.maps.Map(document.getElementById('map_canvas'),
        mapOptions);

    // Try HTML5 geolocation
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        var pos = new google.maps.LatLng(position.coords.latitude,
                                         position.coords.longitude);

        var marker = new google.maps.Marker({
          map: map,
          position: pos,
          title: 'You are here!'
        });

        ajaxAuth(position.coords.latitude, position.coords.longitude);

        /* Ballon
        var infowindow = new google.maps.InfoWindow({
          map: map,
          position: pos,
          content: 'Location found using HTML5.'
        });
		*/

        map.setCenter(pos);
      }, function() {
        handleNoGeolocation(true);
      });
    } else {
      // Browser doesn't support Geolocation
      handleNoGeolocation(false);
    }
}

function handleNoGeolocation(errorFlag) {
	if (errorFlag) {
	  var content = 'Error: The Geolocation service failed.';
	} else {
	  var content = 'Error: Your browser doesn\'t support geolocation.';
	}

	var options = {
	  map: map,
	  position: new google.maps.LatLng(60, 105),
	  content: content
	};

  ajaxAuth(60,105);

	var infowindow = new google.maps.InfoWindow(options);
	map.setCenter(options.position);
}

/*#########################################
 * UTILS
 *########################################*/

function start_utils() {
	$("#feed").niceScroll("#feed .content");
}

function add_message(data){
  var pos = new google.maps.LatLng(data.lat, data.lng);
  var infowindow = new google.maps.InfoWindow({
    map: map,
    position: pos,
    content: '<b>' + data.username + '</b>: '+ data.message
  });
}


/*#########################################
 * AJAXES
 *########################################*/

function ajaxAuth(lat,lng){
  $.post('auth.php',
    {latitude: lat, longitude: lng}, function(data){
      var obj = jQuery.parseJSON(data);
      console.log(data);
      $.each(obj.mensagens,function(index,value){
        add_message(value);
      });
  });
}

/*#########################################
 * EXAMPLE - CONTACT
 *########################################*/

function start_contact(){
	$("#form_contact").submit(function() {
		$("#contact-message").html('<img src="images/ajax-loader.gif" alt="Sending" />');
		$.post($(this).attr("action"), $(this).serialize(), function(data) {
			var obj = jQuery.parseJSON(data);
			
			if (obj.success == true) {
				$('#contact input[type="text"]').val("");
				$('#contact textarea').val("");
			}
			
			$("#contact-message").html(obj.html);
		});
		return false;
	});
}

/*
 * GEOLOCATION - realtime.co
 */

//var latitude;
//var longitude;
//var id;

//function geolocationCallBack(message) {
  //latitude = message.data.latitude;
  //longitude = message.data.longitude;
  //id = message.data.id;
  //xRTML.sendMessage('global','Hello Realtime');
//}

//function onMessage(message){
  //console.log(message);
//}

//$.post('mensagem.php',funciton(){

//});
