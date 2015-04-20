tivo_comskip_installed = "#{Chef::Config[:file_cache_path]}/tivo_comskip_installed"

if ::File.exists?(tivo_comskip_installed)
  log "message" do
    message "Comskip previously installed"
    level :info
  end
else
  include_recipe "tivo::_install_comskip"
end

file tivo_comskip_installed do
  owner "root"
  group "root"
  mode "0644"
  action :create_if_missing
end