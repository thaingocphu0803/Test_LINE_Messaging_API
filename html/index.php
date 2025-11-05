<?php 
	require_once('../config.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test Line Message</title>
</head>

<style>
.container, .row{
	display: flex;
	flex-direction: column;
	align-items: center;
}

.container {
	gap: 3em;
}

#btn-send-message{
	margin-top: 1em;
}
#message {
	padding: 5px;
}

</style>

<body>
	<div class="container">
		<div class="row">
			<h3 class="component-title">Line add friend</h3>
			<div class="line-it-button" data-lang="en" data-type="friend" data-env="PROD" data-lineId="<?= OFFICIAL_ACCOUNT_ID ?>" style="display: none;"></div>
		</div>

		<div class="row">
			<h3 class="component-title">Send message to linked users</h3>
			<textarea name="message" id="message" rows="5" placeholder="message..."></textarea>
			<button id="btn-send-message" type="button">Send</button>
		</div>
	</div>

	<!-- gain friend plugin -->
	<script src="https://www.line-website.com/social-plugins/js/thirdparty/loader.min.js" async="async" defer="defer"></script>
	
	<!-- send message to linked users -->
	<script>
		const sendBtn = document.getElementById('btn-send-message');

		// Add a click event listener to the Send button
		sendBtn.addEventListener('click', async function(){
			let message = document.getElementById('message').value;
			
			 // If the message is empty, do nothing
			if(!message.length) return;
			
			 // Prepare the JSON payload to send to the server
			let payload = {
				message
			}
			
			// Send a POST request to the PHP API endpoint
			let response  = await fetch('./api/push_message_to_linked_users.php',{
				method:"POST",
				headers: {"Content-Type": "application/json"},
				body: JSON.stringify(payload)
			})

			 // If the response is OK
			if(response.ok){
				let result = await response.json();

				// Display the result message in an alert popup
				window.alert(result.message);
			}
		})
	</script>
</body>

</html>