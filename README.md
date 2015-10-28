[![ebay logo](docs/static/logo-vert.png)](http://www.ebayenterprise.com/)

# Magento Multiple Coupons Extension

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/eBayEnterprise/magento-multi-coupons/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/eBayEnterprise/magento-multi-coupons/?branch=master)

To add/update a coupon just set the coupon(s) to the quote and save. 

```
$quote->setCouponCode('foo,bar')->save();
```

This triggers a validation which must split the coupon string and validate each coupon, removing invalid codes from the string as it goes.

## License

Licensed under the terms of the Open Software License v. 3.0 (OSL-3.0). See [LICENSE.md](LICENSE.md) or http://opensource.org/licenses/OSL-3.0 for the full text of the license.

- - -
Copyright © 2014 eBay Enterprise, Inc.
