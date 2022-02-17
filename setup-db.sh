#!/bin/bash
export TTY

# UPDATE mysql.user SET authentication_string = PASSWORD('oracle') WHERE User = 'root' AND Host = 'localhost';
# ALTER USER 'root'@'localhost' IDENTIFIED BY 'oracle';
# UPDATE mysql.user SET authentication_string=PASSWORD('oracle') WHERE User = 'root';
# set password for 'root' = password('oracle');
# 
# The password function doesn't work.
# select password('oracle');
# 
# UPDATE mysql.user SET authentication_string = aes_encrypt('oracle') WHERE User = 'root';
# 
# # It was deprecated
# 
# # This is the solution
# ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'oracle';


# run with msudo
apt install php-mysqli

/etc/init.d/mysql stop
# sudo systemctl set-environment MYSQLD_OPTS="--skip-networking --skip-grant-tables"
export MYSQLD_OPTS="--skip-networking --skip-grant-tables"
/etc/init.d/mysql start
pen-x -shE "mysql -u root" -e "mysql>" -s "flush privileges;" -c m \
    -e "mysql>" -s "USE mysql;" -c m \
    -e "mysql>" -s "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'oracle';" -c m \
    -e "mysql>" -s "CREATE DATABASE fileindexdb;" -c m \
    -e "mysql>" -s "quit;" -c m \
    -i
# sudo killall -u mysql
/etc/init.d/mysql restart

cp -a /var/www/croogle/sphinx.conf /etc/sphinxsearch/sphinx.conf

php /var/www/croogle/update-sphinx-files.php libraries
# php /var/www/croogle/update-sphinx-files.php notes
php /var/www/croogle/update-sphinx-files.php home
php /var/www/croogle/update-sphinx-files.php sys
php /var/www/croogle/update-sphinx-files.php all-home
php /var/www/croogle/update-sphinx-files.php git
php /var/www/croogle/update-sphinx-files.php all-system
