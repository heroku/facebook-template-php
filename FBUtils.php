<?php

/**
 * This class provides Facebook specfic utility functions that you may use
 * to build your app.
 */


require_once('AppInfo.php');
require_once('utils.php');

class FBUtils {

  /*****************************************************************************
   *
   * The content below provides some helper functions that you may wish to use as
   * you develop your app.
   *
   ****************************************************************************/

  /**
   * GETs graph.facebook.com/$target, and returns it as decoded JSON
   * To learn more about the Graph API, visit:
   *  'https://developers.facebook.com/docs/refererence/api'
   *
   * @return graph api content of $target
   */
  public static function fetchFromFBGraph($target) {
   return self::curl('https://graph.facebook.com/' . $target);
  }

  /**
   * Uses FQL (Facebook Query Language) to return the result of $query with the
   * access-token $token.  FQL is used to process more complex requests that the
   * graph API does not directly expose.  For more information, visit
      'https://developers.facebook.com/docs/reference/fql'
   *
   * @return Facebook Query result for $query
   */
  public static function fql($query, $token) {
    $query = urlencode($query);
    return self::curl("https://api.facebook.com/method/fql.query?query=$query&format=json&access_token=$token");
  }

  /**
   * Helper function
   * @return the JSON decoded results of curling $url
   */
  public static function curl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    return json_decode(curl_exec($ch), true);
  }

  /**
   * Authenticates the current viewer of the app, prompting them to login and
   * grant permissions if necessary.  For more information, check the
   * 'https://developers.facebook.com/docs/authentication/'
   *
   * @return app access token if login is successful
   */
  public static function login($redirect) {
    $app_id = AppInfo::appID();
    $app_secret = AppInfo::appSecret();
    $home = AppInfo::getHome();
    // Scope defines what permissions that we are asking the user to grant.
    // In this example, we are asking for the ability to publish stories
    // about using the app, access to what the user likes, and to be able
    // to use their pictures.  You should rewrite this scope with whatever
    // permissions your app needs.
    // See https://developers.facebook.com/docs/reference/api/permissions/
    // for a full list of permissions
    $scope = 'user_likes,user_photos,user_photo_video_tags';
    session_start();
    $code = $_REQUEST["code"];
    // If we don't have a code returned from Facebook, the first step is to get
    // that code
    if (empty($code)) {
      // CSRF protection - for more information, look at 'Security Considerations'
      // at 'https://developers.facebook.com/docs/authentication/'
      $state = md5(uniqid(rand(), TRUE));
      setcookie(
        AppInfo::appID() . '-fb-app',
        $state,
        $expires = 0,
        $path = "",
        $domain = "",
        $secure = "",
        $httponly = true); 
      // Now form the login URL that you will use to authorize your app
      $authorize_url = "https://www.facebook.com/dialog/oauth?client_id=$app_id" .
      "&redirect_uri=$home&state=" . $state . "&scope=$scope";
      // Now we redirect the user to the login page
      echo("<script> top.location.href='" . $authorize_url . "'</script>");
      return false;
    // Once we have that code, we can now request an access-token.  We check to
    // ensure that the state has remained the same.
    } else if ($_REQUEST['state'] === $_COOKIE[AppInfo::appID() . '-fb-app']) {
      $ch = curl_init("https://graph.facebook.com/oauth/access_token");
      curl_setopt($ch, CURLOPT_POSTFIELDS,
        "client_id=$app_id&redirect_uri=$home&client_secret=$app_secret" .
        "&code=$code&scope=$scope");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch);
      // Once we get a response, we then parse it to extract the access token
      parse_str($response, $params);
      $token = $params['access_token'];
      return $token;
    // In the event that the two states do not match, we return false to signify
    // that something has gone wrong during authentication
    } else {
      echo("States do not match.  CSRF?");
      return false;
    }
  }
}
