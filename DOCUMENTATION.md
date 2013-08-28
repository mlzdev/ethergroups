Etherpad Lite Pro documentation for administrators
======

Written by *Timo Welde*  
<timo.welde@hu-berlin.de>

## Table of contents
1. [Overview](#overview)
	* [Features](#features)
	* [Why symfony2?](#symfony2)
	* [Server architecture](#server-architecture)
2. Installation
	* [of Etherpad Lite](#install)
	* [of Etherpad Lite Pro](#installpro)
3. Configuration
	* [of Etherpad Lite](#config)
	* [of Etherpad Lite Pro](#configpro)
4. [Administration](#admin)
5. [Customizing](#customizing)
6. [Components & Licenses](#licenses)


## [Overview](id:overview)
Etherpad Lite is a program which allows users to do collaborative writing. For Moodle (a learning managment system) the HU developed a plugin, which is actively used.  
After it was established, there was a demand to use this editor without the overload of Moodle, so we developed a program, which provides simple ldap authentication and group managment.

### [Features](id:features)
* ldap authentication
* distraction free writing by hiding header and sidebar
* multilingual
* group managment
	* make new group
	* open group
 	* rename it
	* add/remove pads
	* add members via ldap to groups
	* add/remove a group picture
	* sign out of group
		* if you're the last one, delete this group and all it's pads
* pads
	* open
	* remove
	* make public
		* add/remove password
		* show password to group members
* members
	* have to agree a policy after first login
	* get an email, when someone adds them to a group
	* can decide after they logged in, if they want to be in a particualr group, or not


### [Why symfony2?](id:symfony2)
We used the symfony2 framework in the long-term version 2.3.x because it relies on the MVC (Model-View-Controller) pattern, so it is easier for another team to do further development on this software. It also has a big community, so it is easy to get help.  
The framework is under constantly development. With this LTS version we get security updates until May 2016.

### [Server architecture](id:server-architecture)
As server architecture, we are using `apache2` as webserver for this software, because it's well known, secure and supports symfony2 very good.  
To run this software next to etherpad lite on the same server and on the same port (so there are no firewall issues), we decided to use another webserver called `nginx` as a reverse proxy. The advantages of this webserver is, that it is lightweight, fast and that it is easy to setup as a reverse proxy. The directory `/eplite` goes to etherpad lite and all other are going to the apache server.

## Installation
### [of Etherpad Lite](id:install)
You need an etherpad-lite server, which is running on at least the same 2nd-level-domain as your eplitepro server.
[Infos & Download](https://github.com/ether/etherpad-lite)

We recommend to use the etherpad-lite version 1.2.7

It's also recommended to use the latest stable release of nodejs
(http://nodejs.org/)  
*we are using nodejs 0.6.12, installed over apt-get for our productive server. But we test new ep-lite versions always with this node version, before updating productive*  
PLEASE NOTE: New versions of etherpad-lite don't support this version anymore

#### Working ep-lite installation
- Ubuntu 12.04
- apt-get nodejs, npm, git, nginx, abiword, make, g++
- etherpad-lite from git (v1.2.7)
- ep-lite settings.json:
	-	"requireSession":false
	-	"editOnly":true
	-	"abiword": "/usr/bin/abiword"
- upstart script
- logrotate

### [of Etherpad Lite Pro](id:installpro)

1. Install `apache2-prefork-dev, mysql, php5, php5-mysql, php5-intl, php5-ldap`

2. Configure apache2:
		
		a2enmod rewrite
		
	In your site config (e.g. sites_available/eplitepro):
	
		DocumentRoot /path/to/eplitepro/web/
		AllowOverride FileInfo
		
3. Configure Nginx (If you want to use this on the same server, where etherpadlite is running)  [Tell me more](#why-nginx-apache)
	* apache2 -> port `8080`
	* etherpadlite -> port `9001`
	* nginx directory `/` -> proxy_pass to `8080`
	* nginx directory `/eplite` -> proxy_pass to `9001`
	
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

10. [Clear the cache:](id:cache-clear)  
	For debug mode: `php app/console cache:clear`  
	For productive mode: `php app/console cache:clear --env=prod`  
	You maybe have to renew the dir permissions for the `app/cache` folder after this

11. If you want to use the productive mode you have to change `app_dev.php` to `app.php` in the file `web/.htaccess`

***

###[Why nginx + apache?](id:why-nginx-apache)
If you have one server for eplitepro (with apache) and a seperate with etherpadlite (with nginx e.g.), there is no problem.  
When you want them both on one machine (on the same port, to prevent firewall issues) you can configure nginx as a reverse proxy for both.  
E.g. for the etherpadlite server you redirect `/eplite`to port 9001 and everything else to port 8080, where apache (with eplitepro) is waiting. 

---

## Configuration
### [of Etherpad Lite](id:config)
Settings file: `/path/to/eplite/settings.json`  
It's strongly recommended to use a dedicated database (e.g. mysql) for a productive environment  
We also recommend setting these settings, if you want to use it only with Etherpad Lite Pro:

	"requireSession" : true,
	"editOnly" : true,

### [of Etherpad Lite Pro](id:configpro)
#### Automatic removal of missing ldap users
For automatic removal of in ldap deleted users, you have to add following command to e.g. Cron:
`php /path/to/eplitepro/app/console huberlin:ldap`

## [Administration](id:admin)

### URL Schema
The URL Schema for public pads is: `http[s]://[www].[sub].[domain].[tld]/[yourDirectory]/p/[groupID]$[padID]`

### Editing language strings
You can edit the language strings here:
	
	/path/to/eplitepro/src/HUBerlin/EPLiteProBundle/Resources/translations/

### Updating symfony2 vendors
To update the symfony2 framework, you have to change into the base directory of this application and execute: `php composer.phar update`
You maybe have to redo [step 10](#cache-clear) of the etherpad lite pro installation.

### Updating this application
To update this application, you have to get the newest version from git e.g. with `git pull`
and do [step 10](#cache-clear) of the etherpad lite pro installation

### Log
The logfiles from symfony2 are in the folder: `app/logs`

### Backup
To backup this application, make a backup of your databases (both Etherpad Lite *and* Etherpad Lite Pro)

## [Customizing](id:customizing)
you can change images in `src/HUBerlin/EPLiteProBundle/Resources/public/images`  
you can change colors and layout in `src/HUBerlin/EPLiteProBundle/Resources/public/css`


## [Components & Licenses](id:licenses)
Component						| Version	| License		| Usage
------------------------------ | --------	| -------------	| -------
[Symfony2](http://symfony.com/)						| 2.3 (LTS)| MIT			| Main php framework
[Etherpad Lite Client](https://github.com/TomNomNom/etherpad-lite-client)			| api-v1.1 | Apache		| PHP client for the Etherpad Lite HTTP API
[jQuery](http://jquery.com/) & [jQuery UI](http://jqueryui.com/)				| 1.8.3 (IE8 support) & 1.9.2 | MIT			| Main javascript framework
[jQuery Iframe Post Form](http://www.jainaewen.com/files/javascript/jquery/iframe-post-form.html)			| 1.1.1 | MIT and GPL	| for uploading files via ajax
[jQuery blockUI](http://www.malsup.com/jquery/block/)					| 2.57.0 | MIT and GPL	| for blocking the UI when necessary
<http://phpjs.org> [strcmp](http://phpjs.org/functions/strcmp/) & [strnatcmp](http://phpjs.org/functions/strnatcmp/)	|  | MIT  			| for sorting new pads alphabetically
[Modernizr](http://modernizr.com/)						| 3.0.0pre (Custom Build) | MIT  			| to find out browser features (disabling css3 switch)
Icons (from Moodle)				|| GPL  			| the Icons
Etherpad Lite Pro				|| GPL			| this application
