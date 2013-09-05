# Github Receiver

* Contributors: cftp, simonwheatley
* Tested up to: 3.5
* Stable tag: 1.2
* Requires at least: 3.5

Provides an endpoint for the Github Post-Receive Webhook to ping, allowing WordPress to create a post for each Github commit.

## Installation
1. Download and unzip the plugin.
2. Copy the github-receiver directory into your plugins folder.
3. Visit your Plugins page and activate the plugin.
4. Set the Webhook for your Github repos to http://[yourdomain.com]/github-receiver

## Changelog

### 1.3 

* Accept GitLab commits

### 1.2 

* Quick fix: Stop checking the IP addresses, there's too many now

### 1.1 

Tuesday, 29 January 2013

* Stop setting the `post_date` to the commit date

### 1.0 

* Initial release.

