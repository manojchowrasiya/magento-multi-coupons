/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

AdminOrder.prototype.removeCoupon = function(code)
{
    this.loadArea(
        [
            'items',
            'shipping_method',
            'totals',
            'billing_method'
        ],
        true,
        {
            'order[coupon][code]'   : code,
            reset_shipping          : true,
            'order[coupon][remove]' : true
        }
    );
};
