<?php
function auth ($body) {
	if (!isset($body['repo'])) {
    show_error("Repo not specified");
  }
	if (!isset($body['timeout'])) {
    show_error("Timeout not specified");
  }
	if (!isset($body['signature'])) {
    show_error("Signature not specified");
  }
	if ($body['timeout'] < time()) {
    show_error("Signature has expired");
  }

	$hash = $body['repo'] . '|' . $body['user'] . '|' . $body['timeout'];
	$signature = base64_decode($body['signature']);

	$dir = dirname(__FILE__) . '../resources/pubkeys';

	foreach (scandir($dir) as $file) {
		if (!is_file("$dir/$file")) continue;

		$key = openssl_get_publickey(file_get_contents("$dir/$file"));
		$ret = openssl_verify($hash, $signature, $key);

		if ($ret == 1) {
			return true;
		}
	}

  return false;
}
?>