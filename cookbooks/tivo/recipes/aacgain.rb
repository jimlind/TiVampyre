tivo_aacgain_installed = "#{Chef::Config[:file_cache_path]}/tivo_aacgain_installed"

if ::File.exists?(tivo_aacgain_installed)
  log "message" do
    message "AACgain previously installed"
    level :info
  end
else
  include_recipe "tivo::_install_aacgain"
end

file tivo_aacgain_installed do
  owner "root"
  group "root"
  mode "0644"
  action :create_if_missing
end
