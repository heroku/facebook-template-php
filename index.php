<?php
function idx($array, $key, $default=null) {
  return array_key_exists($key, $array) ? $array[$key] : $default;
}

class FacebookApiException extends Exception {
  public function __construct($response, $curlErrorNo) {
    $this->response = $response;
    $this->curlErrorNo = $curlErrorNo;
  }
}

class Facebook {
  public function __construct($opts) {
    $this->appId = $opts['appId'];
    $this->secret = $opts['secret'];
    $this->accessToken = idx($opts, 'accessToken');
    $this->userId = idx($opts, 'userId');
    $this->session = idx($opts, 'session');
    $this->signedRequest = idx($opts, 'signedRequest', array());
    $this->maxSignedRequestAge = idx($opts, 'maxSignedRequestAge', 86400);
  }

  public function loadSignedRequest($signedRequest) {
    list($signature, $payload) = explode('.', $signedRequest, 2);
    $data = json_decode(self::base64UrlDecode($payload), true);
    if (isset($data['issued_at']) &&
        $data['issued_at'] > time() - $this->maxSignedRequestAge &&
        self::base64UrlDecode($signature) ==
          hash_hmac('sha256', $payload, $this->secret, $raw=true)) {
      $this->signedRequest = $data;
      $this->userId = idx($data, 'user_id');
      $this->accessToken = idx($data, 'oauth_token');
    }
  }

  public function api($path, $params=null, $method='GET', $domain='graph') {
    if (!$params) $params = array();
    if ($domain == 'graph')
    	$params['method'] = $method;
    if (!array_key_exists('access_token', $params) && $this->accessToken)
      $params['access_token'] = $this->accessToken;
    $ch = curl_init();
    curl_setopt_array($ch, array(
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_HTTPHEADER     => array('Expect:'),
      CURLOPT_POSTFIELDS     => http_build_query($params, null, '&'),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_URL            => "https://$domain.facebook.com$path",
      CURLOPT_USERAGENT      => 'sample-0.1',
    ));
    $result = curl_exec($ch);
    $decoded = json_decode($result, true);
    $curlErrorNo = curl_errno($ch);
    curl_close($ch);

    var_dump($curlErrorNo);
    var_dump($decoded);

    if ($curlErrorNo !== 0 || (is_array($decoded) && isset($decoded['error'])))
      throw new FacebookApiException($decoded, $curlErrorNo);
    return $decoded;
  }

  public static function base64UrlDecode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }
}

function FB() {
  $fb = new Facebook(array(
    'appId' => $_ENV['FACEBOOK_APP_ID'],
    'secret' => $_ENV['FACEBOOK_SECRET']
  ));
  header('P3P: CP=HONK'); // cookies for iframes in IE
  session_start();
  $cookie_name = 'fbs_' . $fb->appId;
  error_log("testing");
  if (isset($_POST['signed_request'])) {
    $fb->loadSignedRequest($_POST['signed_request']);
    $_SESSION['facebook_user_id'] = $fb->userId;
    $_SESSION['facebook_access_token'] = $fb->accessToken;
  } else if (isset($_COOKIE[$cookie_name])) {
  	$session = array();
    parse_str(trim(
            get_magic_quotes_gpc()
              ? stripslashes($_COOKIE[$cookie_name])
              : $_COOKIE[$cookie_name],
            '"'
          ), $session);	
     // TODO Validate Session
     $fb->userId = $session['uid'];
     $fb->accessToken = $session['access_token'];
     $fb->session = $session;
  } else {
    $fb->userId = idx($_SESSION, 'facebook_user_id');
    $fb->accessToken = idx($_SESSION, 'facebook_access_token');
  }
  return $fb;
}

$fb = FB();
$registration_plugin_url =
  'http://www.facebook.com/plugins/registration.php?' .
  http_build_query(array(
    'client_id' => $fb->appId,
    'fb_only' => 'true',
    'redirect_uri' => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
    'fields' => 'name,email'));
?>

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
  <h3>Registration for Purple Vole</h3>
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
