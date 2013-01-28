# Jirafe Module for Prestashop

This project builds a Prestashop module which automatically integrates Jirafe analytics into the Prestashop ecommerce platform.

Follow the installation instructions below.

## Install

### Git Installation

Clone git project into your shop module directory:

    git clone git://github.com/jirafe/prestashop-module.git my_shop/modules/jirafe

To enable the Jirafe module for Prestashop, log into prestashop, click on **Modules** tab, and open **Stats and Analytics** item.
Follow **Install** next to Jirafe Analytics module.

### Basic Installation

You can find most recent version in [github tag section](https://github.com/jirafe/prestashop-module/tags) follow the
download link which you prefer packaged as **tar.gz** or **zip**.

Extract the contents in your **(path-to-shop)/modules** directory, be sure to rename it into **jirafe**. The resulted directory
structure of your shop shoul look like:

    MyPrestaShop
        classes
        modules
            jirafe
                jirafe_base.php
                jirafe.php
                logo.png
                ...
            autoupgrade
            bankwire
            ...
    ...

To enable the Jirafe module for Prestashop, log into prestashop, click on **Modules** tab, and open **Stats and Analytics** item.
Follow **Install** next to Jirafe Analytics module.

## Uninstall Jirafe Module

### Standard

To remove Jirafe module for Prestashop, log into prestashop, click on **Modules** tab, and open **Stats and Analytics** item.
Follow **uninstall** next to Jirafe Analytics module.

### Manual

To manually remove the plugin data from the prestashop database:

    DELETE FROM ps_configuration WHERE name LIKE 'JIRAFE%';
    DELETE FROM ps_module WHERE name = 'jirafe';

To remove the module from the prestashop platform:

    rm -rf (path_to_prestashop)/modules/jirafe

## Support

- Product [Feedback](https://getsatisfaction.com/jirafe)
- Report [a bug](https://jirafe.com/support)

