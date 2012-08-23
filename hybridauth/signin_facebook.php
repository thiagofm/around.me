    <?php
    // start a new session (required for Hybridauth)
    session_start();
    // change the following paths if necessary
    $config = dirname(__FILE__) . '/config.php';
    require_once( "Hybrid/Auth.php" );
    try{
    // create an instance for Hybridauth with the configuration file path as parameter
    $hybridauth = new Hybrid_Auth( $config );
    // try to authenticate the user with twitter,
    // user will be redirected to Twitter for authentication,
    // if he already did, then Hybridauth will ignore this step and return an instance of the adapter
    $facebook = $hybridauth->authenticate( "Facebook" );
     
    // get the user profile
    $facebook_user_profile = $facebook->getUserProfile();
    //echo "Ohai there! U are connected with: <b>{$twitter->id}</b><br />";
    //echo "As: <b>{$twitter_user_profile->displayName}</b><br />";
    //echo "And your provider user identifier is: <b>{$twitter_user_profile->identifier}</b><br />";
     
    // debug the user profile
    //print_r( $twitter_user_profile );
     
    // exp of using the twitter social api: return users count of friends, followers, updates etc.
    //$account_totals = $twitter->api()->get( 'account/totals.json' );
     
    // print recived stats
    //echo "Here some of yours stats on twitter: " . print_r( $account_totals, true );
     
    // disconnect the user ONLY form twitter
    // this will not disconnect the user from others providers if any used nor from your application
    //echo "Logging out..";
	
	function curPageURL() {
		 $pageURL = 'http';
		 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		 $pageURL .= "://";
		 if ($_SERVER["SERVER_PORT"] != "80") {
		  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		 } else {
		  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		 }
		 return $pageURL;
	}
	
	if(isset($_SESSION['user_id'])){
		require '../conn.php';
		$conn = new Mysql();
        $username = $facebook_user_profile->displayName;
        $resultado = $conn->executar_query("SELECT * FROM user WHERE username = '".$username."';");
		if (mysql_num_rows($resultado) == 0) {
            $resultado = $conn->executar_query('UPDATE user SET username = "' . $facebook_user_profile->displayName . '" WHERE id = ' . $_SESSION['user_id']);    
        } else {
            while ($row = mysql_fetch_assoc($resultado)) {
                $_SESSION['user_id'] = $row["user_id"];
            }
        }
        
		$_SESSION['username'] = $twitter_user_profile->displayName;
		$_SESSION['facebook'] = true;
		header('Location: ' . str_replace("/hybridauth/signin_facebook.php", "", curPageURL()));
	}
	
    //$twitter->logout();
    }
    catch( Exception $e ){
    // Display the recived error,
    // to know more please refer to Exceptions handling section on the userguide
    switch( $e->getCode() ){
    case 0 : echo "Unspecified error."; break;
    case 1 : echo "Hybriauth configuration error."; break;
    case 2 : echo "Provider not properly configured."; break;
    case 3 : echo "Unknown or disabled provider."; break;
    case 4 : echo "Missing provider application credentials."; break;
    case 5 : echo "Authentification failed. "
    . "The user has canceled the authentication or the provider refused the connection.";
    break;
    case 6 : echo "User profile request failed. Most likely the user is not connected "
    . "to the provider and he should authenticate again.";
    $twitter->logout();
    break;
    case 7 : echo "User not connected to the provider.";
    $twitter->logout();
    break;
    case 8 : echo "Provider does not support this feature."; break;
    }
     
    // well, basically your should not display this to the end user, just give him a hint and move on..
    echo "<br /><br /><b>Original error message:</b> " . $e->getMessage();
    }