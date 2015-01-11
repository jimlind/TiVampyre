include_recipe "apt"
include_recipe "hhvm"

include_recipe "php::composer"
include_recipe "queue::beanstalkd"

include_recipe "tivo::avahi-utils"
include_recipe "tivo::gpac"
include_recipe "tivo::tivodecode"

include_recipe "tivo::aacgain"
include_recipe "tivo::handbrake"
include_recipe "tivo::comskip"
include_recipe "tivo::atomicparsley"

# Disk space is limited
execute "apt-get autoremove"
execute "apt-get autoclean"