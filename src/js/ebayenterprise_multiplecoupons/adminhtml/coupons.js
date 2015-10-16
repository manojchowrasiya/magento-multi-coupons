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
