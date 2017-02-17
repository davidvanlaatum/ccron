#!/bin/sh

[ -e /tmp/vagrant-puppet/environments/common/modules/rabbitmq ] || puppet module install puppetlabs-rabbitmq --version 5.6.0 --target-dir /tmp/vagrant-puppet/environments/common/modules/
[ -e /tmp/vagrant-puppet/environments/common/modules/mysql ] || puppet module install puppetlabs-mysql --version 3.10.0 --target-dir /tmp/vagrant-puppet/environments/common/modules/
[ -e /tmp/vagrant-puppet/environments/common/modules/augeasproviders_sysctl ] || puppet module install herculesteam/augeasproviders_sysctl --target-dir /tmp/vagrant-puppet/environments/common/modules/
[ -e /tmp/vagrant-puppet/environments/common/modules/epel ] || puppet module install stahnma-epel --version 1.2.2 --target-dir /tmp/vagrant-puppet/environments/common/modules/
[ -e /tmp/vagrant-puppet/environments/common/modules/composer ] || puppet module install willdurand-composer --version 1.2.5 --target-dir /tmp/vagrant-puppet/environments/common/modules/
[ -e /tmp/vagrant-puppet/environments/common/modules/remi ] || puppet module install hfm-remi --version 1.4.0 --target-dir /tmp/vagrant-puppet/environments/common/modules/
which augtool &>/dev/null || yum install -y augeas
