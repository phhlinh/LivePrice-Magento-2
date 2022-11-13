<?php
/**
 * PL Development.
 *
 * @category    PL
 * @author      Linh Pham <plinh5@gmail.com>
 * @copyright   Copyright (c) 2016 PL Development. (http://www.polacin.com)
 */

namespace PL\Liveprice\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
class ProductListFinalPrice implements ObserverInterface{

    protected $_storeManager;

    protected $_helper;

    protected $_price;

    public function __construct(
        StoreManagerInterface $storeManager,
        \PL\Liveprice\Helper\Data $helper
    )
    {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;

    }

    public function execute(\Magento\Framework\Event\Observer $observer){
        if($this->_helper->isEnabled()){
            $collection = $observer->getCollection();
            if (count($collection)) {
                foreach ($collection as $product) {
                    $livePrice = $this->_helper->calPrice($product->getEntityId());
                    if($livePrice && $livePrice !=0){
                        $product->setFinalPrice($livePrice);
                    }
                }
            }
        }
    }

}