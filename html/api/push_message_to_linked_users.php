<?php
require_once('lib.php');
require_once('enum.php');

try {

	// Only allow POST requests for security reasons
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		throw new Exception("API: The request method Incorrected.");
	}

	// Decode the incoming JSON payload
	$payload = json_decode(file_get_contents("php://input"), true);

	// Connect to the database
	$conn = connect_db();

	// SQL query: select all users who link these account with the Line application.
	$sql = get_sql_select_line_user();

	// Fetch all matching rows from the database
	$result = sql_fetchAll($sql, $conn);

	if(empty($result)){
		throw new Exception("API: There is no any linked user.");
	}

	// Extract the line_user_id values into a simple array
	$line_user_ids =  array_column($result, 'line_user_id');

	// LINE Messaging API endpoint for push messages
	$url = LineApiEndpoint::PUSH_MESSAGE->value;

	// Headers required for LINE API calls
	$header = [
		'Content-Type: application/json',
		'Authorization: Bearer ' . CHANNEL_ACCESS_TOKEN
	];

	// Get the message text from the incoming payload
	$message = $payload['message'];

	// Loop through all LINE user IDs and send the message to each
	foreach ($line_user_ids as $line_user_id) {
		// Build the JSON payload for LINE push message
		$data = get_push_message($line_user_id, $message);

		// Send the push message via LINE API
		$result = call_line_messaging_api($url, $header, $data);

		// If failed, log an error with the affected user ID
		if (!$result) {
			write_error_log("API: fail to push message to Line user: $line_user_id");
		}
	}

	// Return JSON response to indicate success
	json_response(0, 'Send message succcessfully.');
} catch (Exception $e) {

	// Catch any errors and return failure
	json_response(9999, 'Failed to send message.');
}

/**
 * SQL to select all users who link these account with the Line application.
 */
function get_sql_select_line_user()
{
	return <<<SQL
		SELECT line_user_id
		FROM m_users
		WHERE line_user_id iS NOT NULL;
	SQL;
}

/**
 * Build the LINE push message payload for a specific user
 */
function get_push_message($line_user_id, $message)
{
	return [
		'to' => $line_user_id,
		'messages' => [
			[
				'type' => 'text',
				'text' =>  $message
			]
		]
	];
}
