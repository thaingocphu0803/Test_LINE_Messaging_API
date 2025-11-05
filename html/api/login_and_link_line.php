<?php
require_once('lib.php');

try {

    if($_SERVER['REQUEST_METHOD'] !== "POST"){
        throw new Exception('API: The request method is incorrect.');
    }
    
    // Connect to the database
    $conn = connect_db();

    // Get user credentials and link token from POST request
    $user_id = $_POST['user_id'];
    $pssw = $_POST['pssw'];
    $link_token = $_POST['link_token'];
 
    // Hash the plain-text password using SHA-256 before comparison
    $hash_pssw = hash('sha256', $pssw);

    // Validate that the linkToken parameter is provided
    if(empty($link_token)){
        throw new Exception('API: linkToken is not exists.');
    }
      
    // Prepare SQL statement and parameters to retrieve user authentication info
    $auth_param = [
        'param' => [':user_id' => $user_id],
        'sql' => get_sql_select_auth()
    ];

    // Execute SQL query to get the user's stored credentials
    $auth = sql_bind_fetch($auth_param['sql'], $auth_param['param'], $conn);

    // Verify user credentials
    if(!$auth || !hash_equals($auth['pssw'], $hash_pssw)){
        throw new Exception('API: Incorrect user ID or password.');
    }

    //Generate a random nonce that will be used later to verify the account link
    $nonce = base64_encode(random_bytes(16));

    //Prepare SQL statement and bound parameters
    $param = [
        'param' => [
            ':line_user_id' => $nonce, // temporarily store the nonce in line_user_id
            ':user_id' => $user_id
        ], 

        'sql' => get_sql_update_line_user_id()
    ];

    // Begin a database transaction to ensure data consistency
    $conn->beginTransaction();

    // Execute SQL to update the user 
    sql_bind_exec($param['sql'], $param['param'], $conn);

    // Commit the transaction if the update is successful
    $conn->commit();

    // Build the LINE account link URL
    $url = "https://access.line.me/dialog/bot/accountLink?linkToken=$link_token&nonce=$nonce";

    //  Redirect the user to the LINE account link confirmation page
    header("Location: $url");
} catch (Exception $e) {
    // Roll back the transaction if any error occurs during the process
    $conn->rollBack();

    // Write the error message to a log file
    write_error_log($e->getMessage());

    // Display an error message to the browser
    echo "Redirect to link Account is failed...";
}

/**
 *  Returns the SQL statement used to temporarily update the line_user_id field with the nonce.
 */
function get_sql_update_line_user_id(){
    return <<<SQL
        UPDATE 
            m_users
        SET 
            line_user_id = :line_user_id
        WHERE
            user_id = :user_id
    SQL;
}

/**
 *  Returns the SQL statement used to get the user's stored credentials.
 */
function get_sql_select_auth(){
    return <<<SQL
        SELECT
            pssw
        FROM
            m_users
        WHERE
            user_id = :user_id
        LIMIT 1;
    SQL;
}