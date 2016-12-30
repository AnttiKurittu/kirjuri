<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Kirjuri - About</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link href="views/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="views/font-awesome-4.6.3/css/font-awesome.min.css">
  </head>
  <body style="margin-top:40px;margin-bottom:40px;">
<div class="container">
  <div class="jumbotron">
  <h1 class="page-heading">About Kirjuri</h1>
  <p>Kirjuri is a digital forensic evidence management system. It is a web application designed to help forensic labs manage, track and report devices delivered for forensic examination. It was born in the Helsinki Police Department, which handles over a thousand devices annually. Managing these devices and keeping track of the changes and locations to all this material proved to be a difficult task, since no ready software suites for multi-user management existed.</p>

  <p>Kirjuri was written from the ground-up with one task in mind - easing the clerical tasks of the forensic investigator by organizing devices under examination requests. It is easy to deploy on an internal network using a lightweight virtual machine as a server. The current public release for Kirjuri is <?php echo file_get_contents('conf/RELEASE') ?></p>
  <a href="demo/login.php" class="btn btn-info btn-lg">Click here to try the Demo version</a>
  </div>

  <h1 class="page-heading">Main features</h1>
  <ul>
    <li>Organize devices into examination requests and track their location and status.</li>
    <li>Make notes about forensic findings and generate a simple report to document them.</li>
    <li>User management with case-by-case access management and different access leves.</li>
    <li>Simple user interface designed for the needs of actual forensic examiners.</li>
    <li>Extensive internal logging for compliance and audit tasks.</li>
    <li>Highly customizable via configuration files for different organizational needs.</li>
    <li>See the <a href="https://github.com/AnttiKurittu/kirjuri/blob/master/CHANGELOG.md" target="_BLANK">changelog</a> for more details on what's new in the current release.</li>
    <li>Kirjuri supports English, Finnish and German and is easily localizable via a configuration file.</li>
  </ul>
  <h1 class="page-heading">Installation and requirements</h1>
  <p>Kirjuri requires a web server with php and mysql installed. You can install Kirjuri by following these steps:</p>
  <ul>
    <li> Download or clone the Kirjuri code repository from <a href="https://github.com/AnttiKurittu/Kirjuri" target="_BLANK">GitHub</a>.</li>
    <li> Place the downloaded copy into your webroot.</li>
    <li> Modify folder permissions so that the web server owns the following folders: <code>cache</code>, <code>conf</code>, <code>logs</code> and <code>attachments</code>.</li>
    <li> Browse to your web server and open the /install.php page.</li>
    <li> Fill in the fields and run the installer from your web browser.</li>
    <li> Go to "settings" on your admin account and set your preferences.</li>
    <li> Create user accounts for your team.</li>
    <li> If you wish to enable larger attachments, set the settings, server and PHP directives to match the maximum allowed file size.</li>
    <li> If you want Kirjuri to speak your language, copy the lang_EN.conf file to a new file and translate the strings. If you do this, please send me the file so I can add it to the repository.</li>
  </ul>
  <p>Additionally, it is advisable to configure your web browser to not allow direct access to <code>cache</code>, <code>conf</code> or <code>logs</code> folders. This can be achieved by adding the following directives to your web server:</p>

  <h4>Nginx:</h4>
  <pre>
  location ~ \.(conf|log|txt|local)$
  {
    deny all;
    return 403;
  }
  </pre>

  <h4>Apache:</h4>
  <pre>
  &lt;Files ~ "(.js|.css)"&gt;
    Order allow,deny
    Deny from all
  &lt;/Files&gt;
  </pre>
  <h1>Updating to the latest release</h1>
  <ul>
    <li>Back up your existing installation and database! Using a virtual machine to host Kirjuri is recommended, because you can easily take snapshots to prevent problems from updating.</li>
    <li>Download the latest release, unzip it and copy the new Kirjuri files over your old ones.</li>
    <li>If you get an error message after updating, Kirjuri might need some additional tables that do not exist yet on your installation. Run <code>/install.php</code> with your credentials and existing database name. The script will fail at creating a database and/or tables because some of them already exist, but it will write the new tables on your existing installation. It will not erase your existing database.</li>
    <li>Re-set folder permissions if necessary and clear the <code>cache/</code> folder.</li>
  </ul>
  <h1>Localizing Kirjuri</h1>
  <ul>
    <li>Copy the language file of your choice to the settings folder as lang_<i>yourlang</i>.conf</li>
    <li>Translate the variables</li>
    <li>Copy the appropriate icons in <code>views/img/svg/</code> to match device names set in [devices] and [media_objs] in the language file.</li>
    <li>Icon file names convert spaces to underscores, lowercase letters and convert the umlauts <code>ä ö å Ä Ö Å</code> to <code>a o a A O A</code>: using the following Twig filter: <code>{{ entry.device_type|lower|replace({" ": "_", "ä": "a", "ö": "o"}) }}</code></li>
    <li>Please be mindful of possible problems with special characters in device names not converting cleanly to file paths.</li>
    <li>If you localize Kirjuri to a new language, please send me the language file and new icons and I'll gladly add them to the repository and credit you for them.</li>
  <h1 class="page-heading">Important security information</h4>
  <p>Kirjuri is <b>not designed</b> to be installed on an internet-facing server. Forensic evidence and the metadata about the devices and findings is usually extremely sensitive information. It is strongly recommended that you install Kirjuri on an <b>air-gapped network</b> to serve your forensic examiners locally. Familiarize yourself with the software prior to installing it into a production environment. The developers accept no liability on possible security breaches caused by programming errors.</p>

  <p>If absolutely you need to deploy Kirjuri over the internet, it is advisable to <b>limit access by requiring VPN</b> to access the site. Per-user and global IP whitelists should be deployed both on Kirjuri itself and the server serving the application.</p>
  <p>Even though care has been taken to protect Kirjuri from XSS, CSRF, SQLi and other common vulnerabilites, the author <b>will not accept any responsibility or liability</b> on the security of this software. Kirjuri <i>can be secure</i>, <b>if it is installed and used securely</b>. A PHP application cannot be trusted to handle that for the administrator.</p>

  <h1 class="page-heading">License</h1>
  <p>Kirjuri has been released under the MIT License. See the <a href="https://github.com/AnttiKurittu/Kirjuri" target="_BLANK">GitHub repository</a> for licensing details. Kirjuri uses <a href="http://twig.sensiolabs.org" target="_BLANK">Twig</a>, <a href="http://htmlpurifier.org/" target="_BLANK">HTMLPurify</a>, <a href="http://getbootstrap.com" target="_BLANK">Bootstrap CSS</a>,
  <a href="http://fontawesome.io" target="_BLANK">Font Awesome</a>, <a href="http://www.freepik.com" target="_BLANK">Freepik image resources</a>, <a href="http://www.chartjs.org" target="_BLANK">Chart.js</a>, <a href="https://www.tinymce.com" target="_BLANK">TinyMCE editor</a> and <a href="https://jquery.com" target="_BLANK">jQuery</a>.

  <hr>
  <h1 class="page-heading">Contributors</h1>
  <p>This software has been written by Antti Kurittu, who currently works as a senior specialist at the National Cyber Security Center of Finland (FICORA NCSC-FI). German localization work has been done by Dennis Schreiber. If you are interested in contributing, giving feedback or just letting me know you use and enjoy Kirjuri - please <a href="mailto:antti.kurittu@gmail.com">Send me an email</a>! Want to try to hack Kirjuri? Use the demo installation <a href="/hackme/">here</a>. The username is "hacker" and the password is "hunter2". Let me know if you find the secret and how you did it.</p>
  <a href="https://github.com/AnttiKurittu/Kirjuri" target="_BLANK" class="btn btn-info"><i class="fa fa-github"></i> Kirjuri on GitHub</a> <a href="https://twitter.com/AnttiKurittu" target="_BLANK" class="btn btn-info"><i class="fa fa-twitter"></i> Me on Twitter</a>
  <hr>
      </div>
  </div>
  </body>
<html>
