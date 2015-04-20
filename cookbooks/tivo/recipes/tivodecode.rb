tivo_tivodecode_installed = "#{Chef::Config[:file_cache_path]}/tivo_tivodecode_installed"

if ::File.exists?(tivo_tivodecode_installed)
  log "message" do
    message "TiVo File Decoder previously installed"
    level :info
  end
else
  include_recipe "tivo::_install_tivodecode"
end

file tivo_tivodecode_installed do
  owner "root"
  group "root"
  mode "0644"
  action :create_if_missing
end
