TwitterV2Bridge
===============

To automatically retrieve Tweets containing potentially sensitive/age-restricted content, you'll need to acquire your own unique API Bearer token, which will be used by this Bridge to query Twitter's API v2.

Configuration
-------------

1. Make a Twitter Developer account

	- Developer Portal: https://dev.twitter.com

	- I will not detail exactly how to do this, as the specific process will likely change over time. You should easily be able to find guides using your search engine of choice.

	- A basic free developer account grants Essential access to the Twitter API v2, which should be sufficient for this bridge.

2. Create a Twitter Project and App, get Bearer Token

	- Once you have an active Twitter Developer account, sign in to the dev portal

	- Create a new Project (name doesn't matter)

	- Create an App within the Project (again, name doesn't matter)

	- Go to the **Keys and tokens** tab

	- Generate a **Bearer Token** (you don't want the API Key and Secret, or the Access Token and Secret)

3. Configure RSS-Bridge

	- In **config.ini.php** (in rss-bridge root directory) add following lines at the end:

	```
	[TwitterV2Bridge]
	twitterv2apitoken = %Bearer Token from step 2%
	```
	- If you don't have a **config.ini.php**, create one by making a copy of **config.default.ini.php**