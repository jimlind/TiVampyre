apt_package 'beanstalkd' do  
  options '--no-install-recommends'
  action  :install
end