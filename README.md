Etherpad Lite Pro
========================

This is a standalone version for Etherpad Lite implemented with the symfony2 framework.
It requires LDAP for user authentication.

1) Installation
----------------------------------

You need an etherpadlite server to use this. (Recommended: v1.2.7)

Checkout this repository.

Execute the `check.php` script from the command line:

    php app/check.php

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

Modify parameters.yml

Install vendors

Apache webroot to /path/to/symfony/web/

Clear cache

2) Configuration
-------------------------------------
For automatic removal of in ldap deleted users, you have to add following command to e.g. Cron:
php /path/to/symfony/app/console huberlin:ldap
