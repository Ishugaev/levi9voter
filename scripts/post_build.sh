pwd
sudo rm -rf /var/www/*
tar -xzf artifact.tar.gz -C /var/www/
rm -rf *.tar.gz

cd /var/www

php composer.phar install
php app/console doctrine:database:drop --env=dev --force
php app/console doctrine:schema:create  --env=dev
php app/console doctrine:fixtures:load
php app/console assets:install web/

sudo chown -R  www-data:www-data /var/www
sudo chmod -R 777 app/cache
sudo chmod -R 777 app/logs