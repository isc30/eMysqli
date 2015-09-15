# Extended Mysqli (eMysqli)
Extended Mysqli class that allows calling PROCEDURES, FUNCTIONS and VIEWS<br />
By: isc30 -> ivansanzcarasa@gmail.com / https://github.com/isc30

##Getting eMysqli object:
```php
$eMysqli = new eMysqli($host, $username, $password, $database);
  or
$eMysqli = getMysqlConnection();
```

##Calling a procedure:
```php
$eMysqli->callProcedure( 'PROCEDURE_NAME*', [INPUT], [OUTPUT] );
```
 > **Example:**
```php
$result = $eMysqli->callProcedure('prTestLogin', [$email, $password], ['@ok', '@userId']);
$result => Array (
      [pr] => ( )
      [out] => ( [@ok] => true, [@userId] => 23142 )
)
```

##Calling a function:
```php
$eMysqli->callFunction( 'FUNCTION_NAME*', [INPUT] );
```
 > **Example:**
```php
$result = $eMysqli->callFunction('fuGetSum', [26, 57]);
$result => 83
```

##Calling a view:
```php
$eMysqli->callView( 'VIEW_NAME*' );
```
 > **Example:**
```php
$result = $eMysqli->callView('viShowUsers');
$result => Array (
      [0] => (
            [id] => 1,
            [username] => 'Paco'
      )
      [1] => (
            [id] => 2,
            [username] => 'Juan'
      )
)

##Version history

###v1.1.0
* Changed procedure call method when it returns 2 or more SELECTs

###v1.0.1
* Fix some bugs

###v1.0.0
* Procedure support
* Function support
* View support
