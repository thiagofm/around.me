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
	$("#message_input").focusin(function() {
		if ($(this).val() == "Type your message here...") {
			$(this).val("");
		}
	});

	$("#message_input").focusout(function() {
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
var markerCluster;
var markers = new Array();

function start_map() {
	 mapOptions = {
          zoom: 12,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };

    map = new google.maps.Map(document.getElementById('map_canvas'),
        mapOptions);

    markerCluster = new MarkerClusterer(map, markers, {
      maxZoom: 15
    });

    // Try HTML5 geolocation
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        var pos = new google.maps.LatLng(position.coords.latitude,
                                         position.coords.longitude);

        /*
        var marker = new google.maps.Marker({
          map: map,
          position: pos,
          title: 'You are here!'
        });*/

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
  cron_message();
}

var message_pull = new Array();
var message_ballon = undefined;
var message_seconds_showing = 0;
var niceScroll;

function add_message(data, show_ballon){
  if (data.date != undefined && data.date != null) {
    var time = data.date.split(" ");  
  } else {
    var time = new Array();
    time[0] = "9999-99-99";
    time[1] = "99:99:99";
  }
  
  $("#feed .content").append('<p><span class="time">[' + time[1] + ']</span> <span class="user">' + data.username + ':</span> ' + data.message + '</p>');
  $("#feed").getNiceScroll().resize();
  $("#feed").scrollTop($("#feed .content").height() - $("#feed").height());
  add_marker(data);

  if (show_ballon) {
    message_pull.push(data);
  }
}

function cron_message() {
  setTimeout(function(){cron_message();},3000);
  if (message_pull.length > 0) {
    /* Use para balões do Google Maps
    if (message_ballon != undefined && message_ballon.b.contentNode != undefined) {
      $(message_ballon.b.contentNode).parent().parent().parent().fadeOut(1000, function() {
        message_ballon.close();
        show_message(message_pull[0]);
      });
    */
    if (message_ballon != undefined) {
        $(message_ballon.content_).fadeOut(1000, function() {
        message_ballon.close();
        show_message(message_pull[0]);
      });
    } else {
      show_message(message_pull[0]);
    }
  } else {
    message_seconds_showing = message_seconds_showing + 1000;
  }
}

function show_message(data) {
  var pos = new google.maps.LatLng(data.lat, data.lng);
/*
  message_ballon = new google.maps.InfoWindow({
    map: map,
    position: pos,
    content: '<b>' + data.username + '</b>: '+ data.message,
    disableAutoPan: false
  });
*/
  var boxText = document.createElement("div");
  boxText.style.cssText = "border: 1px solid black; margin-top: 8px; background: #ffffff; padding: 5px;";
  boxText.innerHTML = '<p><b>' + data.username + '</b>: '+ data.message + '</p>';

  var myOptions = {
    content: boxText,
    boxStyle: { 
      background: "url('img/tipbox.gif') no-repeat",
      width: "280px"
    },
    disableAutoPan: false,
    pixelOffset: new google.maps.Size(-140, 10),
    position: pos,
    closeBoxURL: "",
    isHidden: false,
    pane: "floatPane",
    enableEventPropagation: true
  };

  message_ballon = new InfoBox(myOptions);
  message_ballon.open(map);

  message_pull.shift();
}

var user_marker = new Array();

function add_marker(data) {
  var encontrado = false;

  for(var i=0; i<user_marker.length; i++) {
      if (user_marker[i] == data.user_id) {
        encontrado = true;
        break;
      }
  }

  if (!encontrado) {
    var pos = new google.maps.LatLng(data.lat, data.lng);

    var marker = new google.maps.Marker({
      map: map,
      position: pos,
      title: data.username
    });

    markerCluster.addMarker(marker);
    //markers.push(marker);
    user_marker.push(data.user_id);

    
  }
}

/*#########################################
 * AJAXES
 *########################################*/

var latitude;
var longitude;
var user_id;
var username;

function ajaxAuth(lat,lng){
  $.post('auth.php', {latitude: lat, longitude: lng}, function(data){
	   console.log(data);
     var obj = jQuery.parseJSON(data);
      username = obj.username;
      user_id = obj.user_id;
      latitude = obj.lat;
      longitude = obj.lng;

      add_marker({
        lat: latitude, 
        lng: longitude, 
        user_id: user_id, 
        username: username, 
        message: "You are here!"
      });

      $.each(obj.mensagens,function(index,value){
        add_message(value, false);
      });
  });
}

$('#form').submit(function(){
  message = $(this).find('input[type=text]').val();
  var req = {
    latitude: latitude,
    longitude: longitude,
    user_id: user_id,
    username: username,
    message: message 
  };
  
  $("#submit").hide();
  $("#sending").show();

  $.post('send_message.php', req, function(data){
    $("#submit").show();
    $("#sending").hide();
    $("#message_input").val("");
    //console.log(data);
  });
  return false;
});

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

function onMessage(message){
  message = $.parseJSON(message.message);
  console.log(message.xrtml.a);
  if (message.xrtml.a == "peopleAction") {
    console.log("people");
    //People notification received
    message = message.xrtml.d;
    add_marker(message, true);
  } else {
    console.log("message");
    //Message received
    message = message.xrtml.d;
    add_message(message, true);
  }
  
}

//$.post('mensagem.php',funciton(){

//});
