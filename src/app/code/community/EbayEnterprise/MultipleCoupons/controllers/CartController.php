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

require_once 'Mage/Checkout/controllers/CartController.php';
class EbayEnterprise_MultipleCoupons_CartController extends Mage_Checkout_CartController
{
    /**
     * @var EbayEnterprise_MultipleCoupons_Model_Service
     */
    protected $couponService;

    /**
     * Initialize the coupon controller.
     * @param Zend_Controller_Request_Abstract
     * @param Zend_Controller_Response_Abstract
     * @param array
     */
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        list($this->couponService) = $this->checkTypes(
            $this->nullCoalesce($invokeArgs, 'coupon_service', Mage::getModel('ebayenterprise_multiplecoupons/service'))
        );

        parent::__construct($request, $response, $invokeArgs);
    }

    /**
     * Add coupon to cart
     */
    public function postAction()
    {
        $quote = $this->_getQuote();

        if ($quote->getItemsCount()) {

            try {

                $couponCode = (string) $this->getRequest()->getParam('coupon_code');

                $oldCouponCode = $quote->getCouponCode();

                $this->couponService->addCoupon($quote, $couponCode);

                $newCouponCode = $quote->getCouponCode();

                $foundInOldCoupon = stristr($oldCouponCode, $couponCode) !== false;

                $foundInNewCoupon = stristr($newCouponCode, $couponCode) !== false;

                $hasCouponChanged = ($foundInOldCoupon === false && $foundInNewCoupon === true);

                if ($hasCouponChanged) {

                    $this->_getSession()->addSuccess(
                        $this->__('Coupon code "%s" was applied.', Mage::helper('core')->escapeHtml($couponCode))
                    );

                } else {
                    $this->_getSession()->addError(
                        $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->escapeHtml($couponCode))
                    );

                }

            } catch (Mage_Core_Exception $e) {

                $this->_getSession()->addError($e->getMessage());

            } catch (Exception $e) {

                $this->_getSession()->addError($this->__('Cannot apply the coupon code.'));

                Mage::logException($e);

            }

        }

        $this->_goBack();
    }

    /**
     * Remote coupon from cart
     *
     * @throws Mage_Exception
     */
    public function cancelAction()
    {
        $isAjax = $this->getRequest()->getParam('isAjax');

        try {

            $quote = $this->_getQuote();

            $couponCode = (string) $this->getRequest()->getParam('coupon_code_cancel');

            $oldCouponCode = $quote->getCouponCode();

            $this->couponService->removeCoupon($quote, $couponCode);

            $newCouponCode = $quote->getCouponCode();

            $foundInOldCoupon = stristr($oldCouponCode, $couponCode) !== false;

            $foundInNewCoupon = stristr($newCouponCode, $couponCode) !== false;

            $hasCouponChanged = ($foundInOldCoupon === true && $foundInNewCoupon === false);

            if ($hasCouponChanged) {

                $this->_getSession()->addSuccess(
                    $this->__('Coupon code %s was canceled.', $couponCode)
                );

            } else {

                $this->_getSession()->addError(
                    $this->__('Cannot cancel the coupon code.')
                );

            }

        } catch (Mage_Core_Exception $e) {

            $this->_getSession()->addError($e->getMessage());

        } catch (Exception $e) {

            $this->_getSession()->addError($this->__('Cannot cancel the coupon code.'));

            Mage::logException($e);

        }

        if (!$isAjax) {

            $this->_goBack();

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
