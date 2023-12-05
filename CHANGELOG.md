2.0.0
-----

* Drop support for Symfony < 6.4
* Drop support for PHP < 8.1, test against PHP 8.3
* Drop deprecation layer; if a custom Command class is used, it must implement the CommandInterface

1.3.0
-----

* Add `getLastCommit()` method
* Improve tests & fix them locally
* Increase PHPStan level to 7
* Deprecate passing a command class that does not implement `Leapt\GitWrapper\CommandInterface`
* Throw an exception if passing a command class that does not have a `run()` method

1.2.0
-----

* Upgrade dev dependencies
* Allow Symfony 7

1.1.0
-----

* Allow Symfony 6
* Test against PHP 8.1

1.0.0
-----

First usable version.
