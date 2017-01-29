<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Kirjuri - Digital Forensic Evidence Item Management</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
  <body style="margin-top:40px;margin-bottom:40px;">
<div class="container">
  <div class="jumbotron">
  <h1>About Kirjuri</h1>
  <p>Kirjuri is a digital forensic evidence item management system. It is a web application designed to help forensic teams manage, track and report devices delivered for forensic examination. It was born in the <a href="https://www.poliisi.fi/en/helsinki">Helsinki Police Department</a>, which handles over a thousand devices annually. Managing these devices and keeping track of the changes and locations to all this material proved to be a difficult task, since no ready software suites for multi-user evidence device management existed.</p>

  <p>Kirjuri was written from the ground-up with one task in mind - easing the clerical tasks of the forensic investigator by organizing devices under examination requests. It is easy to deploy on an internal network using a Linux-based virtual machine as a server. Kirjuri is being used by a number of private organizations and law enforcement agencies in a number of countries. The current public release for Kirjuri is <?php echo file_get_contents('https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/conf/RELEASE') ?>.</p>
  <p>Kirjuri requires a web server with MySQL and PHP7 installed. Some performance issues have been noticed when running Kirjuri on a WAMP server, so installing on a Linux server is recommended.</p>

  <a href="demo/index.php" class="btn btn-info btn-lg">Click here to try the Demo version</a>
  </div>

  <h1 class="page-heading">Main features</h1>
  <ul>
    <li>Organize devices into examination requests and track their location and status.</li>
    <li>Make notes about forensic findings and generate a simple report to document them.</li>
    <li>Organize your tools and manage reservations for them.</li>
    <li>User management with case-by-case access management and different access leves.</li>
    <li>Simple user interface designed for the needs of actual forensic examiners.</li>
    <li>Extensive internal logging for compliance and audit tasks.</li>
    <li>Highly customizable via configuration files for different organizational needs.</li>
    <li>Supports attachments up to 16MB.</li>
    <li>Export and import examination requests (with attachments) via .krf files.</li>
    <li>See the <a href="https://github.com/AnttiKurittu/kirjuri/blob/master/CHANGELOG.md" target="_BLANK">changelog</a> for more details on what's new in the current release.</li>
    <li>Kirjuri supports English, Finnish and German and is easily localizable via a configuration file.</li>
  </ul>

  <h1 class="page-heading">Screenshots</h1>
    <div class="row">
      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/1.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/1.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/2.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/2.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/3.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/3.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/4.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/4.png" alt="Screenshot from Kirjuri">
        </a>
      </div>
    </div>
    <div class="row">

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/6.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/6.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/7.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/7.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/8.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/8.png" alt="Screenshot from Kirjuri">
        </a>
      </div>

      <div class="col-xs-4 col-md-2">
        <a href="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/9.png" class="thumbnail">
          <img src="https://raw.githubusercontent.com/AnttiKurittu/kirjuri/master/extra/screenshots/9.png" alt="Screenshot from Kirjuri">
        </a>
      </div>
    </div>

  <h1 class="page-heading">Installation and requirements</h1>
  <p>Kirjuri requires a web server with PHP7 and MySQL installed. You can install Kirjuri on your server by following these steps:</p>
  <ul>
    <li> Install PHP, MySQL and Git (Debian/Ubuntu: <code>sudo apt-get install git mysql-server php7.0 php7.0-fpm php7.0-mysql nginx-full</code>).</li>
    <li> For a local, single-user installation, you can use a development server app like <a href="https://www.mamp.info/">MAMP</a>.</li>
    <li> Download or clone the Kirjuri code repository from <a href="https://github.com/AnttiKurittu/Kirjuri/tree/master" target="_BLANK">GitHub</a>.</li>
    <li> Place the downloaded copy into your webroot or a subfolder.</li>
    <li> If you wish to configure a testing environment, simply copy the Kirjuri folder to an adjacent subfolder and run the installer again with a different database name.</li>
    <li> Modify folder permissions so that the web server owns the following folders: <code>cache</code>, <code>conf</code> and <code>logs</code>.</li>
    <li> Browse to your web server and open the /install.php page.</li>
    <li> Fill in the fields and run the installer from your web browser.</li>
    <li> Go to "settings" on your admin account and set your preferences.</li>
    <li> Create user accounts for your team or configure LDAP access.</li>
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
    <li>Delete all files and folders from the <code>cache</code> folder, excluding the .gitignore file.</li>
    <li>If you get an error message after updating, Kirjuri might need some additional tables that do not exist yet on your installation. Run <code>/install.php</code> with your credentials and existing database name. The script will fail at creating a database and/or tables because some of them already exist, but it will write the new tables on your existing installation. It will not erase your existing database.</li>
    <li>Re-set folder permissions if necessary.</li>
  </ul>
  <h1>Localizing Kirjuri</h1>
  <ul>
    <li>Copy the language file of your choice to the settings folder as lang_<i>yourlang</i>.conf</li>
    <li>Translate the variables</li>
    <li>Copy the appropriate icons in <code>views/img/svg/</code> to match device names set in [devices] and [media_objs] in the language file.</li>
    <li>Icon file names convert spaces to underscores, lowercase letters and convert the umlauts <code>ä ö å Ä Ö Å</code> to <code>a o a A O A</code>: using the following Twig filter: <code>{{ entry.device_type|lower|replace({" ": "_", "ä": "a", "ö": "o"}) }}</code></li>
    <li>Please be mindful of possible problems with special characters in device names not converting cleanly to file paths.</li>
    <li>If you localize Kirjuri to a new language, please send me the language file and new icons and I'll gladly add them to the repository and credit you for them.</li>
  </ul>

  <h1 class="page-heading">Important security information</h4>
  <p>Kirjuri is <b>not designed</b> to be installed on an internet-facing server. Forensic evidence and the metadata about the devices and findings is usually extremely sensitive information. It is strongly recommended that you install Kirjuri on an <b>air-gapped network</b> to serve your forensic examiners locally. Familiarize yourself with the software prior to installing it into a production environment. The developers accept no liability on possible security breaches caused by programming errors.</p>

  <p>If absolutely you need to deploy Kirjuri over the internet, it is advisable to <b>limit access by requiring VPN</b> to access the site. Additionally you can configure your web server to require client certificates and whitelist IP-addresses on server level. Per-user and global application IP whitelists should be deployed both on Kirjuri itself and the server serving the application.</p>
  <p>Even though care has been taken to protect Kirjuri from unauthorized use, XSS, CSRF, SQLi and other common vulnerabilites, the author <b>will not accept any responsibility or liability</b> on the security of this software. Kirjuri <i>can be secure</i>, <b>if it is installed and used securely</b>. A PHP application cannot be trusted to handle that for the administrator and configuring your production server is <b>your responsibility</b>.</p>

  <h1 class="page-heading">License</h1>
  <p>Kirjuri has been released under the MIT License. See the <a href="https://github.com/AnttiKurittu/Kirjuri/tree/master" target="_BLANK">GitHub repository</a> for licensing details. Kirjuri uses <a href="http://twig.sensiolabs.org" target="_BLANK">Twig</a>, <a href="http://htmlpurifier.org/" target="_BLANK">HTMLPurify</a>, <a href="http://getbootstrap.com" target="_BLANK">Bootstrap CSS</a>,
  <a href="http://fontawesome.io" target="_BLANK">Font Awesome</a>, <a href="http://www.freepik.com" target="_BLANK">Freepik image resources</a>, <a href="http://www.chartjs.org" target="_BLANK">Chart.js</a>, <a href="http://visjs.org/" target="_blank">vis.js</a>, <a href="https://www.tinymce.com" target="_BLANK">TinyMCE editor</a> and <a href="https://jquery.com" target="_BLANK">jQuery</a>.

  <hr>
  <h1 class="page-heading">Contributors</h1>
  <p>This software has been written by Antti Kurittu, who currently works as a senior specialist at the National Cyber Security Center of Finland (FICORA NCSC-FI). German localization work has been done by Dennis Schreiber. If you are interested in contributing, giving feedback or just letting me know you use and enjoy Kirjuri - please <a href="mailto:antti.kurittu@gmail.com">Send me an email</a>! Want to try to hack Kirjuri? Use the demo installation <a href="/hackme/">here</a>. The username is "hacker" and the password is "hunter2". Let me know if you find the secret and how you did it.</p>
  <a href="https://github.com/AnttiKurittu/Kirjuri" target="_BLANK" class="btn btn-info"><i class="fa fa-github"></i> Kirjuri on GitHub</a> <a href="https://twitter.com/AnttiKurittu" target="_BLANK" class="btn btn-info"><i class="fa fa-twitter"></i> Me on Twitter</a>
  <hr>

  <!-- Begin MailChimp Signup Form -->
  <link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
  <style type="text/css">
  	#mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }
  	/* Add your own MailChimp form style overrides in your site stylesheet or in this style block.
  	   We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
  </style>
  <div id="mc_embed_signup">
  <form action="//kurittu.us14.list-manage.com/subscribe/post?u=5860442d0016d2cb516f126fe&amp;id=64cc6cad76" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
      <div id="mc_embed_signup_scroll">
  	<h2>Subscribe to our mailing list</h2>
  <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
  <div class="mc-field-group">
  	<label for="mce-EMAIL">Email Address  <span class="asterisk">*</span>
  </label>
  	<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
  </div>
  	<div id="mce-responses" class="clear">
  		<div class="response" id="mce-error-response" style="display:none"></div>
  		<div class="response" id="mce-success-response" style="display:none"></div>
  	</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
      <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_5860442d0016d2cb516f126fe_64cc6cad76" tabindex="-1" value=""></div>
      <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
      </div>
  </form>
  </div>

  <!--End mc_embed_signup-->

      </div>
  </div>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-54269518-2', 'auto');
  ga('send', 'pageview');

</script>

  </body>
<html>
