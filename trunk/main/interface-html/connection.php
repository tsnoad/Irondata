<?php

function connect($message) {
	$response = '';
	$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($sock) {
		socket_connect($sock,"localhost", 2727);
		socket_send($sock, $message, 131072, 0);
		socket_recv($sock, &$response, 131072, 0);
	}
	return $response;
}

?>
