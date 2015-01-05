log "message" do
  message "Installing Handbrake v0.9.9"
  level :info
end

apt_repository "stebbins-ppa" do
  uri          "http://ppa.launchpad.net/stebbins/handbrake-releases/ubuntu"
  distribution "trusty"
  components   ["main"]
  keyserver    "keyserver.ubuntu.com"
  key          "816950D8"
  action       :add
end

apt_package "handbrake-cli" do
  options "--no-install-recommends"
  version "0.10.0ppa1~trusty1"
  action  :install
end

apt_repository "stebbins-ppa" do
  action :remove
end
