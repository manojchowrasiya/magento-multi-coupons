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
class EbayEnterprise_MultipleCoupons_Model_Validator extends Mage_SalesRule_Model_Validator
{
    const MAX_NUM_COUPONS = 'promo/multiple_coupons/max_num_coupons';

    /**
     * Event name used for loading a valid rule collection
     * @var string
     */
    protected $eventName = 'valid_rule_collection_before_load';

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
     * Load collection of rules for specific website, customer group, and coupon code
     * @param   int
     * @param   int
     * @param   string
     * @return  Mage_SalesRule_Model_Validator
     */
    public function init($websiteId, $customerGroupId, $couponCode)
    {
        $this->addData([
            'website_id'        => $websiteId,
            'customer_group_id' => $customerGroupId,
            'coupon_code'       => $couponCode,
        ]);

        $key = $websiteId . '_' . $customerGroupId . '_' . $couponCode;

        if (!isset($this->_rules[$key])) {

            $ruleCollection = $this->getRuleCollection();

            $this->addCouponFilter($ruleCollection, $couponCode);

            $ruleCollection->addFieldToFilter('is_active', true);

            $ruleCollection->addWebsiteGroupDateFilter($websiteId, $customerGroupId);

            Mage::dispatchEvent($this->eventName, ['rule_collection' => $ruleCollection]);

            $this->_rules[$key] = $ruleCollection->load();

        }

        return $this;
    }

    /**
     * Quote item discount calculation process
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @return  Mage_SalesRule_Model_Validator
     */
    public function process(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $address = $this->_getAddress($item);

        /**
         * check coupon count limit
         */
        $this->validateCouponCount($address);

        /**
         * check coupon usage
         */
        $this->validateCouponUsage($address);

        parent::process($item);
    }

    /**
     * Check if rule can be applied for specific address/quote/customer
     *
     * @param   Mage_SalesRule_Model_Rule
     * @param   Mage_Sales_Model_Quote_Address
     * @return  bool
     */
    protected function _canProcessRule(Mage_SalesRule_Model_Rule $rule, Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * check if validation already occurred
         */
        if ($rule->hasIsValidForAddress($address) && $address->isObjectNew() === false) {
            return $rule->getIsValidForAddress($address);
        }

        /**
         * check per rule usage limit
         */
        if ($rule->getId() && $rule->getUsesPerCustomer() &&
            $this->isValidRuleUsageForAddress($rule, $address) === false
        ) {
            $rule->setIsValidForAddress($address, false);
            return false;
        }

        $rule->afterLoad();

        /**
         * quote does not meet rule's conditions
         */
        if ($rule->validate($address) === false) {
            $rule->setIsValidForAddress($address, false);
            return false;
        }

        /**
         * passed all validations, remember to be valid
         */
        $rule->setIsValidForAddress($address, true);
        return true;
    }

    /**
     * Validate the coupon usage for given address
     * @param Mage_Sales_Model_Quote_Address
     */
    protected function validateCouponCount(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();

        $couponCodes = $this->couponService->getCouponCodesFromQuote($quote);

        $maxCouponsCount = (int) Mage::getStoreConfig(self::MAX_NUM_COUPONS);

        $couponCodes = array_slice($couponCodes, 0, $maxCouponsCount ?: null);

        $couponCode = implode(',', $couponCodes);

        $quote->setCouponCode($couponCode);
    }

    /**
     * Validate the coupon usage for given address
     * @param Mage_Sales_Model_Quote_Address
     */
    protected function validateCouponUsage(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();

        $customerId = $quote->getCustomerId();

        $couponCodes = $this->couponService->getCouponCodesFromQuote($quote);

        $newCodes = $couponCodes;

        foreach ($couponCodes as $index => $couponCode) {

            $isValid = $this->isValidCouponUsage($couponCode, $customerId);

            if (!$isValid) {

                unset($newCodes[$index]);

            }

        }

        if ($newCodes != $couponCodes) {

            $couponCode = implode(',', $newCodes);

            $quote->setCouponCode($couponCode);

        }
    }

    /**
     * Validate the usage of a given coupon
     * @param string
     * @param null|int
     * @return bool
     */
    protected function isValidCouponUsage($couponCode, $customerId = null)
    {
        $coupon = $this->getCouponModel();

        $coupon->load($couponCode, 'code');

        // check if coupon exists
        if (!$coupon->getId()) {
            return false;
        }

        $couponRuleId = $coupon->getRuleId();

        $ruleCollection = $this->_getRules();

        $validRuleIds = $ruleCollection->getAllIds();

        // check if coupon is from a valid rule
        if (!in_array($couponRuleId, $validRuleIds)) {
            return false;
        }

        // check entire usage limit
        if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
            return false;
        }

        // check per customer usage limit
        if ($customerId && $coupon->getUsagePerCustomer()) {

            $couponUsage = new Varien_Object();

            $this->getCouponUsageResource()
                ->loadByCustomerCoupon($couponUsage, $customerId, $coupon->getId());

            if ($couponUsage->getCouponId() && $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()) {
                return false;
            }

        }

        return true;
    }

    /**
     * Validate the rule usage for given address
     * @param Mage_SalesRule_Model_Rule
     * @param Mage_Sales_Model_Quote_Address
     * @return bool
     */
    protected function isValidRuleUsageForAddress(Mage_SalesRule_Model_Rule $rule, Mage_Sales_Model_Quote_Address $address)
    {
        $ruleId = $rule->getId();

        $customerId = $address->getQuote()->getCustomerId();

        $ruleCustomer = $this->getRuleCustomerModel();

        $ruleCustomer->loadByCustomerRule($customerId, $ruleId);

        if ($ruleCustomer->getId() && $ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
            return false;
        }

        return true;
    }

    /**
     * Set validation filter on rule collection
     * @param Mage_SalesRule_Model_Resource_Rule_Collection
     * @param string
     */
    protected function addCouponFilter(Mage_SalesRule_Model_Resource_Rule_Collection $ruleCollection, $couponCode)
    {
        // multiple coupon compatibility
        if ($couponCode && !is_array($couponCode)) {
            $couponCode = explode(',', $couponCode);
        }

        $select = $ruleCollection->getSelect();

        $select->reset();

        $ruleTable = $ruleCollection->getTable('salesrule/rule');

        $couponTable = $ruleCollection->getTable('salesrule/coupon');

        $select->from(['main_table' => $ruleTable]);

        if ($couponCode) {

            $select->joinLeft(['c' => $couponTable], 'main_table.rule_id = c.rule_id ', ['code']);

            $select->where(
                $select->getAdapter()->quoteInto(' main_table.coupon_type = ?', 1) .
                $select->getAdapter()->quoteInto(' OR c.code IN(?)', $couponCode)
            );

            $select->group('main_table.rule_id');

        } else {

            $select->where('main_table.coupon_type = ?', 1);

        }

        $select->order('sort_order');
    }

    /**
     * @return Mage_Salesrule_Model_Resource_Coupon_Usage
     */
    protected function getCouponUsageResource()
    {
        return Mage::getResourceModel('salesrule/coupon_usage');
    }

    /**
     * @return Mage_Salesrule_Model_Rule_Customer
     */
    protected function getRuleCustomerModel()
    {
        return Mage::getModel('salesrule/rule_customer');
    }

    /**
     * @return Mage_Salesrule_Model_Coupon
     */
    protected function getCouponModel()
    {
        return Mage::getModel('salesrule/coupon');
    }

    /**
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    protected function getRuleCollection()
    {
        return Mage::getResourceModel('salesrule/rule_collection');
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
