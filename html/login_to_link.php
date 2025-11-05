<?php
// Get the linkToken parameter from the query string
$linkToken = $_GET['linkToken'];

// Define the form action URL to handle login and link the LINE account
$route = './api/login_and_link_line.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SERVICE LOGIN</title>
</head>

<style>
	.container {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.wrapper{
		border: 1px solid black;
		padding: 1em;
	}

	.form-control {
		display: flex;
		justify-content: space-between;
		gap: 10px;
		margin: 10px 0;
		align-items: center;
	}

	h3 {
		text-align: center;
	}

	.form-control > input {
		font-size: 16px;
		padding: 5px;
	}
	.login-btn {
		display: flex;
		justify-content: center;
	}
	.login-btn > button {
		padding: 5px 10px;
		font-size: 16px;
	}

</style>

<body>
	<div class="container">
		<div class="wrapper">
			<h3>LOGIN</h3>
			<form class="link-form" action="<?= $route ?>" method="POST">
				<!-- user id input -->
				<div class="form-control">
					<label for="user_id">ID</label>
					<input type="text" name="user_id" id="user_id" placeholder="Please enter ID" required>
				</div>
				<div class="form-control">
					<label for="pssw">Password</label>
					<input type="text" name="pssw" id="pssw" placeholder="Please enter password" required>
				</div>
				
				<!-- password input -->
				<input type="hidden" name="link_token" value="<?= $linkToken ?>">

				<!-- login button -->
				<div class="login-btn">
					<button type="submit">Login</button>
				</div>
			</form>
		</div>
	</div>
</body>

</html>