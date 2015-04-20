php_hhvm_installed = "#{Chef::Config[:file_cache_path]}/php_hhvm_installed"

if ::File.exists?(php_hhvm_installed)
  log "message" do
    message "HHVM previously installed"
    level :info
  end
else
  include_recipe "php::_install_hhvm"
end

file php_hhvm_installed do
  owner "root"
  group "root"
  mode "0644"
  action :create_if_missing
end
