# Kirjuri
Kirjuri is a simple php/mysql web application for managing physical forensic evidence items. It is intended to be used as a workflow tool from receiving, booking, note-taking and possibly reporting findings. It simplifies and helps in case management when dealing with a large (or small!) number of devices submitted for forensic analysis.

You can try the demo installation of Kirjuri here: http://kirjuri.kurittu.org/

OVERVIEW & LICENSE
------------

Kirjuri is developed by Antti Kurittu at the Helsinki Police Department as an internal tool. Original development released under the MIT license. Some components are distributed with their own licenses, please see folders & help for details.

It requires PHP and MySQL and uses Twig (http://twig.sensiolabs.org), Bootstrap (http://getbootstrap.com), Font Awesome (http://fontawesome.io), Freepik image resources (http://www.freepik.com), Chart.js (http://www.chartjs.org), TinyMCE editor (https://www.tinymce.com) and jQuery (https://jquery.com). You can install Kirjuri on a LAMP/WAMP/LEMP stack with just a few commands. Kirjuri has been tested on Windows, Max and Linux using PHP5 and PHP7.

WARNING
------------

Kirjuri is NOT designed to be installed on an internet-facing machines except for testing and development purposes. It is INHERENTLY INSECURE, and designed to be used in an air-gapped network by trusted operators in a secure location. If you wish to implement user management and security features, you are welcome to collaborate with this project.

Familiarize yourself with the software prior to installing it into a production environment. The developers accept no liability on security incidents following from the use of this software. The software is provided as-is.

INSTALLATION
------------

* Clone the repository to your server.
* Copy the files to your webroot directory (for example ```/usr/share/nginx/html```)
* Run ```conf/create_tables.sql``` against your database to create the tables needed for operation. (```mysql -u root -pyourpassword < create_tables.sql```)
* Set your mysql root/user password by editing ```include_functions.php``` or copy the commented out php code to ```conf/mysql_credentials.php```. This file is ignored so you can pull updates without having to re-edit the source.
* Set your preferred settings by editing ```conf/settings.conf```. Reload the settings page to apply new settings.
* Set ```cache``` folder permissions so that the www-server process can write into it or disable caching by commenting it out on ```include_functions.php```.
* Set ```attachments``` folder permissions so that the www-server process can read, create directories and write files into it.
* If you wish to enable attachments, set the settings, server and PHP directives to match the maximum allowed file size.
* If you wish to enable editing the crime list or settings from the web UI, set the server process to own ```conf/settings.conf``` and ```conf/crimes_autofill.conf```. This is insecure, and not recommended but might be preferable in some circumstances.
* If you decide to test or run Kirjuri in your organization, drop me a line via email or a shoutout at Twitter: https://twitter.com/AnttiKurittu

USAGE
------------

* Add examination requests from the left-hand menu bar.
* Use the index page to pick an examination request, assign an examiner and add devices.
* Manipulating device actions and locations takes effect immediately, other information needs to be saved with the save button.
* When all devices have been marked as "done" or "no action" Kirjuri will allow you to close the request.
* You can move, remove or edit the devices from the device listing or individual device memo.
* To reload changes made to the settings, visit the "Settings" page on the web app - this will refresh language files and settings.
* Optionally you can clear the cache to reset your PHPSESSID token, which will reload your settings.

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
* If you are updating Kirjuri from the limited release version to the current version, you can migrate your databases by running ```migrate_old_tables.sql``` against your MySQL server. This will create the new tables and insert data from the old tables to the new one. It will also truncate your event log, as there was a bug in the old event log structure where the ID didn't auto-increment. Please back up your existing installation and database before migrating.

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
2016-09-01:

* Fixed some finnish variable names. Tested Kirjuri with PHP7, seems to work fine.

2016-09-01:

* Streamlined the handling of settings - settings now take effect right away on any pageload.
* Kirjuri refuses to accept a device is type, action status and location have not been set.
* Slight tweaks here and there.
* Kirjuri triggers an error if default settings gets a new directive that is not found in the local settings in either /conf/settings.local or /etc/kirjuri/settings.local
* Added some comments to the code.

2016-08-31:

* Cleaned up submit.php and got rid of the clumsy return.php.
* Created a custom error handler that shows and/or logs errors. You can set the error level from your php.ini.
* Fixed a bunch of undefined indexes that created unnecessary notices and errors.
* Split up help.php to settings.php and help.php, so that access to settings can be limited with basic HTTP auth
* Made some small adjustments here and there.

2016-08-28:

* German translation added, thank you Dennis Schreiber for the language file!

2016-08-26:

* Added support for attaching files to examination requests. Script upload is prevented by renaming text files not ending in ```.txt```.

2016-08-25:

* Fixed page titles.
* Added removed items to CSV download.
* Fixed the statistics page counting removed items and cases.
* Slight UI enhancements.

2016-08-24:

* Changed licensing to the simpler MIT license.
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
