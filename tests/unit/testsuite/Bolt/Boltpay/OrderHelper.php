<?php
require_once('TestHelper.php');

class Bolt_Boltpay_OrderHelper
{
    /**
     * Get dummy transaction object
     *
     * @param       $grandTotal
     * @param array $items
     *
     * @return mixed
     */
    public function getDummyTransactionObject($grandTotal, $items = array())
    {

        $dummyData = array(
            'order' => array(
                'cart' =>
                    array(
                        'order_reference' => '00000',
                        'display_id' => '0000000000|00000',
                        'currency' =>
                            array(
                                'currency' => 'USD',
                                'currency_symbol' => '$',
                            ),
                        'discounts' => array(),
                        'discount_amount' => array(
                            'amount' => 0
                        ),
                        'tax_amount' => array(
                            'amount' => 0
                        ),
                        'shipping_amount' => array(
                            'amount' => 0
                        ),
                        'subtotal_amount' => array(
                            'amount' => 0
                        ),
                        'total_amount' => array(
                            'amount' => round($grandTotal * 100)
                        )
                    )
            ),
            'status' => 'payment_review'
        );

        foreach ($items as $item) {
            $dummyData['order']['cart']['items'] =
                array(
                    0 =>
                        array(
                            'reference' => '50256',
                            'name' => 'Linen Blazer',
                            'description' => 'Single vented, notched lapels. Flap pockets. Tonal stitching. Fully lined. Linen. Dry clean.',
                            'total_amount' =>
                                array(
                                    'amount' => round($item['amount'] * $item['qty'] * 100),
                                    'currency' => 'USD',
                                    'currency_symbol' => '$',
                                ),
                            'unit_price' =>
                                array(
                                    'amount' => round($item['amount'] * 100),
                                    'currency' => 'USD',
                                    'currency_symbol' => '$',
                                ),
                            'quantity' => $item['qty'],
                            'sku' => $item['sku'],
                            'image_url' => '',
                            'type' => 'physical',
                        ),
                );
        }

        return json_decode(json_encode($dummyData));
    }

    /**
     * Creates dummy order
     *
     * @param int $productId to be used for order creation
     * @param int $qty of the product provided
     * @param string $paymentMethod code used for order creation
     *
     * @return Mage_Sales_Model_Order dummy order
     *
     * @throws Mage_Core_Exception if provided payment method is not available
     */
    public static function createDummyOrder($productId, $qty = 2, $paymentMethod = 'boltpay')
    {
        $testHelper = new Bolt_Boltpay_TestHelper();
        $testHelper->addTestBillingAddress();
        $addressData = array(
            'firstname' => 'Luke',
            'lastname' => 'Skywalker',
            'street' => 'Sample Street 10',
            'city' => 'Los Angeles',
            'postcode' => '90014',
            'telephone' => '+1 867 345 123 5681',
            'country_id' => 'US',
            'region_id' => 12
        );
        $testHelper->addTestFlatRateShippingAddress($addressData, $paymentMethod);
        $cart = $testHelper->addProduct($productId, $qty);
        $quote = $cart->getQuote();
        $quote->reserveOrderId();

        // Set payment method for the quote
        $quote->getPayment()->importData(array('method' => $paymentMethod));
        $service = Mage::getModel('sales/service_quote', $quote);

        if ($paymentMethod == Bolt_Boltpay_Model_Payment::METHOD_CODE) {
            $previousHookValue = Bolt_Boltpay_Helper_Data::$fromHooks;
            //temporarily set flag to true in order to pass order validation
            Bolt_Boltpay_Helper_Data::$fromHooks = true;
        }

        $service->submitAll();
        if ($paymentMethod == Bolt_Boltpay_Model_Payment::METHOD_CODE) {
            Bolt_Boltpay_Helper_Data::$fromHooks = $previousHookValue;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $service->getOrder();

        return $order;
    }

    /**
     * Deletes dummy order by increment id
     *
     * @param $incrementId
     * @throws Zend_Db_Adapter_Exception
     *
     */
    public static function deleteDummyOrderByIncrementId($incrementId)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $writeConnection */
        $writeConnection = $resource->getConnection('core_write');
        $orderTable = $resource->getTableName('sales/order');
        $orderGridTable = $resource->getTableName('sales/order_grid');

        $query = "DELETE ot, ogt FROM $orderTable ot LEFT JOIN $orderGridTable ogt ON ot.increment_id = ogt.increment_id WHERE ot.increment_id = :increment_id";
        $bind = array(
            'increment_id' => (int)$incrementId
        );

        $writeConnection->query($query, $bind);
    }

    /**
     * Deletes dummy order
     *
     * @param Mage_Sales_Model_Order $order
     */
    public static function deleteDummyOrder(Mage_Sales_Model_Order $order)
    {
        Mage::register('isSecureArea', true);
        $order->delete();
        Mage::unregister('isSecureArea');

    }
}
