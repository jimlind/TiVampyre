apt_package 'monit' do  
  options '--no-install-recommends'
  action  :install
end