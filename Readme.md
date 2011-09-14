Facebook/Heroku sample app -- PHP
=================================

This is a sample app showing use of the Facebook Graph API, written in PHP, designed for deployment to [Heroku](http://www.heroku.com/).

Run locally
-----------

Configure Apache with a `VirtualHost` that points to the location of this code checkout on your system.

[Create an app on Facebook](https://developers.facebook.com/apps) and set the Website URL to your local VirtualHost.

Copy the App ID and Secret from the Facebook app settings page into your `VirtualHost` config, something like:

    <VirtualHost *:80>
        DocumentRoot /Users/adam/Sites/myapp
        ServerName myapp.localhost
        SetEnv FACEBOOK_APP_ID 12345
        SetEnv FACEBOOK_SECRET abcde
    </VirtualHost>

Restart Apache, and you should be able to visit your app at its local URL.

Deploy to Heroku via Facebook integration
-----------------------------------------

The easiest way to deploy is to create an app on Facebook and click Cloud Services -> Get Started, then choose PHP from the dropdown.  You can then `git clone` the resulting app from Heroku.

Deploy to Heroku directly
-------------------------

If you prefer to deploy yourself, push this code to a new Heroku app on the Cedar stack, then copy the App ID and Secret into your config vars:

    heroku create --stack cedar
    git push heroku master
    heroku config:add FACEBOOK_APP_ID=12345 FACEBOOK_SECRET=abcde

Enter the URL for your Heroku app into the Website URL section of the Facebook app settings page, hen you can visit your app on the web.

