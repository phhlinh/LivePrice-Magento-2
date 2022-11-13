<?php
/**
 * PL Development.
 *
 * @category    PL
 * @author      Linh Pham <plinh5@gmail.com>
 * @copyright   Copyright (c) 2016 PL Development. (http://www.polacin.com)
 */
namespace PL\Liveprice\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

class Data extends AbstractHelper
{

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    protected $_datetime;
    protected $_timezone;

    protected $priceCurrency;

    protected $_coreRegistry;

    protected $_modelStoreManagerInterface;

    protected $_pricingHelper;

    public $livePriceValue;

    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry,
        DateTime $datetime,
        TimezoneInterface $timezone,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $modelStoreManagerInterface,
        PricingHelper $pricingHelper
    ) {
        parent::__construct($context);
        $this->_productFactory = $productFactory;
        $this->_datetime = $datetime;
        $this->_timezone = $timezone;
        $this->priceCurrency = $priceCurrency;
        $this->_coreRegistry = $registry;
        $this->_modelStoreManagerInterface = $modelStoreManagerInterface;
        $this->_pricingHelper = $pricingHelper;

    }

    public function isEnabled(){
        if($this->scopeConfig->getValue('liveprice/general/enable',ScopeInterface::SCOPE_STORE)){
            return true;
        }
        return false;
    }

    public function getProductLevel(){
        if($this->scopeConfig->getValue('liveprice/general/product_level',ScopeInterface::SCOPE_STORE)){
            return true;
        }
        return false;
    }

    public function getPriceStep(){
        if($this->scopeConfig->getValue('liveprice/general/price_step',ScopeInterface::SCOPE_STORE)){
            return $this->scopeConfig->getValue('liveprice/general/price_step',ScopeInterface::SCOPE_STORE);
        }
        return 0;
    }
    public function getTimeStep(){
        if($this->scopeConfig->getValue('liveprice/general/time_step',ScopeInterface::SCOPE_STORE)){
            return $this->scopeConfig->getValue('liveprice/general/time_step',ScopeInterface::SCOPE_STORE);
        }
        return 1;
    }

    public function getDisplayCountDownTimer(){
        if($this->scopeConfig->getValue('liveprice/general/display_countdown_timer',ScopeInterface::SCOPE_STORE)){
            return $this->scopeConfig->getValue('liveprice/general/display_countdown_timer',ScopeInterface::SCOPE_STORE);
        }

    }

    public function getTooltipTimer(){
        if($this->scopeConfig->getValue('liveprice/general/tooltip_timer',ScopeInterface::SCOPE_STORE)){
            return $this->scopeConfig->getValue('liveprice/general/tooltip_timer',ScopeInterface::SCOPE_STORE);
        }

    }

    public function getUpdateMainPrice(){
        if($this->scopeConfig->getValue('liveprice/general/update_main_price',ScopeInterface::SCOPE_STORE)){
            return $this->scopeConfig->getValue('liveprice/general/update_main_price',ScopeInterface::SCOPE_STORE);
        }

    }

    public function getBaseCurrencyCode(){
        return $this->_modelStoreManagerInterface->getStore()->getBaseCurrencyCode();       
        
    }

    public function getLocaleCode(){
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $resolver = $om->get('Magento\Framework\Locale\Resolver');
        return $resolver->getLocale();
    }
    
    public function getTimerColor(){
        if($this->scopeConfig->getValue('liveprice/color/timer',ScopeInterface::SCOPE_STORE)){
            return '#'.$this->scopeConfig->getValue('liveprice/color/timer',ScopeInterface::SCOPE_STORE);
        }
        return '#323A45';
    }

    public function getPriceColor(){
        if($this->scopeConfig->getValue('liveprice/color/end_price',ScopeInterface::SCOPE_STORE)){
            return '#'.$this->scopeConfig->getValue('liveprice/color/end_price',ScopeInterface::SCOPE_STORE);
        }
        return '#26B57B';
    }


    public function calPrice($productId){
        $productCollection = $this->_productFactory->create()
            ->getCollection()
            ->addAttributeToSelect("*")
            ->addFieldToFilter('entity_id',$productId);
        //print"<pre>"; print_r($productCollection->getData()); exit;
        if($productCollection->getSize()){
            $product = $productCollection->getFirstItem(); //print"<pre>"; print_r($product); exit;

            if($product->getProgressDateStart()){
                $datetimeArray = explode(' ',$product->getProgressDateStart());
                $timeStart = $datetimeArray[0].' '.$product->getProgressTimeStart();
                $currentTime = $this->_timezone->date()->format('m/d/y h:i:s A');
                $now = strtotime($currentTime);
                if($now -  strtotime($timeStart)){
                    $startDate = new \DateTime($timeStart);
                    $sinceStart = $startDate->diff(new \DateTime($currentTime));
                    $progressmMinutes = $sinceStart->days * 24 * 60;
                    $progressmMinutes += $sinceStart->h * 60;
                    $progressmMinutes += $sinceStart->i;
                    if($this->getProductLevel()){
                        if($product->getPriceStep() > 0 && $product->getTimeStep() >0 ){
                            $priceRate = $product->getPriceStep()/$product->getTimeStep();
                        }
                    }else{
                        if( $this->getPriceStep() >0 && $this->getTimeStep()>0 ){
                            $priceRate = $this->getPriceStep()/$this->getTimeStep();
                        }
                    }

                    $this->livePriceValue = ($progressmMinutes*$priceRate) +  $product->getProgressPriceStart();
                    if($this->livePriceValue < $product->getProgressPriceEnd()){
                        return $this->livePriceValue;
                    }
                }
            }
        }
    }



    public function getPriceHtml($price,$price_type ='finalPrice',$price_id='',$display_label=''){
        $html='<span class="price-container">';
        if($display_label!=""){
            $html.='<span class="price-label">'.$display_label.'</span>';        }
        $html.='<span id="'.$price_id.'" data-price-amount="'.$price.'" data-price-type="'.$price_type.'" class="price-wrapper ">';
        $html.='<span class="price">'.$this->_pricingHelper->currency($price,true,false).'</span>';
        $html.='</span>';
        $html.'</span>';
        return $html;
    }

    public function getPriceSnippet($price,$price_type ='finalPrice',$price_id='',$display_label=''){
        $html='<span class="price-container " itemprop="offers" itemscope="" itemtype="http://schema.org/Offer">';
        $html.='<span id="'.$price_id.'" data-price-amount="'.$price.'" data-price-type="'.$price_type.'" class="price-wrapper " itemprop="price">';
        $html.='<span class="price">'.$this->_pricingHelper->currency($price,true,false).'</span>';
        $html.'</span>';
        $html.='<meta itemprop="priceCurrency" content="'.$this->getBaseCurrencyCode().'">';
        $html.='</span>';
        return $html;
    }




}

