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
class EbayEnterprise_MultipleCoupons_Model_Service
{

    const SEPARATOR = ',';

    /**
     * Add Coupon to Quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param $couponCode
     */
    public function addCoupon(Mage_Sales_Model_Quote $quote, $couponCode)
    {
        $couponCodes = $this->getCouponCodesFromQuote($quote);

        if ($this->canAddCouponCode($couponCode, $couponCodes)) {

            $couponCodes[] = $couponCode;

            $couponCode = implode(self::SEPARATOR, $couponCodes);

            $this->setCouponCode($quote, $couponCode);

        }
    }

    /**
     * Remove Coupon from Quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param $couponCode
     */
    public function removeCoupon(Mage_Sales_Model_Quote $quote, $couponCode)
    {
        $couponCodes = $this->getCouponCodesFromQuote($quote);

        if ($this->canRemoveCouponCode($couponCode, $couponCodes)) {

            $index = array_search($couponCode, $couponCodes);

            unset($couponCodes[$index]);

            $couponCode = implode(self::SEPARATOR, $couponCodes);

            $this->setCouponCode($quote, $couponCode);

        }

    }

    /**
     * Update the coupon's usage
     * @param array
     * @param int|null
     * @throws Exception
     */
    public function updateCouponUsage(array $couponCodes, $customerId = null)
    {
        $couponModel = $this->getCouponModel();

        foreach ($couponCodes as $couponCode){

            $couponModel->load($couponCode, 'code');

            if ($couponModel->getId()) {

                $couponModel->setTimesUsed($couponModel->getTimesUsed() + 1);

                $couponModel->save();

                if ($customerId) {

                    $couponUsage = $this->getCouponUsageResource();

                    $couponUsage->updateCustomerCouponTimesUsed($customerId, $couponModel->getId());

                }
            }
        }
    }

    /**
     * Get coupon codes from quote
     * @param Mage_Sales_Model_Quote
     * @return array
     */
    public function getCouponCodesFromQuote(Mage_Sales_Model_Quote $quote)
    {
        $couponCode = $quote->getCouponCode();

        return $this->getCouponCodes($couponCode);
    }

    /**
     * Get coupon codes from order
     * @param Mage_Sales_Model_Order
     * @return array
     */
    public function getCouponCodesFromOrder(Mage_Sales_Model_Order $order)
    {
        $couponCode = $order->getCouponCode();

        return $this->getCouponCodes($couponCode);
    }

    /**
     * Get coupon codes
     * @param string
     * @return array
     */
    public function getCouponCodes($couponCode)
    {
        return $couponCode ? explode(self::SEPARATOR, $couponCode) : [];
    }

    /**
     * Set the coupon code on the quote
     *
     * @param Mage_Sales_Model_Quote
     * @param $couponCode
     */
    public function setCouponCode(Mage_Sales_Model_Quote $quote, $couponCode)
    {
        $quote->setCouponCode($couponCode);

        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $quote->save();
    }

    /**
     * @return Mage_Salesrule_Model_Coupon
     */
    protected function getCouponModel()
    {
        return Mage::getModel('salesrule/coupon');
    }

    /**
     * @return Mage_Salesrule_Model_Resource_Coupon_Usage
     */
    protected function getCouponUsageResource()
    {
        return Mage::getResourceModel('salesrule/coupon_usage');
    }

    /**
     * @param $couponCode
     * @param array $codes
     * @return bool
     */
    protected function canRemoveCouponCode($couponCode, array $codes)
    {
       return in_array($couponCode, $codes);
    }

    /**
     * @param $couponCode
     * @param array $codes
     * @return bool
     */
    protected function canAddCouponCode($couponCode, array $codes)
    {
       return in_array($couponCode, $codes) === false;
    }

}
