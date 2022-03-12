Depending on your servers abilities you can choose between two types of authentication:

* [.htaccess](#htaccess)
* [RSS-Bridge Authentication](#rss-bridge-authentication)

**General advice**:

- Make sure to use a strong password, no matter which solution you choose!
- Enable HTTPS on your server to ensure your connection is encrypted and secure!

## .htaccess

.htaccess files are commonly used to restrict access to files on a web server. One of the features of .htaccess files is the ability to password protect specific (or all) directories. If setup correctly, a password is required to access the files.

The usage of .htaccess files requires three basic steps:

1) [Enable .htaccess](#enable-htaccess)
2) [Create a .htpasswd file](#create-a-htpasswd-file)
3) [Create a .htaccess file](#create-a-htaccess-file)

### Enable .htaccess

This process depends on the server you are using. Some providers may require you to change some settings, or place/change some file. Here are some helpful links for your server (please add your own if missing :sparkling_heart:)

- Apache: http://ask.xmodulo.com/enable-htaccess-apache.html

### Create a .htpasswd file

The `.htpasswd` file contains the user name and password used for login to your web server. Please notice that the password is stored in encrypted form, which requires you to encrypt your password before creating the `.htpasswd` file!

Here are three ways of creating your own `.htpasswd` file:

**1) Example file**

Example `.htpasswd` file (user name: "test", password: "test"):

```.htpasswd
test:$apr1$a52u9ILP$XTNG8qMJiEXSm1zD0lQcR0
```

Just copy and paste the contents to your `.htpasswd` file.

**2) Online generator (read warning!)**

You can create your own `.htpasswd` file online using a `.htpasswd` generator like this: https://www.htaccesstools.com/htpasswd-generator/

**WARNING!**
- Never insert real passwords to an online generator!

**3) Generate your own password**

Another way to create your own `.htpasswd` file is to run this script on your server (it'll output the data for you, you just have to paste it int a `.htpasswd` file):

```PHP
<?php
// Password to be encrypted for a .htpasswd file
$clearTextPassword = 'some password';

// Encrypt password
$password = crypt($clearTextPassword, base64_encode($clearTextPassword));

// Print encrypted password
echo $password;
?>
```

>source: https://www.htaccesstools.com/articles/create-password-for-htpasswd-file-using-php/

### Create a .htaccess file

The `.htaccess` file is used to specify which directories are password protected. For that purpose you should place the file in whatever directory you want to restrict access. If you want to restrict access to RSS-Bridge in general, you should place the file in the root directory (where `index.php` is located).

Two parameters must be specified in the `.htaccess` file:

* AuthName
* AuthUserFile

`AuthName` specifies the name of the authentication (i.e. "RSS-Bridge"). `AuthUserFile` defines the **absolute** path to a `.htpasswd` file.

Here are two ways of creating your own `.htaccess` file:

**1) Example file**

```.htaccess
AuthType Basic
AuthName "My Protected Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

Notice: You must change the `AuthUserFile` location to fit your own server (i.e. `/var/www/html/rss-bridge/.htpasswd`)

**2) Online generator**

You can use an online generator to create the file for you and copy-paste it to your `.htaccess` file: https://www.htaccesstools.com/htaccess-authentication/

## RSS-Bridge Authentication

RSS-Bridge ships with an authentication module designed for single user environments. You can enable authentication and specify the username & password in the [configuration file](../03_For_Hosts/08_Custom_Configuration.md#authentication).

Please notice that the password is stored in plain text and thus is readable to anyone who can access the file. Make sure to restrict access to the file, so that it cannot be read remotely!