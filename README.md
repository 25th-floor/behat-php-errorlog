behat-php-errorlog
==================

PHP Error Logger Context for Behat 3.

The Context extends from Mink so you need a mink driver running. Basically it creates a log file for every Test Scenario
in the beforeScenario Hook and after the Scenario it looks into the log file if there is something present.

To get Errors logged within these Error Log Files the application itself needs to be adjusted. This Context sends the
necessary information to your application but the application itself needs to know what to do with it.

For this the application and the behat runner must be on the same machine, so both can access the same error log file.


Selenium
--------

Selenium does not provide a way to send such meta information via Headers or something else. The easiest way to get
information to your application therefore is to call an url (which must be provided in the configuration of the context)
with the necessary information and handle everything else within the application. (for example via cookie).

In our case, the Context will call the provided url with the full path to the error log file.


Installation
============

Just install via composer


Configuration
=============

behat
-----

Add the context to your behat confguration file. There are three Constructor Parameters:

 * `directory` it needs the Directory where to put the log files.

 * `url` optional but required for selenium test scenarios. See Selenium Part

 * `size` optional, definition after which size it will no longer output the contents of the error log file to your output. Defaults to 2014, use 0 to disable it.


your application
----------------

Depending if it's a normal Scenario or Selenium you need to adjust your application to work with the context.

For Selenium Scenarios you have to provide the url and then save the error_log, for example within a cookie. Other
Scenarios will get the absolute path to the error log using the request Headser `X-BEHAT-PHP-ERROR-LOG`.

With the absolute path to the error log one must set the php configuration:

```php
ini_set("log_errors", 1);
ini_set("error_log", $path);
```

Scenarios
---------

You don't need to adjust anything in your Scenarios to get this working. But there is way if you don't want the Context
to run at a specific Scenario or a whole Feature File. You can use the tag `@ignore-php-logging` if you don't want any
php action to be logged.

Todo
====

* Get Wep Api working

* maybe don't depend that behat and application are on the same machine?
