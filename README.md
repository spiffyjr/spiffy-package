# Spiffy\Package

[![Build Status](https://travis-ci.org/spiffyjr/spiffy-package.svg)](https://travis-ci.org/spiffyjr/spiffy-package)
[![Code Coverage](https://scrutinizer-ci.com/g/spiffyjr/spiffy-package/badges/coverage.png?s=e3d80c9767c0d5c9cc049e52a4c12b0e0bb29f1d)](https://scrutinizer-ci.com/g/spiffyjr/spiffy-package/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spiffyjr/spiffy-package/badges/quality-score.png?s=e454ad99c82766505cdc8097ec159b56ae9bba20)](https://scrutinizer-ci.com/g/spiffyjr/spiffy-package/)

## Installation

Spiffy\Package can be installed using composer which will setup any autoloading for you.

`composer require spiffy/spiffy-package`

Additionally, you can download or clone the repository and setup your own autoloading.

## Naming

Packages following the following internal naming schema when resolving.

1) The package name is lower-cased.
2) CamelCase is replaced with dash separation.
3) Namespace backslashes are replaced with periods.

e.g.,

Spiffy\Mvc => mvc
Spiffy\AsseticPackage => spiffy.assetic-package
