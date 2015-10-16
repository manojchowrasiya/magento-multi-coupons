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
class EbayEnterprise_MultipleCoupons_Model_Observer
{
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
    }

    /**
     * @param Varien_Event_Observer $observer
     *  this observer event contains:
     *      - 'order_create_model'  => Mage_Adminhtml_Model_Sales_Order_Create
     *      - 'request_model'       => Mage_Core_Controller_Request_Http
     *      - 'session'             => Mage_Adminhtml_Model_Session_Quote
     */
    public function handleAdminCouponAdd(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $event->getRequestModel();

        $order = $request->getPost('order');

        if (isset($order['coupon']['code'])) {

            /** @var Mage_Adminhtml_Model_Session_Quote $session */
            $session = $event->getSession();

            $quote = $session->getQuote();

            if (isset($order['coupon']['remove'])) {

                $this->couponService->removeCoupon($quote, $order['coupon']['code']);

            } else {

                $this->couponService->addCoupon($quote, $order['coupon']['code']);

            }

            // Remove coupon from the request.
            // - see Mage_Adminhtml_Model_Sales_Order_Create::importPostData
            unset($order['coupon']);

            $request->setPost('order', $order);

        }

    }

    /**
     * Warn if maximum coupons are used
     * @param Varien_Event_Observer
     */
    public function warnMaximumCoupons(Varien_Event_Observer $observer)
    {
        $checkoutSession = $this->getCheckoutSession();

        $quote = $checkoutSession->getQuote();

        $couponCodes = $this->couponService->getCouponCodesFromQuote($quote);

        $cartCouponsCount = count($couponCodes);

        $maxCouponsCount = Mage::getStoreConfig('promo/multiple_coupons/max_num_coupons');

        if ($cartCouponsCount >= $maxCouponsCount) {

            $checkoutSession->addNotice(
                Mage::helper('core')->__('You have entered the maximum number of allowed coupons. If you wish to enter another, please delete one first.')
            );

        }

    }

    /**
     * Get checkout session model instance
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Update coupon usage
     * @param Varien_Event_Observer
     * @return $this
     * @throws Exception
     */
    public function handleSalesOrderPlaceAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $this->updateCouponUsage($order);

        return $this;
    }


    /**
     * Update coupon usage
     * @param Mage_Sales_Model_Order
     */
    protected function updateCouponUsage(Mage_Sales_Model_Order $order)
    {
        $couponCodes = $this->couponService->getCouponCodesFromOrder($order);

        // default handler works well with 1 code, we don't need to change anything
        if (count($couponCodes) > 1) {

            $customerId = $order->getCustomerId();

            $this->couponService->updateCouponUsage($couponCodes, $customerId);

        }
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
