[![Build Status](https://travis-ci.org/jimlind/TiVampyre.png?branch=master)](https://travis-ci.org/jimlind/TiVampyre)

###Installation

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

    php console db-setup
    php console get-shows
    php console db-destroy

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