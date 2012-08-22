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

	var infowindow = new google.maps.InfoWindow(options);
	map.setCenter(options.position);
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

function geolocationCallBack(message) {
    console.log(message.data.latitude);
    console.log(message.data.longitude);
}
