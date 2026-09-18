#!/bin/sh
set -e

# Railway fournit le port d'écoute via la variable PORT (souvent différent à chaque déploiement).
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# data/ (base SQLite) et uploads/ (bulletins) sont destinés à être montés en volume Railway :
# on s'assure juste qu'ils existent et sont écrivables par Apache au démarrage.
mkdir -p /var/www/html/data /var/www/html/uploads/bulletins
chown -R www-data:www-data /var/www/html/data /var/www/html/uploads

exec apache2-foreground
