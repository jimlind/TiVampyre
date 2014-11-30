[![Build Status](https://travis-ci.org/jimlind/TiVampyre.png?branch=master)](https://travis-ci.org/jimlind/TiVampyre)

###Installation

####Prerequisites

I run Ubuntu 14.04 LTS so if you want something else you are on your own.

TiVampyre utilizes my own [tivo-php](https://github.com/jimlind/tivo-php) library so you'll
want to follow the link and make sure you've fulfilled the prerequisites.

HHVM is the fastest most compatible PHP runtime so install it following the
[directions](https://github.com/facebook/hhvm/wiki/Prebuilt-Packages-on-Ubuntu-14.04).
```sh
wget -O - http://dl.hhvm.com/conf/hhvm.gpg.key | sudo apt-key add -
echo deb http://dl.hhvm.com/ubuntu trusty main | sudo tee /etc/apt/sources.list.d/hhvm.list
sudo apt-get update
sudo apt-get install hhvm
```

Nginx is the best way to run HHVM as a web server.
```sh
sudo apt-get install nginx
sudo /usr/share/hhvm/install_fastcgi.sh
```

HandBrake is the easiest video transcoder, but the default package is broken. Luckily,
the [snapshots](https://launchpad.net/~stebbins/+archive/ubuntu/handbrake-snapshots) work great.
```sh
sudo add-apt-repository ppa:stebbins/handbrake-snapshots
sudo apt-get update
sudo apt-get install handbrake-cli
```

Keep the Audio in check.
```sh
sudo add-apt-repository ppa:stefanobalocco/ppa
sudo apt-get update
sudo apt-get install aacgain
sudo add-apt-repository ppa:stefanobalocco/ppa --remove
```

GPAC is needed for muxing and demuxing.
```sh
sudo apt-get install gpac
```

Install Composer for PHP dependencies.
```sh
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Install dependencies with Composer.
```sh
hhvm /usr/local/bin/composer install --no-dev --optimize-autoloader
```

Install Wine and ComSkip
```sh
sudo apt-get install unzip
sudo apt-get install wine --no-install-recommends
sudo apt-get autoremove
sudo apt-get clean

cd ~
wget http://www.kaashoek.com/files/comskip81_069.zip
unzip comskip81_069.zip -d comskip
sudo mv comskip /opt/
```

###Setup Notes

Checkout this repository.
I like to put it in the /var/www/TiVampyre directory.

You will need to check the permissions on the database file and db directory.
It is created by the command line and accessed by the apache user.
TODO: Automate some part of this process.

###Development Environment

HHVM...
In my dev environment I just use the version of HHVM from PuPHPet and I'm not too
particular because they are close enough for my uses.

Composer...
The dev environment has it installed as an executable
automatically.

I've committed the Vagrantfile and the puphpet folders that it uses that I use for
development. Using the version of VirtualBox and Vagrant documented below.

```
VirtualBox 4.3.14
Vagrant 1.6.3
```

HHVM doesn't throw all errors to the screen because of the way the JIT bits work so
you'll want to watch the error log.

```sh
tail -f /var/log/hhvm/error.log
```

###Run a Command

    hhvm console db-destroy
    hhvm console db-setup
    hhvm console db-truncate
    hhvm console download XXXXXXX
    hhvm console get-shows
    hhvm console list-shows

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