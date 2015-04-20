tivo_handbrake_installed = "#{Chef::Config[:file_cache_path]}/tivo_handbrake_installed"

if ::File.exists?(tivo_handbrake_installed)
  log "message" do
    message "Handbrake previously installed"
    level :info
  end
else
  include_recipe "tivo::_install_handbrake"
end

file tivo_handbrake_installed do
  owner "root"
  group "root"
  mode "0644"
  action :create_if_missing
end
