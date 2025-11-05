Author: Thai Ngoc Phu

Contact: thaingocphu0803@gmail.com

------------

# I. Requirement

- Ubuntu 22.04

- MySQL 8.0

- PHP 8.1

- Ngrok

- Messaging API (LINE Platform)

**Note:** Make sure the `logs` folder in the application is writable by the web server.

  
# II. LINE Messaging API
### 1. Create Account

The LINE Platform allows you to create a business account or upgrade an individual account to a business account. [LINE Bussiness ID](https://account.line.biz/login)

### 2. Create Messaging API Channel

- Step 1: Access the [Line Official Account Manager](https://manager.line.biz/)
- Step 2: Select the specific account you want to use from the account list.
- Step 3: In the account control panel, go to **Settings** -> **Messaging API** -> **Enable Messaging API**.
- Step 4:Enter a Channel Name, then click **Agree** → **OK** (You can skip the Privacy Policy and Terms of Use sections.)

**Result**: After enabling the Messaging API, a new Messaging API Channel will be created and linked to your LINE Official Account.

# III. Setting Up
### 1. Install Ngrok

**Note:**
- To work with the LINE Messaging API, a webhook must be set up to handle events sent from the LINE platform (such as message, follow, or postback events). However, the webhook URL must use HTTPS and must have an SSL/TLS certificate issued by a trusted Certificate Authority (CA).
- Since LINE’s webhook verification process does not accept self-signed certificates, it is not possible to directly use a local development server with HTTPS. Therefore, during testing or development, ngrok is used to expose the local server over a public HTTPS URL. This allows the LINE platform to successfully send webhook events to your local environment for testing and debugging purposes.

----
##### Log in to Ngrok:
Go to the [Ngrok login page](https://dashboard.ngrok.com/login).

##### Install Ngrok: 
Run the following command to install Ngrok on Ubuntu:

```cmd
sudo curl -sSL https://ngrok-agent.s3.amazonaws.com/ngrok.asc \
  | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null \
  && echo "deb https://ngrok-agent.s3.amazonaws.com bookworm main" \
  | sudo tee /etc/apt/sources.list.d/ngrok.list \
  && sudo apt update \
  && sudo apt install ngrok
```
##### Get the AuthToken:
After logging in, open your Dashboard and click on Your Authtoken. Copy the displayed token.

##### Add the AuthToken to the Ngrok configuration:
Run the following command to register your token:

```cmd
ngrok config add-authtoken {AuthToken}
```
##### Expose your local application via HTTPS:
Run Ngrok by specifying the port your application is running on:
```cmd
ngrok http {application port}
```

##### Result:
Ngrok will generate a public HTTPS URL that maps to your local application port.

### 2. Set Webhook URL

##### Access the LINE Developers Console:
Go to the [LINE Developers Console](https://developers.line.biz/console/)

##### Navigate to the Messaging API Channel:
In the console, go to **Provider**->Your Provider Name-> your Message API channel to access your Messaging API settings.

##### Open the Webhook Settings:
Inside the Messaging API Channel, scroll down to the Messaging API section, then open Webhook settings and click **Edit**.

##### Configure the Webhook URL:

- In the edit form, enter the webhook URL using the domain provided by Ngrok:
```cmd
https://{{ngrok_domain}}/api/webhook.php
```
- Click **Update** to save the webhook URL.
- Then, click **Verify** to check whether the URL is accessible.
  
**Result**: If the connection is successful, a “Success” message will be displayed.

##### Enable the Webhook:

Turn on the **Use webhook** option to start receiving events from the LINE platform through this webhook.

##### Optional: Enable Webhook Redelivery
You can also enable the Webhook redelivery option so that the LINE platform will automatically resend events if the webhook delivery fails.

### 3. Get Official Account ID

**Note:**
- The LINE Messaging API can only send messages to users who have added the official account as a friend.
- To save implementation time during development, the Add Friend plugin provided by the LINE Platform is used. This plugin allows users to easily add the official account as a friend directly from within the application.
- To use this plugin, you must obtain the Official Account ID and include it in the plugin configuration so that it links correctly to your official account.

---

##### Access the LINE Developers Console:
Go to the [LINE Developers Console](https://developers.line.biz/console/)

##### Navigate to the Messaging API Channel:
In the console, go to **Provider**->Your Provider Name-> your Message API channel to access your Messaging API settings.

##### Copy the bot's basic ID:
Inside the Messaging API Channel, scroll down to the Messaging API section, then **copy** the **Bot basic ID**

### 4. Get Channel Secret

**Note:**
- In the LINE Platform, the channel secret is used as a key to generate an HMAC-SHA256 signature based on the webhook request body.
- This signature is used to verify the webhook signature included in the webhook header.

---

##### Access the LINE Developers Console:
Go to the [LINE Developers Console](https://developers.line.biz/console/)

##### Navigate to the Messaging API Channel:
In the console, go to **Provider**->Your Provider Name-> your Message API channel to access your Messaging API settings.

##### Copy the channel secret:
Inside the Messaging API Channel, scroll down to the Basic settings section. Then **copy** the **Channel secret**.

### 5. Get Channel Access Token

**Note:**
- In the LINE Platform, the channel access token is included in the request header when calling the Messaging API.
- It is used to verify that the request is authorized and that the user has permission to access and use the corresponding channel.

---

##### Access the LINE Developers Console:
Go to the [LINE Developers Console](https://developers.line.biz/console/)

##### Navigate to the Messaging API Channel:
In the console, go to **Provider**->Your Provider Name-> your Message API channel to access your Messaging API settings.

##### Copy the channel secret:
Inside the Messaging API Channel, scroll down to the Messaging API section. Click **Issue** to generate a new **Channel Access Token**, then **copy** the token.

##### 1.2. Setting up project:
- Step 1: use the command line to clone the repository with the following command:

```cmd
git clone https://github.com/thaingocphu0803/Test_LINE_Messaging_API.git .
```

- Step 2: Execute the **db_for_test.sql** in the source code to your database.

- Step 3: Rename the config.php.example file to config.php, then add the following constants:

| Name  | Purpose  |
| ------------ | ------------ |
|OFFICIAL_ACCOUNT_ID|The official account ID obtained from the [Get Official Account ID](#get-official-account-id) section|
|DOMAIN|The domain generated during the [Install Ngrok](#install-ngrok) section|
|CHANNEL_SECRET|The Messaging API channel secret obtained from the [Get Channel Secret](#get-channel-secret) section|
|CHANNEL_ACCESS_TOKEN|The Messaging API channel access token obtained from the [Get Channel Access Token](#get-channel-access-token) section|
|DB_ENGINE|The database engine being used|
|DB_HOST|The host address where your database is running|
|DB_PORT|The port number where your database service is running|
|DB_NAME|The name of your database|
|DB_USER|The username used to connect to the database|
|DB_PASSWORD|The password used to connect to the database|

# IV. Guide To Use

### 1. Add a friend with the OA

- Step 1: Click the **Add Friend** button in the application.
- Step 2: In the Add Friend modal provided by LINE, click **Add Friend** again. (If you are not logged in, you will be prompted to log in first.)

### 2. Linking account

**Note:** The password stored in the database is “111111”, which is hashed using the SHA-256 algorithm.

---

- Step 1: In the LINE app, send a message with the content **“link account”** to your service’s official account. After sending the message, your service’s official account will reply with a message containing a link button to connect your user account with LINE. Click the **Link Account** button to proceed.
- Step 2: After the user taps the Link Account button, they will be redirected to the login page to enter their service credentials and click **Login** to verify their identity. If the authentication is successful, the user will then be redirected to the LINE account linking page to complete the linking process.
- Step 3: Once the linking process is completed, you will be redirected to a success page confirming that your LINE account has been successfully linked.

### 3. Send a message to the linked users

- Step 1: In the application, enter the message, then click the **send** button to send the message to all users who link their service account with LINE.
- Step 2: The message will be sent to the LINE Application by your service official account.


