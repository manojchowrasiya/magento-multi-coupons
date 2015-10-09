Multiple coupons support for Magento

To add/update a coupon just set the coupon(s) to the quote and save. 

``php $quote->setCouponCode('foo,bar')->save();``

This triggers a validation which must split the coupon string and validate each coupon, removing invalid codes from the string as it goes.
