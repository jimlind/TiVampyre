[![Build Status](https://travis-ci.org/jimlind/TiVampyre.png?branch=master)](https://travis-ci.org/jimlind/TiVampyre)

###Installation

####Services

I run Ubuntu 14.04 LTS so if you want something else you are on your own.

HHVM is the fastest most compatible PHP runtime so install it following the 
[directions](https://github.com/facebook/hhvm/wiki/Prebuilt-Packages-on-Ubuntu-14.04).

```sh
wget -O - http://dl.hhvm.com/conf/hhvm.gpg.key | sudo apt-key add -
echo deb http://dl.hhvm.com/ubuntu trusty main | sudo tee /etc/apt/sources.list.d/hhvm.list
sudo apt-get update
sudo apt-get install hhvm
```

HandBrake is the easiest video transcoder, but the default package is broken. Luckily,
the [snapshots](https://launchpad.net/~stebbins/+archive/ubuntu/handbrake-snapshots) work great.

```sh
sudo add-apt-repository ppa:stebbins/handbrake-snapshots 
sudo apt-get update
sudo apt-get install handbrake-cli
```

The Avahi daemon is a good way to make it easy to locate the server on the network.

```sh
sudo apt-get install avahi-utils
```

Nginx is the best way to run HHVM as a web server. 
```sh
sudo apt-get install nginx
sudo /usr/share/hhvm/install_fastcgi.sh

```

###Setup Notes

Checkout this repository. I like to put it in the /var/www/TiVampyre directory.

You will need to check the permissions on the database file and db directory.
It is created by the command line and accessed by the apache user.
This might be able to be automated, at the very least easy to check.

###Development Environment

I've committed the Vagrantfile and the puphpet folders that it uses that I use for 
development. Using the version of VirtualBox and Vagrant documented below.

```
VirtualBox 4.3.14
Vagrant 1.6.3
```

###Run a Command

    hhvm /var/www/TiVampyre/console db-setup
    hhvm /var/www/TiVampyre/console get-shows
    hhvm /var/www/TiVampyre/console db-destroy

###Schedule Crontab
```
02,32 * * * * hhvm /var/www/TiVampyre/console get-shows >/dev/null 2>&1    
```

###Unit Tests

    Straight Tests
    $ vendor/bin/phpunit

    Test Coverage to the Terminal
    $ vendor/bin/phpunit --coverage-text

    Test Coverage to HTML
    $ vendor/bin/phpunit --coverage-html ./
    *Then push to the gh-pages branch*

###Code Sniffing

    $ vendor/bin/phpcs src/

###Run Composer from HHVM for Speed

	hhvm ~/bin/composer.phar install --no-dev --optimize-autoloader

###Configuration Options

./config/tivampyre.json

####tivampyre_mak (Media Access Key)**

    This is the magic ID that tells your TiVo that the person trying to interact
    with it is actually a person with physical access to the device.

    Find it via:
    TiVo Central -> Messages and Set Up -> Account and System Information

####tivampyre_comskip_path (Commercial Skip Executable Path)***

    This should be where comskip.exe and compskip.ini are found on your server.  I
    like to keep that stuff in /opt/ and some people like to keep in is the
    /usr/local/bin/ directory.  Doesn't matter to me.

    Include trailing slash.

####tivampyre_working_directory (Working Directory)**

    This should be where everything is going to be downloaded to and processed from.
    Where the finished products will also be found. Everything will get stored here,
    nothing in the /tmp/ directory.

    Include trailing slash.