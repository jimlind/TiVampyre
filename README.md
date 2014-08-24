[![Build Status](https://travis-ci.org/jimlind/TiVampyre.png?branch=master)](https://travis-ci.org/jimlind/TiVampyre)

###Installation

####Server

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

Since this just runs on my local network and isn't considered production I run it it on HHVM.

Here's all the junk I had to install on my box running the most recent (at the 
time of printing) version of Ubuntu.

* libapache2-mod-php5
* php5-cli
* php5-curl
* php5-gd
* php5-json
* php5-sqlite
* avahi-utils

###Setup Notes

You will need to check the permissions on the database file and db directory.
It is created by the command line and accessed by the apache user.
This might be able to be automated, at the very least easy to check.

###Run a Command

    hhvm console db-setup
    hhvm console get-shows
    hhvm console db-destroy

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