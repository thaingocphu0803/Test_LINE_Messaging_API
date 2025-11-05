<?php
require_once('../../config.php');
/**
 * PDO Connect to MYSQL
 */
function connect_db()
{
	$config = [
		'engine' => DB_ENGINE,
		'db_host' => DB_HOST,
		'db_port' => DB_PORT,
		'db_name' => DB_NAME,
		'db_user' => DB_USER,
		'db_pssw' => DB_PASSWORD,
	];

	$dns = get_dns($config['engine'], $config['db_host'], $config['db_port'], $config['db_name']);

	try {
		if ($dns == false) {
			throw new PDOException('DB: Failed to create the dns.', 11111);
		}
		$pdo =  new PDO($dns, $config['db_user'], $config['db_pssw']);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $pdo;
	} catch (PDOException $e) {
		throw new Exception($e->getMessage());
	}
}

/**
 * Generate DNS string for PDO connection
 */
function get_dns($engine, $host, $port, $name)
{
	return "{$engine}:" . ($name ? ("dbname={$name};") : '') . "host={$host};port={$port}";
}

/**
 * Execute a query with bind parameters
 */
function sql_bind_exec($query, $param, $pdo)
{
	try {
		$cmd = $pdo->prepare($query);

		foreach ($param as $key => $val) {
			$cmd->bindValue($key, $val);
		}

		$cmd->execute();

		return $cmd;
	} catch (PDOException $e) {
		throw new Exception($e->getMessage());
	}
}

/**
 * Output JSON response and exit
 */
function json_response($code = 0, $message = '', $data = [])
{
	$data = [
		'code' => $code,
		'message' => $message,
		'data' => $data
	];

	echo json_encode($data);
	exit();
}

/**
 * Fetch a single record from database with bound parameters.
 */
function sql_bind_fetch($query, $param , $pdo)
{	
	try {
		$cmd = $pdo->prepare($query);

		if (!empty($param)) {
			foreach ($param as $key => $val) {
				$cmd->bindValue($key, $val);
			}
		}

		$cmd->execute();

		$result = $cmd->fetch(PDO::FETCH_ASSOC);

		return $result;

	} catch (PDOException $e) {
		throw new Exception($e->getMessage());
	}
}

/**
 * Fetch all records from database (no bind parameters).
 */
function sql_fetchAll($query, $pdo)
{	
	try {
		$cmd = $pdo->prepare($query);

		$cmd->execute();

		$result = $cmd->fetchAll(PDO::FETCH_ASSOC);

		return $result;

	} catch (PDOException $e) {
		throw new Exception($e->getMessage());
	}
}

/**
 *  Write an error message into logs/error_log.txt.
 */
function write_error_log($message){
	$newMessage = date("Y/m/d H:i:s")." ".$message. "\n";
	file_put_contents('logs/error_log.txt', $newMessage, FILE_APPEND);
}

/**
 * Write received webhook events into logs/webhook_log.txt.
 */
function write_webhook_log($message){
	$newMessage = date("Y/m/d H:i:s")." event: ".$message. "\n";
	file_put_contents('logs/webhook_log.txt', $newMessage, FILE_APPEND);
}

/**
 * Send a POST request to the LINE API with optional JSON payload.
 */
function call_line_messaging_api($url, $header, $data = []){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if(!empty($data)){
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	}

	$response = curl_exec($ch);
	$error = curl_error($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if(!empty($error)){
		write_error_log("Curl Error: $error");
		return false;
	}else if($httpCode !== 200){
		write_error_log("HTTP $httpCode: $response");
		return false;
	}

	return json_decode($response, true);
}