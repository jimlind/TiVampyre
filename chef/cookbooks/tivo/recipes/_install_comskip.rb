log "message" do
  message "Installing Comskip and Wine"
  level :info
end

apt_package "wine" do  
  options "--no-install-recommends"
  action  :install
end

apt_package "unzip" do  
  options "--no-install-recommends"
  action  :install
end

remote_file "/opt/comskip81_069.zip" do
  source "http://www.kaashoek.com/files/comskip81_069.zip"
  notifies :run, "bash[install_program]", :immediately
end

bash "install_program" do
  user "root"
  cwd "/opt/"
  code <<-END
    unzip comskip81_069.zip -d comskip
  END
  action :nothing
end
