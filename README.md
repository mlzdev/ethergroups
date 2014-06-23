Ethergroups - *Groups for Etherpad*
======

###Documentation for administrators

Written by *Timo Welde*  
<timo.welde@hu-berlin.de>

## Table of contents
1. [Overview](#overview)
	* [Features](#features)
	* [Why symfony2?](#why-symfony2)
	* [Server architecture](#server-architecture)
2. Installation
	* [of Etherpad Lite](#of-etherpad-lite)
	* [of Ethergroups](#of-ethergroups)
3. Configuration
	* [of Etherpad Lite](#of-etherpad-lite-1)
	* [of Ethergroups](#of-ethergroups-1)
4. [Administration](#administration)
5. [Customizing](#customizing)
6. [Components & Licenses](#components--licenses)


## [Overview](id:overview)
Etherpad Lite is a program which allows users to do collaborative writing. For Moodle (a learning managment system) the HU developed a plugin, which is actively used.  
After it was established, there was a demand to use this editor without the overload of Moodle, so we developed a program, which provides simple ldap authentication and group managment.

### [Features](id:features)
* ldap authentication
* distraction free writing by hiding header and sidebar
* readonly mode
* multilingual
	* german
	* english
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
	* can decide after they logged in, if they want to be in a particular group, or not


### [Why symfony2?](id:why-symfony2)
* We used the symfony2 framework in the long-term version 2.3.x because it relies on the MVC (Model-View-Controller) pattern, so it is easier for another team to do further development on this software.
	* The ORM in Symfony2 called `Doctrine` supports various databases
	* The Templating Engine `Twig` makes it easy to design the website, without php knowledge
	* The Controller works with services, which can be injected to support various behaviour
* It also has a big community, so it is easy to get help.  
* The framework is under constantly development. With this LTS version we get security updates until May 2016.

### [Server architecture](id:server-architecture)
As server architecture, we are using `apache2` as webserver for this software, because it's well known, secure and supports symfony2 very good.  
To run this software next to etherpad lite on the same server and on the same port (so there are no firewall issues), we decided to use another webserver called `nginx` as a reverse proxy. The advantages of this webserver is, that it is lightweight, fast and that it is easy to setup as a reverse proxy. The directory `/eplite` goes to etherpad lite and all other are going to the apache server.  
Further information: [Why nginx + apache?](#why-nginx-apache)

## Installation
### [of Etherpad Lite](id:of-etherpad-lite)
You need an etherpad-lite server, which is running on at least the same 2nd-level-domain as your ethergroups server, because we are using cookies to authenticate users.
[Infos & Download](https://github.com/ether/etherpad-lite)

We recommend to use the etherpad-lite version 1.3.0

It's also recommended to use the latest stable release of nodejs
(http://nodejs.org/)  
*we are using nodejs 0.8.26, installed via n (a nodejs version managment tool installed via npm) for our productive server. But we test new ep-lite versions always with this node version, before updating productive*  

#### Working ep-lite installation
- Ubuntu 12.04
- apt-get nodejs, npm, git, nginx, abiword, make, g++
- etherpad-lite from git (v1.3.0)
- ep-lite settings.json:
	-	"requireSession":false
	-	"editOnly":true
	-	"abiword": "/usr/bin/abiword"
- upstart script
- logrotate

### [of Ethergroups](id:of-ethergroups)

1. Install `apache2-mpm-prefork, mysql, php5, php5-mysql, php5-intl, php5-ldap`

2. Configure apache2:
		
		a2enmod rewrite
		
	In your site config (e.g. sites_available/ethergroups):
	
		DocumentRoot /path/to/ethergroups/web/
		<Directory /var/www/ethergroups>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride FileInfo
                Order allow,deny
                allow from all
        </Directory>
		AllowOverride FileInfo
		
3. Configure Nginx (If you want to use this on the same server, where etherpadlite is running)  [Tell me more](#why-nginx-apache)
	* nginx -> port `80`
	* apache2 -> port `8080` (Maybe you have to listen on this port also in apache2 ports.conf)
	* etherpadlite -> port `9001`
	* nginx directory `/` -> proxy_pass to `8080`
	* nginx directory `/eplite` -> proxy_pass to `9001`
	
	[How to put Etherpad Lite behind a reverse Proxy](https://github.com/ether/etherpad-lite/wiki/How-to-put-Etherpad-Lite-behind-a-reverse-Proxy)  
	[How to configure nginx to proxy vhosts to apache](http://blog.ludovf.net/configure-nginx-to-proxy-virtual-hosts-to-apache/ "Title")

4. Checkout this repository with `git clone`.

5. Execute the `check.php` script from the command line and resolve problems (you should execute it with the apache2 user to avoid permission errors):
		
		php app/check.php

	Access the `config.php` script from a browser:

    	http://sub.domain.tld/config.php

	If you get any warnings or recommendations, fix them before moving on.

6. Copy `app/config/parameters.yml.dist` to `app/config/parameters.yml` and modify it, to fit you installation

	Setting		|	Explanation
	----------- |--------------
	database\_* | we use `pdo_mysql`. You can have a look [here](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html) for other databases
	mailer\_* | 
	locale | this is the default locale, symfony should use
	secret | this is a secret code, symfony uses to prevent bots from filling out forms. you can generate one [here](https://tools.brain-tower.com/en/security/passwordgenerator?template=symfony-secret)
	ldap.data.provider_* | your ldap config
	etherpadlite apikey | found in the `APIKEY.txt` on your etherpad lite server
	forgotpasswordurl | the url the link for "Forgot your password?" should point to
	admin\_* | admin login and password for the site under yourdomain.tld/**admin**
	loguserdata | log user data on/off e.g. IPs and usernames [boolean]
	readonly | readonly mode on/off [boolean]

7. Change dir permissions of `app/cache`, `app/logs`, `app/Resources/translations` & `web/uploads` to your webservers user:group

8. Install vendors with `php composer.phar install`

9. Create database with `php app/console doctrine:schema:create`

10. [Clear the cache:](id:cache-clear)  
	For debug mode: `php app/console cache:clear`  
	For productive mode: `php app/console cache:clear --env=prod`  
	You maybe have to renew the dir permissions for the `app/cache` folder after this

11. If you want to use the productive mode you have to change `app_dev.php` to `app.php` in the file `web/.htaccess`

***

###[Why nginx + apache?](id:why-nginx-apache)
If you have one server for ethergroups (with apache) and a seperate with etherpadlite (with nginx e.g.), there is no problem.  
BUT when you want them both on one machine and on the same port (to prevent firewall issues e.g. with firewalls which only allow port 80 and 443) you can configure nginx as a reverse proxy for both.  
E.g. for the etherpadlite server you redirect `/eplite`to port 9001 and everything else to port 8080, where apache (with ethergroups) is listening.

![image](https://raw.github.com/goldquest/ethergroups/develop-local/doc/nginx_rp_diagram.png)

---

## Configuration
### [of Etherpad Lite](id:of-etherpad-lite-1)
Settings file: `/path/to/eplite/settings.json`  
It's strongly recommended to use a dedicated database (e.g. PostgreSQL) for a productive environment  
We also recommend setting these settings, if you want to use it only with Ethergroups:

	"requireSession" : false,
	"editOnly" : true,

### [of Ethergroups](id:of-ethergroups-1)
#### Automatic removal of missing ldap users
For automatic removal of in ldap deleted users, you have to add following command to e.g. Cron:
`php /path/to/ethergroups/app/console Ethergroups:ldap`

## [Administration](id:administration)

### URL Schema
The URL Schema for public pads is: `http[s]://[www].[sub].[domain].[tld]/[yourDirectory]/p/[groupID]$[padID]`

### Editing language strings and mail content
You can edit the language strings directly here:
	
	/path/to/ethergroups/app/Resources/translations/
	
The mail contents can be edited here:

	/path/to/ethergroups/src/Ethergroups/MainBundle/Resource/views/Mails
	
#### Change frontpage strings via website
There is an admin site, where you can change the frontpage.  
You have to give a login and a password in the `parameters.yml` file.
After that you can access the admin page under `http://yourdomain.tld/admin`.
	
### Adding another language
To add another language, you have to add a file with the translations, according to `messages.en.yml` with the naming schema: `messages.[langCode].yml` in

	/path/to/ethergroups/src/Ethergroups/MainBundle/Resources/translations/
	
and add this language to the dropdown menu in

	/path/to/ethergroups/src/Ethergroups/MainBundle/Resources/views/layout.html.twig

### Updating symfony2 vendors
To update the symfony2 framework (e.g. when this repository updated the composer.lock file), you have to change into the base directory of this application and execute: `php composer.phar install`
You maybe have to redo [step 10](#cache-clear) of the ethergroups installation.

### Updating this application
To update this application, you have to get the newest version from git e.g. with `git pull`
and do [step 10](#cache-clear) of the ethergroups installation

### Log
The logfiles from symfony2 are in the folder: `app/logs`  
There is a log file from symfony2 and a log file with statistical informations

### Backup
To backup this application, make a backup of your databases (both Etherpad Lite *and* Ethergroups) and save the files in the `web/uploads` directory

## [Customizing](id:customizing)
you can change images in `src/Ethergroups/MainBundle/Resources/public/images`  
you can change colors and layout in `src/Ethergroups/MainBundle/Resources/public/css`


## [Components & Licenses](id:components--licenses)
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
Ethergroups				|| GPL			| this application
