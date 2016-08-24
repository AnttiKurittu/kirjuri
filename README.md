# Kirjuri
Kirjuri is a simple php/mysql web application for managing physical forensic evidence items. It is intended to be used as a workflow tool from receiving, booking, note-taking and possibly reporting findings. It simplifies and helps in case management when dealing with a large (or small!) number of devices submitted for forensic analysis.

You can try the demo installation of Kirjuri here: http://kirjuri.kurittu.org/

OVERVIEW & LICENSE
------------

Kirjuri is developed by Antti Kurittu at the Helsinki Police Department as an internal tool. Original development released under the GPL v3.0 license. Some components are distributed with their own licenses, please see folders & help for details.

Kirjuri requires PHP5 and MySQL and uses Twig (http://twig.sensiolabs.org), Bootstrap (http://getbootstrap.com), Font Awesome (http://fontawesome.io), Freepik image resources (http://www.freepik.com), Chart.js (http://www.chartjs.org), TinyMCE editor (https://www.tinymce.com) and jQuery (https://jquery.com). You can install Kirjuri on a LAMP/WAMP/LEMP stack with just a few commands.

WARNING
------------

Kirjuri is NOT designed to be installed on an internet-facing machines except for testing and development purposes. It is INHERENTLY INSECURE, and designed to be used in an air-gapped network by trusted operators in a secure location. If you wish to implement user management and security features, you are welcome to collaborate with this project.

Familiarize yourself with the software prior to installing it into a production environment. The developers accept no liability on security incidents following from the use of this software. The software is provided as-is.

INSTALLATION
------------

* Clone the repository to your server.
* Copy the files to your webroot directory (for example /usr/share/nginx/html/)
* Run conf/create_tables.sql against your database to create the tables needed for operation. (```mysql -u root -pyourpassword < create_tables.sql```)
* Set your mysql root/user password by editing ```include_functions.php```
* Set other settings by editing ```conf/settings.conf```
* Set ```cache``` folder permissions so that the www-server process can write into it.
* If you wish to enable editing the autofill crime list and settings from the web UI, set the server process to own ```conf/settings.conf``` and ```conf/crimes_autofill.conf```. This is insecure, and not recommended but might be preferable in some circumstances.
* If you run Kirjuri in your organization, drop me a line via email or a shoutout at Twitter: https://twitter.com/AnttiKurittu

UPDATING FROM A PREVIOUS VERSION
------------
* If you are updating Kirjuri from the limit release version to this version, you can migrate your databases by running ```migrate_old_tables.sql``` against your MySQL backend. This will create the new tables and insert data from the old tables to the new one. It will also truncate your event log, as there was a bug in the old event log structure where the ID accidentally didn't auto-increment.

LOOKING TO PARTICIPATE?
------------
* Everyone interested is encouraged to submit code and enhancements. If you don't feel confident submitting code, you can submit lanugage files and localized lists of devices etc. These will gladly be accepted.

SCREENSHOTS
------------

![Add a request](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_add_request.png)
![Index page](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_index.png)
![Case overview](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_overview.png)
![Device listing in case](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_devices.png)
![Device memo](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshot_device_memo.png)

CHANGELOG
------------
2016-08-24:

* More elegant handling of mysql credentials, you can use an optional ignored file which will allow pulling/pushing changes.
* Made the device action and device location jump lists dynamic - choosing a value automatically saves it. This works in both device memo and devices overview.
* Added examiners private notes as an editable field in the device memo

2016-08-23:

* Moved the listings from settings to the language files where they belong.
* Translated some finnish named variables to english.
* Made it possible to copy ```settings.conf``` to ```settings.local``` and added it to gitignore so that pulling changes doesn't reset settings.

2016-08-22:

* Fixed the language file to use natural language variables for more readable code.
* Moved the MySQL credentials from the config file to source code.
* Cleaned up the inconsistent use of Twig brackets.
* Fixed page titles.
* Removed unused files.
* Added an icon to stalled cases (counting from latest update), stall threshold can be set from settings.
* Added possibility to download case data as a CSV file.
* Made the device listing droplists dynamic - no need to save one by one, changes effect immediately.
* Other small bugfixes.
