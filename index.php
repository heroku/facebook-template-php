
<!-- 
 * A simple Facebook PHP example for Sample App
 *
 * - Majority of PHP code from: https://gist.github.com/818006
 *		- (written by Naitik Shah <n@daaku.org>
 * - Additions for Sample App written by Aryeh Selekman <aryeh@selekman.com>
-->

<html>
<head>
<style type="text/css">

body {
	max-width: 630px;
	padding: 10px;
	border-style: solid;
	border-width: 1px;
	height: 500px;
	width: 630px;
	font-family: sans-serif;
}

</style>
</head>
<body>
<div id="fb-root"></div>

    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId   : <?php echo $fb->appId; ?>,
          status  : true, // check login status
          cookie  : true, // enable cookies to allow the server to access the session
          xfbml   : true // parse XFBML
        });

        // whenever the user logs in, we refresh the page
        FB.Event.subscribe('auth.login', function() {
          window.location.reload();
        });
      };

      (function() {
        var e = document.createElement('script');
        e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
        e.async = true;
        document.getElementById('fb-root').appendChild(e);
      }());
    </script>

<?php if (!$fb->userId) { ?>
  <h3>Registration</h3>
  <iframe src="<?php echo $registration_plugin_url; ?>"
          scrolling="auto"
          frameborder="no"
          style="border:none"
          allowTransparency="true"
          width="610"
          height="330">
  </iframe>
<?php } else { 
	$me = 	$fb->api('/me', array('fields' => 'first_name'));
	$firstName = $me['first_name'];
	$appPath = '/' . $fb->appId;
	$appData = $fb->api($appPath);
	$appName = $appData['name'];
?>
<h3>Welcome to <?php echo $appName; ?>, <?php echo $firstName; ?>!</h3>
<h4>Get Started with The Facebook <a href='http://graph.facebook.com'>Graph API</a></h4>
<p> Friends using <?php echo $appName; ?>:</p>
<?php 
$friends = $fb->api('/method/fql.query', 
		array('query' => 'SELECT uid, name, is_app_user, pic_square FROM user WHERE uid in (SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1', 
		'format' => 'json'), 
		'GET', 
		'api');
foreach ($friends as $friend) {
		echo '<img src="' . $friend['pic_square'] .'"</img> ' . $friend['name'] . '<br/>';
	}
} ?>
</body>
</html>
