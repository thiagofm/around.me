<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>around.me - See your around</title>
  <meta name="description" content="">

  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">

  <script src="js/libs/modernizr-2.5.3.min.js"></script>
</head>
<body>
  <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
  <header>
    <h1>around.me</h1>
  </header>

  <div id="main" role="main">
    <div id="map">
      <div id="map_canvas"></div>
    </div><!-- end div#map -->

    <div id="feed">
      <div class="content">
      </div><!-- end div.content -->
    </div><!-- end div#feed -->
  </div><!-- end div#main -->

  <footer>
    <div id="form">
      <form id="message">
        <input type="text" value="Type your message here..." id="message_input" />
        <div id="submit">
          <input type="submit" value="Send" />
        </div><!-- end div#submit -->
        <div id="sending">
          <img src="img/ajax-loader.gif" alt="sending..." />
        </div><!-- end div#sending -->
        <div class="clear"></div>
      </form>
    </div><!-- end div#form -->
  </footer>

  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>

  <script type="text/javascript"
    src="http://maps.googleapis.com/maps/api/js?key=AIzaSyA6O6CoNsT22DxgvNFgKWSHJOJLhP0lJAI&sensor=true">
  </script>

  <script src="js/libs/jquery.nicescroll.min.js"></script>
  <script src="js/script.js"></script>
</body>
</html>
