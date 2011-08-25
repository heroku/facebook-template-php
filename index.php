<?php
// Copyright 2004-present Facebook. All Rights Reserved.


/**
 * This sample app is provided to kickstart your experience using Facebook's
 * resources for developers.  This sample app provides examples of several
 * key concepts, including authentication, the Graph API, and FQL (Facebook
 * Query Language). Please visit the docs at 'developers.facebook.com/docs'
 * to learn more about the resources available to you
 */

// Provides access to Facebook specific utilities defined in 'FBUtils.php'
require_once('FBUtils.php');
// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');
// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');

/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

// Log the user in, and get their access token
$token = FBUtils::login(AppInfo::getHome());
if ($token) {

  // Fetch the viewer's basic information, using the token just provided
  $basic = FBUtils::fetchFromFBGraph("me?access_token=$token");
  $my_id = assertNumeric(idx($basic, 'id'));

  // Fetch the basic info of the app that they are using
  $app_id = AppInfo::appID();
  $app_info = FBUtils::fetchFromFBGraph("$app_id?access_token=$token");

  // This fetches some things that you like . 'limit=*" only returns * values.
  // To see the format of the data you are retrieving, use the "Graph API
  // Explorer" which is at https://developers.facebook.com/tools/explorer/
  $likes = array_values(
    idx(FBUtils::fetchFromFBGraph("me/likes?access_token=$token&limit=11"), 'data')
  );

  // This fetches 5 of your friends.
  $friends = array_values(
    idx(FBUtils::fetchFromFBGraph("me/friends?access_token=$token&limit=5"), 'data')
  );

  // And this returns 2 of your photos.
  $photos = array_values(
    idx($raw = FBUtils::fetchFromFBGraph("me/photos?access_token=$token&limit=2"), 'data')
  );

  // Here is an example of a FQL call that fetches all of your friends that are
  // using this app
  $app_using_friends = FBUtils::fql(
    "SELECT uid, name, is_app_user, pic_square FROM user WHERE uid in (SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1",
    $token
  );

  // This formats our home URL so that we can pass it as a web request
  $encoded_home = urlencode(AppInfo::getHome());

  // These two URL's are links to dialogs that you will be able to use to share
  // your app with others.  Look under the documentation for dialogs at
  // developers.facebook.com for more information
  $send_url = "https://www.facebook.com/dialog/send?redirect_uri=$encoded_home&display=page&app_id=$app_id&link=$encoded_home";
  $post_to_wall_url = "https://www.facebook.com/dialog/feed?redirect_uri=$encoded_home&display=page&app_id=$app_id";
} else {
  // Stop running if we did not get a valid response from logging in
  exit("Invalid credentials");
}
?>

<!-- This following code is responsible for rendering the HTML   -->
<!-- content on the page.  Here we use the information generated -->
<!-- in the above requests to display content that is personal   -->
<!-- to whomever views the page.  You would rewrite this content -->
<!-- with your own HTML content.  Be sure that you sanitize any  -->
<!-- content that you will be displaying to the user.  idx() by  -->
<!-- default will remove any html tags from the value being      -->
<!-- and echoEntity() will echo the sanitized content.  Both of  -->
<!-- these functions are located and documented in 'utils.php'.  -->
<html>
  <head>
    <!-- We get the name of the app out of the information fetched -->
    <title><?php echo(idx($app_info, 'name')) ?></title>
    <link type="text/css" rel="stylesheet" href="style.css">
  </head>
  <body>
  <div class="content">
    <!--  Display the app name and user's name from the data fetched -->
    <h2>
      Welcome to
      <?php echo(idx($app_info, 'name') . ', ' . idx($basic, 'name') . '.'); ?>
    </h2>
    <table cellspacing="30">
      <tr>
        <td valign="top">
          <h3> Your Picture: </h3>
          <!-- By passing a valid access token here, we are able to display -->
          <!-- the user's images without having to download or prepare -->
          <!-- them ahead of time -->
          <a
            href="#"
            onclick="window.open('http://www.facebook.com/<?php echo($my_id) ?>')">
            <img
              src="https://graph.facebook.com/me/picture?type=large&access_token=<?php echoEntity($token) ?>"
              width="200"
            />
          </a>
        </td>
        <td valign="top">
          <h3> Friends using this app: </h3>
          <table border="0">
            <?php
              foreach ($app_using_friends as $auf) {
                // Extract the pieces of info we need from the requests above
                $uid = assertNumeric(idx($auf, 'uid'));
                $pic = idx($auf, 'pic_square');
                $name = idx($auf, 'name');
                echo('
                  <tr>
                    <td>
                      <a
                        href="#"
                        onclick="window.open(\'http://www.facebook.com/' . $uid . '\')">
                        <img src=' .$pic . '/>
                      </a>
                    </td>
                    <td>' . $name . '</td>
                  <tr>');
              }
            ?>
          </table>
        </td>
        <td valign="top">
          <h3> Share your app: </h3>
          <p>
            <!-- Here we use the link for the 'Send Dialog' from earlier-->
            <a href="#" onclick="top.location.href = '<?php echo($send_url) ?>'">
              Send your app to your friends
            </a>
          </p>
          <p>
            <!-- This displays the other dialog link, which allows users -->
            <!-- to share this app on their wall -->
            <a
              href="#"
              onclick="top.location.href = '<?php echo($post_to_wall_url) ?>'">
              Post to your wall
            </a>
          </p>
        </td>
      </tr>
      <tr>
        <td valign="top">
          <h3> Recent Photos: </h3>
          <?php
            foreach ($photos as $photo) {
              // Extract the pieces of info we need from the requests above
              $src = idx($photo, 'source');
              $id = assertNumeric(idx($photo, 'id'));

              // Here we link each photo we display to it's location on Facebook
              echo('
                <p>
                  <a
                    href="#"
                    onclick="window.open(\'http://www.facebook.com/' .$id . '\')">
                    <img src="' .$src . '" width="200" />
                  </a>
                </p>'
              );
            }
          ?>
        </td>
        <td valign="top">
          <h3> A few of your friends: </h3>
          <table border="0">
            <?php
              foreach ($friends as $friend) {
                // Extract the pieces of info we need from the requests above
                $id = assertNumeric(idx($friend, 'id'));
                $name = idx($friend, 'name');
                // Here we link each friend we display to their profile
                echo('
                  <tr>
                    <td>
                      <a
                        href="#"
                        onclick="window.open(\'http://www.facebook.com/' . $id . '\')">
                        <img src="https://graph.facebook.com/' . $id . '/picture"/>
                      </a>
                    </td>
                    <td>' . $name . ' </td>
                  <tr>'
                );
              }
            ?>
          </table>
        </td>
        <td valign="top">
          <h3> Things you like: </h3>
          <ul>
            <?php
              foreach ($likes as $like) {
                // Extract the pieces of info we need from the requests above
                $id = assertNumeric(idx($like, 'id'));
                $item = idx($like, 'name');
                // This display's the object that the user liked as a link to
                // that object's page.
                echo('
                  <li>
                    <a
                      href="#"
                      onclick="window.open(\'http://www.facebook.com/' .$id .'\')">' .
                      $item .
                    '</a>
                  </li>'
                );
              }
            ?>
          </ul>
        </td>
      </tr>
    <table>
  </div>
  </body>
</html>
