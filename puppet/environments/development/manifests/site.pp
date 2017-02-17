class { 'remi':
  remi_safe_enabled  => 1,
  remi_php56_enabled => 1,
}

file {
  '/var/log/messages':
    mode => 'a+r';
  '/var/log/httpd':
    mode    => 'o=rx',
    require => Package['httpd'];
  ['/var/log/httpd/access_log', '/var/log/httpd/error_log']:
    mode    => 'g=r,o=r',
    require => Package['httpd'];
}
package {
  ['htop', 'atop', 'nc', 'telnet', 'tcpdump', 'lsof']:
    require => Yumrepo[epel],
    ensure  => present;
}

package {
  ['php', 'php-cli', 'php-pdo', 'php-mysqlnd', 'php-bcmath', 'php-mbstring', 'php-posix', 'php-dom', 'php-pecl-xdebug', 'php-opcache']:
    require => Yumrepo[remi],
    notify  => Service['httpd'],
    ensure  => latest;
}

augeas {
  'php.ini':
    context => '/files/etc/php.ini',
    changes => [
      "set Date/date.timezone 'Australia/Adelaide'",
      "set PHP/error_log syslog",
      "set PHP/opcache.validate_timestamps On",
      "set runkit/runkit.internal_override On",
      "set xdebug/xdebug.remote_enable 1",
      "set xdebug/xdebug.remote_connect_back 1"],
    notify  => Service['httpd'];
}

class { '::mysql::server':
  restart          => true,
  override_options => {
    'mysqld' => {
      'bind-address' => '0.0.0.0'
    }
  }
}

mysql::db {
  'tests':
    user     => 'root',
    host     => '%',
    password => '',
    grant    => ['ALL'];
  'unit':
    user     => 'root',
    host     => '%',
    password => '',
    grant    => ['ALL'];
  'symfony':
    user     => 'root',
    host     => '%',
    password => '',
    grant    => ['ALL'];
}

class {
  'rabbitmq':
    admin_enable      => true,
    delete_guest_user => false,
    repos_ensure      => true,
    package_provider  => 'yum',
    service_ensure    => running;
}

rabbitmq_plugin {
  ['rabbitmq_shovel',
    'rabbitmq_shovel_management',
    'rabbitmq_federation',
    'rabbitmq_federation_management',
    'rabbitmq_management_visualiser',
    'rabbitmq_tracing']:
    notify => Service['rabbitmq-server'];
}

rabbitmq_user { 'admin':
  admin    => true,
  password => 'admin',
}

rabbitmq_user_permissions { 'admin@/':
  configure_permission => '.*',
  read_permission      => '.*',
  write_permission     => '.*',
}

include composer

file {
  '/etc/httpd/conf.d/welcome.conf':
    ensure => absent,
    notify => Service['httpd'];
  '/etc/httpd/conf.d/root.conf':
    content => 'DocumentRoot /var/www/html/web
  <Directory "/var/www/html/web">
    AllowOverride All
    # Allow open access:
    Require all granted
  </Directory>
',
    notify  => Service['httpd'];
}

package {
  'httpd':
    ensure => present,
    notify => Service['httpd'];
}

service {
  'httpd':
    ensure => running,
    enable => true;
  ['firewalld', 'iptables']:
    ensure => stopped,
    enable => false;
}
