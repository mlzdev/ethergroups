Etherpad Lite Pro
========================

This is a standalone version for Etherpad Lite implemented with the symfony2 framework.
It requires LDAP for user authentication.

Prerequirement
--------------
You need an etherpad-lite server, which is running on at least the same 2nd-level-domain as your moodle server.
[Infos & Download](https://github.com/ether/etherpad-lite)

We recommend to use the etherpad-lite version 1.2.7

It's also recommended to use the latest stable release of nodejs
(http://nodejs.org/)  
*we are using nodejs 0.6.12, installed over apt-get for our productive server. But we test new ep-lite versions always with this node version, before updating productive*  
PLEASE NOTE: New versions of etherpad-lite don't support this version anymore

Installation
----------------------------------

1. Install `apache2-prefork-dev, mysql, php5, php5-mysql, php5-intl, php5-ldap`

2. Configure apache2:
		
		a2enmod rewrite
		
	In your site config (e.g. sites_available/eplitepro):
	
		DocumentRoot /path/to/eplitepro/web/
		AllowOverride FileInfo
		
3. Configure Nginx (If you want to use this on the same server, where etherpadlite is running)  [^1]

	* apache2 -> port `8080`
	* etherpadlite -> port `9001`
	* nginx `/` -> proxy_pass to `8080`
	* nginx `/eplite` -> proxy_pass to `9001`
		
	[How to configure nginx to proxy vhosts to apache](http://blog.ludovf.net/configure-nginx-to-proxy-virtual-hosts-to-apache/ "Title")

4. Checkout this repository with `git clone`.

5. Execute the `check.php` script from the command line:
		
		php app/check.php

	Access the `config.php` script from a browser:

    	http://sub.domain.tld/config.php

	If you get any warnings or recommendations, fix them before moving on.

6. Copy `app/config/parameters.yml.dist` to `app/config/parameters.yml` and modify it, to fit you installation

7. Change dir permissions of `app/cache`, `app/logs` & `web/uploads` to your webservers user:group

8. Install vendors with `php composer.phar install`

9. Create database with `php app/console doctrine:schema:create`

10. Clear the cache:  
	For debug mode: `php app/console cache:clear`  
	For productive mode: `php app/console cache:clear --env=prod`  
	You maybe have to renew the dir permissions for the `app/cache` folder after this

11. If you want to use the productive mode you have to change `app_dev.php` to `app.php` in the file `web/.htaccess`


Configuration
-------------------------------------
For automatic removal of in ldap deleted users, you have to add following command to e.g. Cron:
`php /path/to/eplitepro/app/console huberlin:ldap`

You can edit the language strings here:
	
	/path/to/eplitepro/src/HUBerlin/EPLiteProBundle/Resources/translations/
	
	
[^1]: If you have one server for eplitepro (with apache) and another with etherpadlite (with nginx e.g.), there is no problem, but when you want them both on one server (with the same port) you can configure the nginx as a reverse proxy for both.  E.g. for the etherpadlite server you redirect `/eplite`to port 9001 and everything else to port 8080, where apache is waiting. 