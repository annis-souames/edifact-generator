<?php

namespace EDI\Generator\Invoic;

use EDI\Generator\Base;
use EDI\Generator\EdiFactNumber;
use EDI\Generator\Message;
use EDI\Generator\Traits\Item as ItemTrait;

/**
 * Class Item
 * @package EDI\Generator\Invoic
 */
class Item extends Base
{
    use ItemTrait;

    const DISCOUNT_TYPE_PERCENT = 'percent';
    const DISCOUNT_TYPE_ABSOLUTE = 'absolute';

    /** @var array */
    protected $invoiceDescription;
    /** @var array */
    protected $grossPrice;
    /** @var array */
    protected $netPrice;
    /** @var int */
    protected $discountIndex = 0;

    protected $itemTaxInfo;


    /**
     * @return array
     */
    public function getInvoiceDescription()
    {
        return $this->invoiceDescription;
    }

    /**
     * @param string $invoiceDescription
     * @return Item
     */
    public function setInvoiceDescription($invoiceDescription)
    {
        $this->invoiceDescription = Message::addFTXSegment($invoiceDescription, 'INV');
        $this->addKeyToCompose('invoiceDescription');

        return $this;
    }


    /**
     * @param $qualifier
     * @param $value
     * @param int $priceBase
     * @param string $priceBaseUnit
     * @return array
     */
    public static function addPRISegment($qualifier, $value, $priceBase = 1, $priceBaseUnit = 'PCE')
    {
        return [
            'PRI',
            [
                $qualifier,
                EdiFactNumber::convert($value),
                '',
                '',
                (string)$priceBase,
                $priceBaseUnit
            ]
        ];
    }

    /**
     * @return array
     */
    public function getGrossPrice()
    {
        return $this->grossPrice;
    }

    /**
     * @param string $grossPrice
     * @return Item
     */
    public function setGrossPrice($grossPrice)
    {
        $this->grossPrice = self::addPRISegment('AAB', $grossPrice);
        $this->addKeyToCompose('grossPrice');
        return $this;
    }

    /**
     * @return array
     */
    public function getNetPrice()
    {
        return $this->netPrice;
    }

    /**
     * @param string $netPrice
     * @return Item
     */
    public function setNetPrice($netPrice)
    {
        $this->netPrice = self::addPRISegment('AAA', $netPrice);
        $this->addKeyToCompose('netPrice');
        return $this;
    }

    public function addTax($base,$percent=20,$name='VAT')
    {
        $this->itemTaxInfo = [
            'TAX',
            '7',
            $name,
            '',
            $base,
            [
                '',
                '',
                '',
                EdiFactNumber::convert($percent, 0)
            ],
            'S'
        ];
        $this->addKeyToCompose('itemTaxInfo');
        return $this;
    }

    /**
     * @param $value
     * @param $percent
     * @param $amount
     * @param string $name
     * @return $this
     */
    public function addDiscount($value, $percent, $name = '',$qualifier='TD')
    {
        $index = 'discount' . $this->discountIndex++;
        $this->{$index} = [
            'ALC',
            'A',
            '',
            '2',
            '1',
            [
                $qualifier,
                '',
                '',
                $name,
                ''
            ]
        ];
        $this->addKeyToCompose($index);

        $index = 'discount' . $this->discountIndex++;
        $this->{$index} = [
            'PCD',
            [
                '',
                '1',
                EdiFactNumber::convert(abs($value))
            ]
        ];
        $this->addKeyToCompose($index);

        // Logic for MOA
        $index = 'discount' . $this->discountIndex++;
        $this->{$index} = self::addMOASegment('204', abs($value));
        $this->addKeyToCompose($index);

        return $this;
    }
}
