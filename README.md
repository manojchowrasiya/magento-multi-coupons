Multiple coupons support for Magento

Here's how it works:

When a coupon code is added via controller/action, we use the CouponService to 
add the coupon by joining it to a comma seperated string. Then the controller
simply sets the code to the quote and saves it. This triggers a validation which
must split the coupon string and validate each coupon, removing invalid codes 
from the string as it goes. The final coupon code string is compared by the 
controller to determine if it was added or not.