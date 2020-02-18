# Z-API
Z-API is a PHP Class that aim to provide an easy way to fetch data from Zone-Annuaire.com ( any other website with the same template would work with few edits )
* Easy to use
* Bypass zt-protect.com
# How to Use
1. Import the class
```php
require'class.zoneannuaire.php';
$obj = new ZoneAnnuaire();
   ```
2. Make a Search
```php
$list = $obj->getList('Reine des Neiges');
   ```
3. Fetch data from a specific ID
```php
$obj->getInformation($list[0]['id']);
   ```
