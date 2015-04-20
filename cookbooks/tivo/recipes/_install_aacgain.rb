log "message" do
  message "Installing AACgain v1.9"
  level :info
end

apt_repository "stefanobalocco-ppa" do
  uri          "http://ppa.launchpad.net/stefanobalocco/ppa/ubuntu"
  distribution "trusty"
  components   ["main"]
  keyserver    "keyserver.ubuntu.com"
  key          "184890B5"
  action       :add
end

apt_package "aacgain" do  
  version "1.9-1~trusty+1"
  action  :install
end

apt_repository "stefanobalocco-ppa" do
  action :remove
end
