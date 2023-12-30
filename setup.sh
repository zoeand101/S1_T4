#!/bin/bash

# Determine package manager command
if [ -x "$(command -v yum)" ]; then
    PKG_MANAGER="yum"
elif [ -x "$(command -v apt-get)" ]; then
    PKG_MANAGER="apt-get"
else
    echo "Error: Unsupported package manager."
    exit 1
fi

# Clone the repository
git clone https://github.com/zoeand101/S1_T4.git

# Update packages
sudo $PKG_MANAGER update

# Move into the cloned directory
cd S1_T4 || exit

# Delete the .gitattributes file
rm .gitattributes

# Remove the .git folder (including all Git version control information)
rm -rf .git

# Download and install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'e21205b207c3ff031906575712edab6f13eb0b361f2085f1f1237b7126d785e826a450292b6cfd1d64d92e6563bbde02') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

# Install PHP GD and its dependencies
sudo $PKG_MANAGER install -y php-gd
if [ "$PKG_MANAGER" = "apt-get" ]; then
    sudo $PKG_MANAGER install -y libgd-dev # Ubuntu-specific package for gd-devel
elif [ "$PKG_MANAGER" = "yum" ]; then
    sudo $PKG_MANAGER install -y gd-devel # CentOS-specific package for gd-devel
fi

# Run Composer commands
composer update
composer install

# Make S1L3NT.php executable if it exists
if [ -f "S1L3NT.php" ]; then
    chmod +x S1L3NT.php
fi
