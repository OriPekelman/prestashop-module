# To manually remove the plugin data from the prestashop database:
DELETE FROM configuration WHERE name LIKE 'JIRAFE%';
DELETE FROM module WHERE name = 'jirafe';
