<?php

/**
 * Enum representing the types of messages handled by the LINE Messaging API.
 */
enum MessageType: String  {
	case MESSAGE = 'message';

	case ACCOUNT_LINK = 'accountLink';
}

/**
 * Enum defining specific message contents used within the system.
 */
enum MessageContent: String {
	case LINK_ACCOUNT = "link account";
}

/**
 * Enum containing LINE Messaging API endpoints for sending messages.
 */
enum LineApiEndpoint: String {
	// Endpoint for sending a message to a specific user (push message)
	case PUSH_MESSAGE  = 'https://api.line.me/v2/bot/message/push';
}

/**
 * Enum representing the possible results of an account link process.
 */
enum LinkResult: String {
	case OK = 'ok';
	case FAIL = 'failed';
}