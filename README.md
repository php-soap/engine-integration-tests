# SOAP Engine integration tests

This package provides a set of `PHPUnit` test cases for testing your own [SOAP engine (components)](https://github.com/php-soap/engine).
To make sure that all your custom engine components have the same end-result, this package provides test cases that can be used to test your custom components.

## Installation

```
composer require --dev php-soap/engine-integration-tests
```

## Usage

This will make following test cases available:

* [AbstractDecoderTest](https://github.com/php-soap/engine-integration-tests/tree/main/src/AbstractDecoderTest.php): Can be used to test a custom `Decoder`.
* [AbstractEncoderTest](https://github.com/php-soap/engine-integration-tests/tree/main/src/AbstractEncoderTest.php): Can be used to test a custom `Encoder`.
* [AbstractEngineTest](https://github.com/php-soap/engine-integration-tests/tree/main/src/AbstractEngineTest.php): : Can be used to test a custom `Engine` or `Transport`.
* [AbstractMetadataProviderTest](https://github.com/php-soap/engine-integration-tests/tree/main/src/AbstractMetadataProviderTest.php): : Can be used to test a custom `Metadata` or `MetadataProvider`.
