Vagrant.configure('2') do |config|
  # config.vm.box = 'puppetlabs/centos-6.6-64-puppet'
  config.vm.box = 'puppetlabs/centos-7.2-64-puppet'
  config.vm.network :forwarded_port, guest: 80, host: 8001
  config.vm.network :forwarded_port, guest: 8000, host: 8000
  config.vm.network :forwarded_port, guest: 5672, host: 5672
  config.vm.network :forwarded_port, guest: 15672, host: 15672
  config.vm.network 'private_network', ip: '192.168.33.200'
  config.vm.synced_folder '.', '/var/www/ccron', mount_options: %w(dmode=0777 fmode=0777)
  config.vm.synced_folder '~/.composer', '/home/vagrant/.composer', mount_options: %w(dmode=0777 fmode=0777) if File.exists?(File.expand_path('~/.composer'))
  config.vm.provision 'shell', name: 'puppet modules', inline: '/var/www/ccron/puppet/installmodules.sh', preserve_order: true, run: 'always'
  config.vm.hostname = 'ccron'
  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true
  config.hostmanager.aliases = 'ccron.localdomain'
  config.vm.provision 'puppet', run: 'always' do |puppet|
    puppet.environment_path = 'puppet/environments'
    puppet.environment = 'development'
    puppet.options = '--verbose --show_diff'
    puppet.facter = {
    }
  end
  config.vm.provider 'virtualbox' do |vb|
    vb.memory = '1024'
    vb.cpus = 2
    vb.customize ['modifyvm', :id, '--vram', 1, '--ioapic', 'on']
  end
  config.vm.provider 'vmware_fusion' do |v|
    v.vmx['memsize'] = '1024'
    v.vmx['numvcpus'] = '2'
  end
  config.vm.provision 'shell', name: 'composer', privileged: false, inline: 'composer install -d /var/www/ccron', preserve_order: true, run: 'always'
  config.vm.provision 'shell', name: 'schema', privileged: false, inline: 'php /var/www/ccron/bin/console doctrine:schema:update --force', preserve_order: true, run: 'always'
end
