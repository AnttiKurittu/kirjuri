CHANGELOG
------------

2017-09

* Fixed a few bugs
* Last release, I will no longer actively develop this project as I don't have time for it anymore. If you are interested in taking the lead in developing this project, please contact me at antti.kurittu@gmail.com.

2017-01    Version 0.9.1

* Made some tweaks to the user interface on user settings.
* Added a language file editor to easily localize Kirjuri. You can access it from settings -> lang -> click the "Lang"-title link. Language files are stored as JSON files. Older .conf files will be loaded if no .JSON file is present, but saved as JSON on first edit.
* Added a reservation calendar for the tools.
* Branched the Kirjuri git project to "develop" and "master", "develop" contains the bleeding edge changes which are still untested. These changes will be merged to master after enough testing has been done to make sure nothing breaks.
* Some bugfixes.

2017-01-22 Version 0.9.0:

* Dropped support for PHP5 - dependencies require this. Upgrade to PHP7.
* Updated dependencies: Twig v2.1.0, HTMLPurifier v4.8.0, php-barcode-generator v0.2.1., TinyMCE v4.5.2
* Greatly improved logging - Kirjuri logs events to logs/kirjuri.log and case events to case folders in the logs/-folder. Logging to database is deprecated, all logs now go to log files.
* Case timeline - A neat visualization of case events.
* Audit trail. All POST and GET requests that contain changes to requests or devices are saved locally. This enables auditing changes to requests and in some cases, restoring data to the database from previous POST requests. Audit logs are encrypted with a auto-generated key. You can change this key manually. You can access the audit logs and case timeline by clicking the green calendar icon in the case log.
* You can now export and import individual cases in the form of .krf files. These files include the examination request, all devices and all attachments. The file is a JSON object compressed with gzencode(), so importing and exporting large cases might take a while.
* LDAP authentication support, currently supports authenticating against Active Directory with the ldap_bind()-function. AD authentication creates a local account on first login. Authentication to this account is only allowed over LDAP. You can still use local accounts as usual, and disable this feature from the settings. If the feature is enabled, domain authentication is the default setting.
* A cool new background image from Subtle Patterns! https://subtlepatterns.com/
* Files are now uploaded to the database to simplify backup, to enable auditing file uploads & downloads and mitigate file inclusion vulnerabilities. If you have local files stored at the attachments/ subfolders, you can still access these, but uploading or removing local files is now deprecated. File size is limited to 16MB by database design to keep the database size reasonable, and files are compressed prior to writing to the database. Kirjuri is not designed to be used as a file storage, so this functionality is offered mainly for storing forensic reports, documents, images or other small files related to the case. Large files like drive images should be stored elsewhere.
* Started working on a RESTful API. The rest_api.php script is disabled by default, as it can only read information for now.
* Moved from incrementing release numbers to versioning.
* Renamed views/\*.html files to .twig
* A lot of small bugfixes.

2017-01-01 Release 133:

* Made the user session management a bit more sane.
* Handed the task of checking for a valid session to unreadcounter.php
* Added checking for session timestamp to determine whether session is online or not.
* Other tweaks and fixes.
* Fixed a typo in the Finnish language file that made the setting fail.

2016-12-29 Release 132:

* Made handling settings better - instead of editing a text file, fields are presented and settings file parsed from that.
* More tweaks, bugfixes and other betterments.
* Gave an input form to set the admin password on install time instead of defaulting to "admin".
* Removed adding extra examiners from settings file. Create users for this.
* Switched some glyphicons to Font Awesome icons. Gotta finish this.
* Ending an users session will force-logout after a while.

2016-12-27 release 131:

* Enhanced the request front page with a more concise layout.
* Added session information and administrator ability to terminate single sessions for users.
* Global black/whitelists for login IP addresses, configurable in conf/access_list.php.
* Tweaks, bugfixes, etc.

2016-12-25 release 130:

* Enhanced user management - force logout, delete user permanently
* Enhanced security features with per-user IP black/whitelisting. This will not restrict existing sessions but sets limits to new sessions.
* Bugfixes and performance tweaks.

2016-12-22 release 129:

* Big feature update!
* You can now set which index page columns are shown or not. The file to configure this behaviour is conf/index_columns.conf.
* Per-case, per user access control added. Users can restrict access to examination requests and users are not able to view or edit requests where they don't belong to the access group. Administrators can see and set any case access group. Access groups are independent of user privilege level.
* Case event logging; log files are now written for each case, and they are displayed on the examination request page to keep track of the investigation. Non-case related logs are written to kirjuri_case_0.log and errors to error.log. The logs are stored in kirjuri_errors.log and kirjuri_events.log when stored & cleared. All log events are also written to the database for audit purposes.
* CSRF protection enhanced, now works as per-session to enable a tabbed workflow.
* Implemented internal protections with case access tokens.
* User passwords are now stored with the standard password_hash() function, not sha256 (what was I even thinking?)
*

2016-12-18 release 128:

* Added CSRF protection to all pages, even though you shouldn't deploy Kirjuri on a network where this is an issue.
* Updated the German language file with the new language directives. Translation courtesy of Dennis Schreiber, thank you very much!
* Shuffled the message buttons around so that the tooltip doesn't cover the essential buttons.
* Added a possibility to create a device memo report template, so that you can add your own "fields" to it. Currently it has rows for imaging details. You can edit this file at conf/report_notes.template. HTML is allowed, the file is sanitized before presentation. You can copy the file to report_notes.local to preserver your local changes through updates.
* The "add-only" user now only sees the "Add a new request"-page.
* Message of the day will be shown at the login screen.

2016-12-08 release 127:

* Added a passwords field to the "new request"-page. The investigator can now add all known passwords to the request at the time of submission, and they will be displayed in the private notes section. Fixed a few minor annoyances.
* Statistics page will not load if no cases present.
* This update does not alter database structures.

2016-11-15:

* Fixed an error on newest MySQL (at least Ver 14.14 Distrib 5.7.16) where inserting a value of '' no longer populates an INT field with "0" but instead fails. Some dirty workarounds on submit.php to fix this. No need to update if your version works fine.

2016-10-11:

* Added two new data graphs to the statistics; amount of data per requesting unit and amount of devices per requesting unit.
* Small bugfixes.

2016-09-30:

* Added HTMLPurifier processing for strings presented as raw to prevent XSS.

2016-09-29:

* Upgraded the included TinyMCE from 4.2.7 to 4.4.3

2016-09-28:

* Implemented a simple internal messaging system. This update requires running ```install.php``` to create the necessary tables.

2016-09-27:

* THIS RELEASE CREATES AN ADDITIONAL DATABASE TABLE. RE-RUN ```install.php``` to auto-create the necessary tables. Back up your database before making any changes to your existing installation.
* Added support for managing your forensic tools and adding them to the case via a tool registry. The tool registry can be used to keep track of software and hardware versions in use complete with a version / update history. You can add information about used tools to a device by using the "Add a tool..."-dropdown in the device memo and saving the data. This will add a line to the examiners notes about the tools used. This information will not be printed to the report.
* Added support for using an IMEI database to automatically look up device details based on the TAC identified in the IMEI code. The database is not shared but you can request a copy from GSMA if you are eligible for one. The database format is as follows: "TAC|Marketing Name|Internal Model Name|Manufacturer|Bands|Allocation Date|Country Code|Fixed Code|Manufacturer Code|Radio Interface|Brand Name|Model Name|Operating System|NFC|Bluetooth|WLAN|Device Type". The administrator can upload an IMEI database via the settings or move it manually to ```conf/imei.txt```.

2016-09-23:

* Added support for printing and reading barcodes. Device and case stickers now have a barcode, which you can read to the "Search" box and jump straight to that device. The label printer has been tested with a Dymo LabelWriter 450. Support for barcodes has been built with https://github.com/picqer/php-barcode-generator
* Some visual changes to better handle limited screen real estate, stacked the action / status droplists on top of each other to make room for device information.
* Changed how enter behaves when filling forms from saving form to jumping to next field.
* Started working on an API. This is still very heavily a work in progress, and is at this time undocumented. I'm still including it in this update so that it can be tried out. Do not expect it to remain unchanged over future updates!
* Added a warning after three months if confiscation date is nearing +4 months (if this option is turned on, default off)
* Removed the timeout for open browser windows logging the user out.
* Added the ability to dump the database into a file for backing up from the browser. The link is under settings, available to admin accounts. Set your mysqldump binary location in the source if it doesn't work right away.
* Fixed some coding standards which jumbled a lot of the files around.
* Fixed a few minor bugs.


2016-09-14:

* Updated Twig to version 1.24.3 (https://github.com/twigphp/Twig)
* Updated Font Awesome to version 4.6.3 (http://fontawesome.io/)

2016-09-09:

* Big update!
* Implemented user management.
  * Passwords are saved as simple sha256 hashes, which should be enough for this use case and don't require dependencies
  * Users are stored in the database
* Simplified installation with an install script.
  * Run /install.php to rebuild database and add user tables
* Improved error / info message system.
* Removed clumsiness and utilized the session variable more.
* Streamlined template rendering a bit.
* Added comments to the code.
* Please notify me of any bugs you find, I've tested the new version but some might have slipped by.

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
