apt_package 'monit' do  
  options '--no-install-recommends'
  action  :install
end

cookbook_file '/etc/monit/monitrc' do
   owner 'root'
   group 'root'
   mode '0600'
   source 'monitrc'
end

service 'monit' do
  action :restart
end
