log "message" do
  message "Installing TiVo File Decoder v0.3pre4"
  level :info
end

apt_package "build-essential" do
  options "--no-install-recommends"
  action  :install
end

remote_file "/usr/src/tivodecode-0.3pre4.tar.gz" do
  source "http://downloads.sourceforge.net/project/kmttg/tools/tivodecode-0.3pre4.tar.gz"
  notifies :run, "bash[install_tivodecode]", :immediately
end

bash "install_tivodecode" do
  user "root"
  cwd "/usr/src/"
  code <<-END
    tar -zxf tivodecode-0.3pre4.tar.gz
    (cd tivodecode-0.3pre4/ && ./configure && make && make install)
  END
  action :nothing
end

apt_package "build-essential" do
  action  :remove
end
