composer_file = '/usr/local/bin/composer.phar'

apt_package "git" do  
  options "--no-install-recommends"
  action  :install
end

remote_file composer_file do
  source "https://getcomposer.org/composer.phar"
  mode '0755'
  notifies :run, "bash[install_composer_vendor]", :immediately
end

bash "install_composer_vendor" do
  cwd "#{node['project_dir']}"
  code <<-END
    composer.phar install
  END
  action :nothing
end