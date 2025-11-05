<?php
require_once('lib.php');
require_once('enum.php');

// Decode JSON payload into a PHP array
$body = file_get_contents("php://input");

// Decode JSON payload into a PHP array	
$payload = json_decode($body, true);

// Get the signature from the request header
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? null;

// Generate a hash using the channel secret to verify the request
$hash = hash_hmac('sha256', $body, CHANNEL_SECRET, true);
$expectedSignature = base64_encode($hash);

// Compare LINE’s signature with expected signature
if (!hash_equals($expectedSignature, $signature)) {
	write_error_log("Webhook: Invalid Signature.");
	http_response_code(400);
	exit();
}

// Log the received payload for debugging
write_webhook_log($body);

// Get the event type
$type =  $payload['events'][0]['type'] ?? null;

// Get the message text (if any) and normalize it
$message = strtolower(trim($payload['events'][0]['message']['text'])) ?? null;

/**
 * When a user sends the message "link account",
 * the server generates a linkToken and replies with a template message
 * containing a "Link" button for the user to link their LINE account.
 */
if (($type == MessageType::MESSAGE->value) && ($message == MessageContent::LINK_ACCOUNT->value)) {
	$line_user_id = $payload['events'][0]['source']['userId'];

	$url = get_linkToken_url($line_user_id);
	$header = [
		'Authorization: Bearer ' . CHANNEL_ACCESS_TOKEN
	];
	
	// Call LINE API to get a linkToken
	$result = call_line_messaging_api($url, $header);

	// Extract the linkToken from LINE’s response
	$link_token = $result['linkToken'] ?? null;

	// Send the link message if the link token exists.
	if ($link_token) {
		$url = LineApiEndpoint::PUSH_MESSAGE->value;
		$data = get_link_account_message(DOMAIN, $line_user_id, $link_token);

		$header = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . CHANNEL_ACCESS_TOKEN
		];

		// Send push message to user
		$result = call_line_messaging_api($url, $header, $data);

		if (!$result) {
			write_error_log("Webhook: Push message failed.");
		}
	}

}

// Get the link result if it exists
$link_result = $payload['events'][0]['link']['result'] ?? null;


/**
 * Check the link result.
 * If the linking process failed, log the error.
 */
if ($link_result == LinkResult::FAIL->value) {
	write_error_log("Webhook: Link service account to Line is failed.");
}

/**
 * When the user successfully links their LINE account,
 * update the database to associate the LINE user ID with the existing system user.
 */
if (($type == MessageType::ACCOUNT_LINK->value) && $link_result == LinkResult::OK->value) {
	try {
		// Connect to the database
		$conn = connect_db();
		
		// Nonce is a temporary token used to match the current logged-in user
		$link_nonce =  $payload['events'][0]['link']['nonce'] ?? '';

		$select_param = [
			'param' => [':line_user_id' => $link_nonce],
			'sql' => get_sql_select_user()
		];

		// Select the user matches with the nonce
		$result = sql_bind_fetch($select_param['sql'], $select_param['param'], $conn);

		// logging error if user is not found.
		if (!$result) {
			throw new Exception('DB: there is no user match with nonce.');
		}

		// Get user ID and LINE user ID
		$line_user_id = $payload['events'][0]['source']['userId'];
		$user_id = $result['user_id'];

		$update_param = [
			'param' => [
				':line_user_id' => $line_user_id,
				':user_id' => $user_id
			],
			'sql' => get_sql_update_user()
		];
		
		// Begin a database transaction to ensure data consistency
		$conn->beginTransaction();

		// Update the user's LINE ID in the database
		sql_bind_exec($update_param['sql'], $update_param['param'], $conn);
    	
		// Commit the transaction if the update is successful
		$conn->commit();
	} catch (Exception $e) {
		// Roll back the transaction if any error occurs during the process
		$conn->rollBack();

		// Write the error message to a log file
		write_error_log($e->getMessage());
	}
}

// Respond with HTTP 200 OK to let LINE know the webhook was processed successfully
http_response_code(200);


/**
 * SQL to select user based on line_user_id, in this case is nonce.
 */
function get_sql_select_user()
{
	return <<<SQL
		SELECT
			user_id
		FROM
			m_users
		WHERE
			line_user_id = :line_user_id
	SQL;
}

/**
 * SQL to update user's line_user_id
 */
function get_sql_update_user()
{
	return <<<SQL
		UPDATE
			m_users
		SET 
			line_user_id = :line_user_id 
		WHERE 
			user_id = :user_id;
	SQL;
}

/**
 * Build the LINE template message for account linking
 */
function get_link_account_message($domain, $line_user_id, $link_token)
{
	$text = "Click here to link your account!";
	$label = "Link";
	$url = "$domain/login_to_link.php?linkToken=$link_token";

	return [
		'to' => $line_user_id,
		'messages' => [
			[
				'type' => "template",
				'altText' => $text,
				'template' => [
					"type" => "buttons",
					"text" =>  $text,
					'actions' => [
						[
							"type" => "uri",
							"label" => $label,
							"uri" => $url
						]
					]
				]
			]
		]
	];
}

/**
 * Generate the LINE API URL to get a linkToken for a user
 */
function get_linkToken_url($line_user_id)
{
	return "https://api.line.me/v2/bot/user/$line_user_id/linkToken";
}
