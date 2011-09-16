<?php

/*****************************************************************************
 *
 * This script provides non Facebook specific utility functions that you may
 * wish to use in your app.  These functions involve 'sanitizing' output to
 * prevent XSS (Cross-site scripting) vulnerabilities.  By using these
 * functions, you remove a certain type of malicious content that could
 * be displayed to your users.  For a more robust and comprehensive solution,
 * Facebook has open sourced XHP.  XHP is a PHP extension which makes your
 * front-end code easier to understand and help you avoid cross-site
 * scripting attacks.  Learn more at 'https://github.com/facebook/xhp/wiki/'.
 *
 ****************************************************************************/


/**
 * @return the value at $index in $array or $default if $index is not set.
 *         By default, the value returned will be sanitized to prevent
 *         XSS attacks, however if $sanitize is set to false, the raw
 *         values will be returned
 */
function idx($array, $index, $default = null, $sanitize = true) {
  if (array_key_exists($index, $array)) {
    $value = $array[$index];
  } else {
    $value = $default;
  }
  if ($sanitize) {
    return htmlentities($value);
  } else {
    return $value;
  }
}

/**
 * This will echo $value after sanitizing any html tags in it.
 * This is for preventing XSS attacks.  You should use echoSafe whenever
 * you are echoing content that could possibly be malicious (i.e.
 * content from an external request). This does not sanitize javascript
 * or attributes
 */
function echoEntity($value) {
  echo(htmlentities($value));
}

/**
 * @return $value if $value is numeric, else null.  Use this to assert that
 *         a value (like a user id) is a number.
 */
function assertNumeric($value) {
  if (is_numeric($value)) {
    return $value;
  } else {
    return null;
  }
}

