# Kirjuri
Kirjuri is a simple php/mysql web application for managing physical forensic evidence items. It is intended to be used as a workflow tool from receiving, booking, note-taking and possibly reporting findings. It simplifies and helps in case management when dealing with a large (or small!) number of devices submitted for forensic analysis.

OVERVIEW & LICENSE
------------

Kirjuri is developed by Antti Kurittu at the Helsinki Police Department as an internal tool. Original development released under the MIT license. Some components are distributed with their own licenses, please see folders & help for details.

It requires PHP and MySQL and uses Twig (http://twig.sensiolabs.org), HTMLPurify (http://http://htmlpurifier.org/), Bootstrap (http://getbootstrap.com), Font Awesome (http://fontawesome.io), Freepik image resources (http://www.freepik.com), Chart.js (http://www.chartjs.org), TinyMCE editor (https://www.tinymce.com) and jQuery (https://jquery.com). You can install Kirjuri on a LAMP/WAMP/LEMP stack with just a few commands. Kirjuri has been tested on Windows, Mac and Linux using PHP5 and PHP7.

CHANGELOG
------------

see [CHANGELOG.md](CHANGELOG.md)

WARNING
------------

Kirjuri is NOT designed to be installed on an internet-facing machines except for testing and development purposes. It is INHERENTLY INSECURE, and designed to be used in an air-gapped network by trusted operators in a secure location. If you wish to implement more security features, you are welcome to collaborate with this project.

Familiarize yourself with the software prior to installing it into a production environment. The developers accept no liability on security incidents following from the use of this software. The software is provided as-is.

INSTALLATION
------------

1. Clone the repository to your server and copy the files to your webroot directory (for example ```/usr/share/nginx/html```)
2. Set ```cache```, ```attachments```, ```logs``` and ```conf``` folder permissions so that the www-server process can read, create directories and write files into them.
3. Navigate to ```http://yourwebserver/install.php``` and run the installation script. The script will ask for your MySQL credentials, create the necessary database, tables and write two files:
  * ```conf/mysql_credentials.php``` which stores you MySQL credentials
  * ```conf/settings.local``` which stores your local changes to the settings file.
  * These files will not be updated with ```git pull```
  * You can place these files into /etc/kirjuri if you wish and they will be read from there.
4. Move the install script somewhere safe so that users can't accidentally run it and cause problems.
5. Log in with "admin" / "admin" and create your users.
6. Go create your first examination request, assign it to an user and add devices!

If you wish to enable larger attachments, set the settings, server and PHP directives to match the maximum allowed file size.
For any questions or suggestions, drop me a line via email or a shoutout at Twitter: https://twitter.com/AnttiKurittu. I'd also love to know if you use Kirjuri in your organization!

USAGE
------------

* Add examination requests from the left-hand menu bar.
* Use the index page to pick an examination request, assign an examiner and add devices.
* Manipulating device actions and locations takes effect immediately, other information needs to be saved with the save button.
* When all devices have been marked as "done" or "no action" Kirjuri will allow you to close the request.
* You can move, remove or edit the devices from the device listing or individual device memo.
* Users have four levels of access; admin, user, view only and add only. Create your users accordingly. If you wish to effectively disable user management give the anonymous user admin access.
* If you have access to an IMEI database, Kirjuri can look up IMEI numbers and pull device manufacturer and model data from it. The database can be uploaded from settings and requires the following format: TAC|Marketing Name|Internal Model Name|Manufacturer|Bands|Allocation Date|Country Code|Fixed Code|Manufacturer Code|Radio Interface|Brand Name|Model Name|Operating System|NFC|Bluetooth|WLAN|Device Type

LOCALIZATION
------------
* Copy the language file of your choice to the settings folder as lang_XX.conf
* Translate the variables
* Copy the appropriate icons in ```views/img/svg/``` to match device names set in [devices] and [media_objs] in the language file
* Icon file names convert spaces to underscores, lowercase letters and convert the umlauts ```ä ö å Ä Ö Å``` to ```a o a A O A```: using the following Twig filter: ```{{ entry.device_type|lower|replace({" ": "_", "ä": "a", "ö": "o"}) }}```
* Please be mindful of possible problems with special characters in device names not converting cleanly to file paths.
* If you localize Kirjuri to a new language, please send me the language file and new icons and I'll gladly add them to the repository and credit you for them.

UPDATING FROM A PREVIOUS VERSION
------------

* Back up your existing installation and database!

* If you get an error message after updating, Kirjuri might need some additional tables that do not exist yet. Run "/install.php" with your credentials and existing database name. The script will fail at creating a database and/or tables because some of them may already exist, but it will write the new tables on your existing installation.

* If you are updating Kirjuri from the finnish limited release version to the current version, you can migrate your databases by running ```migrate_old_tables.sql``` against your MySQL server. This will create the new tables and insert data from the old tables to the new one. It will also truncate your event log, as there was a bug in the old event log structure where the ID didn't auto-increment. Please back up your existing installation and database before migrating.

LOOKING TO PARTICIPATE?
------------
* Everyone interested is encouraged to submit code and enhancements. If you don't feel confident submitting code, you can submit lanugage files and localized lists of devices etc. These will gladly be accepted.

SCREENSHOTS
------------

![Requests overview](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/1.png)
![Statistics](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/2.png)
![Add request](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/3.png)
![User management](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/4.png)
![Demo request 1](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/5.png)
![Demo request 2](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/6.png)
![Devices](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/7.png)
![Device memo](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/8.png)
![Examination report](https://github.com/AnttiKurittu/kirjuri/blob/master/conf/screenshots/9.png)
