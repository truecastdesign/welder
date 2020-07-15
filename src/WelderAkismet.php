<?php

namespace Truecast;

class WelderAkismet
{
    var $version;

    function __construct($api_key, $blog)
    {

	  $this->api_key = $api_key;
      $this->blog = $blog;
      $this->required_keys = array('user_ip', 'user_agent');
    }



	function check_comment($post_args) {

	  $this->verify_post_args($post_args);

	  return ($this->call('comment-check', $post_args, "{$this->api_key}.rest.akismet.com/1.1") != 'false');

	}



	function submit_spam($post_args) {

	  $this->verify_post_args($post_args);

	  return ($this->call('submit-spam', $post_args, "{$this->api_key}.rest.akismet.com/1.1") != 'false');

	}



	function submit_ham($post_args) {

	  $this->verify_post_args($post_args);

	  return ($this->call('submit-ham', $post_args, "{$this->api_key}.rest.akismet.com/1.1") != 'false');

	}



	function verify_key() {

	  $sendKey = array('key' => $this->api_key);

	  return ($this->call('verify-key', $sendKey, "rest.akismet.com/1.1") != 'invalid'); 

	}



	function verify_post_args($post_args) {

	  # iterate over required keys and verify each one

	  foreach ($this->required_keys as $key)

	    if (!array_key_exists($key, $post_args))

	      die("missing required akismet key '$key'");

	}



	function call($meth, $post_args, $host) {

	  # build post URL

	  $url = "http://$host/$meth";

	  //{$this->api_key}.rest.akismet.com/1.1



	  # add blog to post args

	  $post_args['blog'] = $this->blog;



	  # init HTTP handle

	  $http = curl_init($url);



	  # init HTTP handle

	  curl_setopt($http, CURLOPT_POST, 1);

	  curl_setopt($http, CURLOPT_POSTFIELDS, $post_args);

	  curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);

	  curl_setopt($http, CURLOPT_USERAGENT, "User-Agent: truecast/1.0 | afdn_errorPage/$pluginVersion");

      # do HTTP
      $ret = curl_exec($http);



	  # check error response

	  if ($err_str = curl_error($http))

	    die("CURL Error: $err_str");



	  # close HTTP connection

	  curl_close($http);

 

	  # return result

	  return $ret;

	}
}