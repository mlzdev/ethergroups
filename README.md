Etherpad Lite Pro
========================

This is a standalone version for Etherpad Lite implemented with the symfony2 framework.
It requires LDAP for user authentication.

Installation
----------------------------------

You need an etherpadlite server to use this. (Recommended: v1.2.7)

1. Install `apache2-prefork-dev, mysql, php5, php5-mysql, php5-intl, php5-ldap`

2. Configure apache2:
		
		mods_available/rewrite.load -> mods_enabled (symlink)
		
	In you site config (e.g. sites_available/eplitepro):
	
		DocumentRoot /path/to/symfony/web/
		AllowOverride FileInfo
		
3. Configure Nginx (If you want to use this on the same server, where etherpadlite is running)  

		apache2 -> port 8080
		nginx -> proxy_pass to 8080
		
	[How to configure nginx to proxy vhosts to apache](http://blog.ludovf.net/configure-nginx-to-proxy-virtual-hosts-to-apache/ "Title")

4. Checkout this repository with `git clone`.

5. Execute the `check.php` script from the command line:
		
		php app/check.php

	Access the `config.php` script from a browser:

    	http://localhost/path/to/symfony/app/web/config.php

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


2) Configuration
-------------------------------------
For automatic removal of in ldap deleted users, you have to add following command to e.g. Cron:
`php /path/to/symfony/app/console huberlin:ldap`