/*
 * Javascript code for "around.me" website.
 *
 * Version: 0.1, 22 august 2012
 *
 */ 

$(document).ready(function() {
	//Use funções de start para cada função do site
	//start_contact();
	start_map();
});

/*#########################################
 * MAP
 *########################################*/

var mapOptions;
var map;

function start_map() {
	mapOptions = {
          center: new google.maps.LatLng(-34.397, 150.644),
          zoom: 8,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };

    map = new google.maps.Map(document.getElementById("map_canvas"),
        mapOptions);
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