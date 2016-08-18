# kirjuri
Kirjuri is a simple php/mysql web application for managing physical forensic evidence.

LICENSE
------------

Kirjuri is developed by Antti Kurittu at the Helsinki Police Department as an internal tool. Original development released under the GPL v3.0 license. Some components are distributed with their own licenses, please see folders & help for details.

Kirjuri requires PHP5 and MySQL.

Kirjuri is NOT designed to be installed on an internet-facing machine except for testing and development purposes. It is INHERENTLY INSECURE, and built to be used in an air-gapped network by trusted operators. If you wish to implement user management and security features, you are welcome to submit a pull request.

INSTALLATION
------------

* Clone the repository to your server.
* Copy the files in your webroot directory (for example /usr/share/nginx/html/) 
* Run conf/create_tables.sql against your database to create the tables needed for operation. (mysql -u root -pyourpassword <create_tables.sql)
* Set your mysql root/user password at conf/settings.conf.

* If you wish to enable Twig caching for better performance, create a folder called "cache" under the webroot and give the WWW server process ownership of it. Edit main.php and uncomment row 6 to enable cache.
* Additionally if you wish to be able to edit the settings for Kirjuri inside the web app, give ownership of conf/settings.conf to the www process.

SCREENSHOTS
------------

![Add a request](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_add_request.png)
![Index page](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_index.png)
![Case overview](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_overview.png)
![Device listing in case](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_devices.png)
