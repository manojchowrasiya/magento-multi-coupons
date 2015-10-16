<?php
/**
 * Copyright (c) 2013-2015 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2015 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class EbayEnterprise_MultipleCoupons_Block_Checkout_Cart_Coupon extends Mage_Checkout_Block_Cart_Coupon
{
    /**
     * Template must be overwritten in _toHtml()
     */
    const TEMPLATE_OVERRIDE = 'ebayenterprise_multiplecoupons/checkout/cart/coupon.phtml';

    /**
     * @var array
     */
    protected $appliedCodes;

    /**
     * @var EbayEnterprise_MultipleCoupons_Model_Service
     */
    protected $couponService;

    /**
     * Initialize the coupon controller.
     * @param array
     */
    public function __construct(array $invokeArgs = array())
    {
        list($this->couponService) = $this->checkTypes(
            $this->nullCoalesce($invokeArgs, 'coupon_service', Mage::getModel('ebayenterprise_multiplecoupons/service'))
        );

        parent::__construct();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $this->setTemplate(self::TEMPLATE_OVERRIDE);

        return parent::_toHtml();
    }

    /**
     * Get applied coupons
     * @return array
     */
    public function getAppliedCoupons()
    {
        if (!isset($this->appliedCodes)) {

            $quote = $this->getQuote();

            $this->appliedCodes = $this->couponService->getCouponCodesFromQuote($quote);

        }

        return $this->appliedCodes;
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param  array
     * @param  string|int
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Validate constructor parameters.
     * @param EbayEnterprise_MultipleCoupons_Model_Service
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_MultipleCoupons_Model_Service $couponService
    ) {
        return func_get_args();
    }
}
