Configuring LDAPS for use with Kirjuri:
* specify server as ldaps://my.server.com
* ensure ldap is configured
 - Ubuntu 16.04 uses /etc/ldap/ldap.conf
 - TLS_REQCERT allow
 - if server public key is properly installed use TLS_REQCERT hard
 - server key is required to defend against MITM
* restart php7.0-fpm service
* See logs/kirjuri.log and include_functions.php for troubleshooting if you run into LDAP(S) issues.

