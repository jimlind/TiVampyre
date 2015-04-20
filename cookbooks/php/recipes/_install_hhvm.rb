log "message" do
  message "Installing Prebuilt HHVM package for Ubuntu"
  level :info
end

apt_repository "hhvm-dl" do
  uri          "http://dl.hhvm.com/ubuntu"
  distribution "trusty"
  components   ["main"]
  keyserver    "keyserver.ubuntu.com"
  key          "5a16e7281be7a449"
  action       :add
end

apt_package "hhvm" do
  action  :install
end

apt_repository "hhvm-dl" do
  action :remove
end