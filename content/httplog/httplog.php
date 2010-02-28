<?php 
	include('smartmeter.conf');
	// time=<timestamp>
	// port=P{ABCD}{0-7}
	// uuid=<01234567-89AB-CDEF...>
	// 
	if(!preg_match('/^([timeportuuidPA-D0-9\-a-fA-F\=\&]*)$/',$_SERVER['QUERY_STRING']))
		die("");

	function make_a_difference($system_date, $sent_date) {
		// erwartet beide Daten als Unix-Epoch (Sekunden seit 1970-01-01 00:00:00)
		$result = $system_date - $sent_date;

		return $result;
	}

	$controllertime = $_GET["time"];
	$port = $_GET["port"];
	$controller = $_GET["uuid"];

	// all required parameters present?
	if ($controller == "") {
		echo "Parameter uuid is required - aborting";
		exit;
	} 
	if ($controllertime == "") {
		echo "Parameter time is required - aborting";
		exit;
	} 
	if ($port == "") {
		echo "Parameter port is required - aborting";
		exit;
	} 

	$conn = pg_connect("dbname=smartmeter host=localhost user=".$smlogger_user." password=".$smlogger_password);

	$result = pg_query($conn, "SELECT id from public.channels where uuid = '$controller' and channel = '$port'");
	$line = pg_fetch_array($result);
	// die DB-interne ID des Controllers steht nun in $line['id']
	$interne_id = $line['id'];
	// falls diese aber leer ist (= noch nicht in der DB existiert), ebenfalls abbrechen
	if ($interne_id == '') {
		echo "uuid has not approved yet - Aborting!";
		exit;
	}

	if (!$conn) {
		// couldn't connect
		echo "could not connect localhost";
		exit;
	}
	$time_delta = make_a_difference($controllertime, microtime(true));
	$result = pg_query($conn, "insert into public.pulses (servertime, controllertime, time_delta, channel, id) VALUES (NOW(), to_timestamp('$controllertime'), $time_delta, '$port', '$interne_id' )");
	// optional passthru to volkszaehler.org
	if ($httplog_passthru=="yes") {
		fopen("http://volkszaehler.org/httplog/httplog.php?time=".$controllertime."&uuid=".$uuid."&port=".$port."&passthru=yes", "r");
	}
?>
