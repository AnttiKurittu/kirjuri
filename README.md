# kirjuri
Kirjuri is a simple php/mysql web application for managing physical forensic evidence.

LICENSE
------------

Kirjuri is developed by Antti Kurittu at the Helsinki Police Department as an internal tool. Original development released under the GPL v3.0 license. Some components are distributed with their own licenses, please see folders & help for details.

Kirjuri requires PHP5 and MySQL and uses Twig (http://twig.sensiolabs.org), Bootstrap (http://getbootstrap.com), Font Awesome (http://fontawesome.io), Freepik image resources (http://www.freepik.com) and jQuery (https://jquery.com).

Kirjuri is NOT designed to be installed on an internet-facing machines except for testing and development purposes. It is INHERENTLY INSECURE, and designed to be used in an air-gapped network by trusted operators. If you wish to implement user management and security features, you are welcome to submit a pull request.

INSTALLATION
------------

* Clone the repository to your server.
* Copy the files to your webroot directory (for example /usr/share/nginx/html/)
* Run conf/create_tables.sql against your database to create the tables needed for operation. (mysql -u root -pyourpassword < create_tables.sql)
* Set your mysql root/user password at conf/settings.conf.
* Set folder permissions for the web server process to own cache/
* If you wish to enable editing the autofill crime list and settings from the web UI, set the server process to own conf/settings.conf and conf/crimes_autofill.conf. This is insecure, and not recommended but might be preferable in some circumstances.
* If you run Kirjuri in your organization, drop me a line via email or a shoutout at Twitter: https://twitter.com/AnttiKurittu

SCREENSHOTS
------------

![Add a request](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_add_request.png)
![Index page](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_index.png)
![Case overview](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_overview.png)
![Device listing in case](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_devices.png)
