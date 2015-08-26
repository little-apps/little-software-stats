Little Software Stats
=====================

Build status: [![Build Status](https://travis-ci.org/little-apps/little-software-stats.svg?branch=master)](https://travis-ci.org/little-apps/little-software-stats)

Little Software Stats is a web application developed by Little Apps which provides a open source runtime intelligence solution.

Little Software Stats is the first free and open source application that allows software developers to monitor how their software is being used. It is developed using PHP & MySQL which allows it to be ran on most web servers. 

### Requirements ###

The following is required to run Little URL Shortener properly:

* Web server (See [notes](#notes))
* [PHP v5.3](http://php.net/) or higher (See [notes](#notes))
* [MySQL](http://www.mysql.com/) or [MariaDB](https://www.mariadb.org) v5.5 or higher
* PHP extensions:
 * [Original MySQL API](http://php.net/manual/en/book.mysql.php) (See [notes](#notes))
 * [MySQL Improved](http://php.net/manual/en/book.mysqli.php) (See [notes](#notes))
 * [GD (Image Processing)](http://php.net/manual/en/book.image.php)
 * [Hash](http://php.net/manual/en/book.hash.php)
 * [Session](http://php.net/manual/en/book.session.php)
 * [JSON](http://php.net/manual/en/book.json.php)
 * [SimpleXML](http://php.net/manual/en/book.simplexml.php)
 * [ZLib](http://php.net/manual/en/book.mbstring.php)
 * [Multibyte String](http://php.net/manual/en/book.mbstring.php)

#### Notes ####
 * URL rewrite support with the web server is recommended, but not required
 * PHP v5 or higher will work, but PHP v5.3 or higher is recommended.
 * MySQL Improved will be used if it is installed (as the original MySQL API is deprecated). If it is not installed, the original MySQL API will be used instead.
 * The appropriate web server and memory needed will depend on the scale of your software. For example, if your using Little Software Stats with a highly used software program then you may want to consider running Nginx or Lighttpd with lots of RAM. You should consider using something like suPHP which runs PHP at the user-level instead of the root user or the default PHP user.

### Installation ###
1. Upload and extract Little Software Stats to your web server
2. Create a MySQL database with a user that has full privileges to access and modify it
3. Go to http://example.com/install/ and follow the steps
4. Remove or rename the install directory
4. Integrate Little Software Stats with your software and track your users

### Updating ###
1. Make backup from web server of Little Software Stats directory and database
2. Upload and extract the updated Little Software Stats archive to your web server
3. Go to http://example.com/install/update.php and follow the steps
4. Remove or rename the install directory
5. Little Software Stats should now be updated

### Example ###

If you would like to see Little Software Stats in action, please check out [demo.little-software-stats.com](http://demo.little-software-stats.com). This website is in sync with every update that is pushed to this Git and there is only read-only access.

### Release Notes ###
* 0.1
 * First public release
 
### To Do ###
 * Remove unneeded JavaScript files
 * Translations for various languages
 * Composer support
 * ~~Update PHPMailer~~
 * SQLite support (through PDO)
 * Smarty integration
 * Pluggable (support for plugins)
 * Multiple logins
 * Login permissions
 * Mobile version
 * Better website template
 * Data export
 * Data export API
 * Automated reports
 * Two factor authentication support (Google Authenticator, Authy, etc)
 * Send notification via e-mail when someone logs in
 * Add TLS and SSL support to SMTP configuration options

### License ###
Little Software Stats itself is licensed under the GNU General Public License v3 and the Little Software Stats libraries are licensed under the GNU Lesser General Public License. 

### Show Your Support ###
Little Apps relies on people like you to keep our software running. If you would like to show your support for Little Software Stats, then you can [make a donation](https://www.little-apps.com/?donate) using PayPal, Payza or Bitcoins. Please note that any amount helps (even just $1).

### Credits ###

Little Apps would like to thank the following for helping Little Software Stats:

 * [PHPMailer by Jim J.](https://github.com/PHPMailer/PHPMailer/)
 * [DataTables by Allan Jardine](http://www.datatables.net)
 * [HighCharts by HighSoft](http://www.highcharts.com/)
 * [jQuery Notify Bar by Dmitri Smirnov](https://github.com/dknight/jQuery-Notify-bar)
 * [jQuery Date by Jörn Zaefferer and Brandon Aaron](http://brandon.aaron.sh/)
 * [jQuery Datepicker by Kelvin Luck](https://github.com/vitch/jQuery-datepicker)
 * [bPopup by Bjoern Klinggaard](http://dinbror.dk/bpopup)
 * [jQuery ScrollTo by Ariel Flesler](http://flesler.blogspot.ca/2007/10/jqueryscrollto.html)
 * jQuery SelectBox by Sadri Sahraoui
 * [jQuery Tooltip by Joern Zaefferer](http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/)
 * [HighChartsPHP by Portugalmail Comunicações](http://www.goncaloqueiros.net/highcharts.php)
 * [Portable PHP password hashing framework (PHPass) by Openwall](http://www.openwall.com/phpass/)
 * [GeoIP Database by MaxMind](https://www.maxmind.com/en/geoip2-databases)
 * [GeoIPs API by Bianet Solution Ltd.](http://www.geoips.com/en)
 * [Minify by Steve Clay and Ryan](https://code.google.com/p/minify/)
 * [reCAPTCHA by Google](http://www.google.com/recaptcha/intro/index.html)
 
### More Info ###

For more information, please visit [http://www.little-software-stats.com](http://www.little-software-stats.com)
