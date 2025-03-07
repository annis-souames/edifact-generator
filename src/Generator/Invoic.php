<?php

namespace EDI\Generator;

use EDI\Generator\Invoic\Item;
use EDI\Generator\Traits\ContactPerson;
use EDI\Generator\Traits\NameAndAddress;
use EDI\Generator\traits\VatAndCurrency;

/**
 * Class Invoic
 * @url http://www.unece.org/trade/untdid/d96b/trmd/invoic_s.htm
 * @package EDI\Generator
 */
class Invoic extends Message
{
    use ContactPerson, NameAndAddress, VatAndCurrency;

    const TYPE_INVOICE = '380';
    const TYPE_CREDIT_NOTE = '381';
    const TYPE_SERVICE_CREDIT = '31e';
    const TYPE_SERVICE_INVOICE = '32e';
    const TYPE_BONUS = '33i';

    /** @var array */
    protected $invoiceNumber;
    /** @var array */
    protected $invoiceDate;
    /** @var array */
    protected $deliveryDate;
    /** @var array */
    protected $items;
    /** @var array */
    protected $reductionOfFeesText;
    /** @var array */
    protected $invoiceDescription;

    /** @var array */
    protected $composeKeys = [
        'invoiceNumber',
        'invoiceReference',
        'invoiceDate',
        'deliveryDate',
        'reductionOfFeesText',
        'excludingVatText',
        'invoiceDescription',
        'manufacturerAddress',
        'wholesalerAddress',
        'deliveryAddress',
        'supplierAddress',
        'sellerVatNumber',
        'sellerGovNumber',
        'sellerCompanyNumber',
        'buyerAddress',
        'buyerVatNumber',
        'buyerGovNumber',
        'buyerCompanyNumber',
        'invoiceAddress',
        'invoiceeVatNumber',
        'invoiceeGovNumber',
        'invoiceeCompanyNumber',
        'deliveryPartyAddress',
        'deliveryVatNumber',
        'deliveryGovNumber',
        'deliveryCompanyNumber',
        'contactPerson',
        'mailAddress',
        'phoneNumber',
        'faxNumber',
        'vatNumber',
        'currency',
    ];

    /** @var array */
    protected $positionSeparator;
    /** @var array */
    protected $totalPositionsAmount;
    /** @var array */
    protected $basisAmount;
    /** @var array */
    protected $taxableAmount;
    /** @var array */
    protected $payableAmount;
    /** @var array */
    protected $tax;
    /** @var array */
    protected $taxAmount;
    /** @var array */
    protected $totalAmount;

    protected $invoiceReference;


    /**
     * Invoic constructor.
     * @param null $messageId
     * @param string $identifier
     * @param string $version
     * @param string $release
     * @param string $controllingAgency
     * @param string $association
     */
    public function __construct(
        $messageId = null,
        $identifier = 'INVOIC',
        $version = 'D',
        $release = '96A',
        $controllingAgency = 'UN',
        $association = 'EAN008'
    ) {
        parent::__construct(
            $identifier,
            $version,
            $release,
            $controllingAgency,
            $messageId,
            $association
        );
        $this->items = [];
    }


    /**
     * @param $item Item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }


    /**
     * @return $this
     * @throws EdifactException
     */
    public function compose()
    {
        $this->composeByKeys();

        foreach ($this->items as $item) {
            $composed = $item->compose();
            foreach ($composed as $entry) {
                $this->messageContent[] = $entry;
            }
        }

        $this->setPositionSeparator();
        $this->composeByKeys([
            'positionSeparator',
            'invoiceReference',
            'totalPositionsAmount',
            'basisAmount',
            'taxableAmount',
            'payableAmount',
            'totalAmount',
            'taxAmount',
            'tax',
            'taxAmount',
        ]);

        // Computing total quantity
        $totalQuantity = array_reduce($this->items, function($carry, $item)
        {
            return (int)$carry + (int)$item->getQuantity()[1][1];
        });
        // Adding CNT segments
        $this->messageContent[] = ['CNT', ['1', (string)$totalQuantity]];
        $this->messageContent[] = ['CNT', ['2', (string)count($this->items)]];

        parent::compose();
        return $this;
    }

    /**
     * @return array
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     * @param string $documentType
     * @return Invoic
     * @throws EdifactException
     */
    public function setInvoiceNumber($invoiceNumber, $documentType = self::TYPE_INVOICE)
    {
        $this->isAllowed($documentType, [
            self::TYPE_INVOICE,
            self::TYPE_CREDIT_NOTE,
            self::TYPE_SERVICE_CREDIT,
            self::TYPE_SERVICE_INVOICE,
            self::TYPE_BONUS
        ]);
        $this->invoiceNumber = self::addBGMSegment($invoiceNumber, $documentType);
        return $this;
    }

    /**
     * @return array
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @param string $invoiceDate
     * @return Invoic
     * @throws EdifactException
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $this->addDTMSegment($invoiceDate, '137');
        return $this;
    }

    /**
     * @return array
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param $deliveryDate
     * @return Invoic
     * @throws EdifactException
     */
    public function setDeliveryDate($deliveryDate)
    {
        $this->deliveryDate = $this->addDTMSegment($deliveryDate, '35');
        return $this;
    }

    /**
     * @return array
     */
    public function getReductionOfFeesText()
    {
        return $this->reductionOfFeesText;
    }

    /**
     * @param string $reductionOfFeesText
     * @return Invoic
     */
    public function setReductionOfFeesText($reductionOfFeesText)
    {
        $this->reductionOfFeesText = self::addFTXSegment($reductionOfFeesText, 'OSI', 'HAE');
        return $this;
    }

    public function setRegulatoryText($regText,$corporate,$amount)
    {
        $this->reductionOfFeesText = self::addFTXSegment($regText, 'REG', '',$corporate,$amount);
        return $this;
    }



    /**
     * @return array
     */
    public function getInvoiceDescription()
    {
        return $this->invoiceDescription;
    }

    /**
     * @param string $invoiceDescription
     * @return Invoic
     */
    public function setInvoiceDescription($invoiceDescription)
    {
        $this->invoiceDescription = self::addFTXSegment($invoiceDescription, 'OSI');
        return $this;
    }

    /**
     * @return Invoic
     */
    public function setPositionSeparator()
    {
        $this->positionSeparator = ['UNS', 'S'];
        return $this;
    }

    /**
     * @return array
     */
    public function getTotalPositionsAmount()
    {
        return $this->totalPositionsAmount;
    }

    /**
     * @param string|float $totalPositionsAmount
     * @return Invoic
     */
    public function setTotalPositionsAmount($totalPositionsAmount)
    {
        $this->totalPositionsAmount = self::addMOASegment('79', $totalPositionsAmount);
        return $this;
    }

    /**
     * @return array
     */
    public function getBasisAmount()
    {
        return $this->basisAmount;
    }

    /**
     * @param string|float $basisAmount
     * @return Invoic
     */
    public function setBasisAmount($basisAmount)
    {
        $this->basisAmount = self::addMOASegment('56', $basisAmount);
        return $this;
    }

    /**
     * @return array
     */
    public function getTaxableAmount()
    {
        return $this->taxableAmount;
    }

    /**
     * @param string|float $taxableAmount
     * @return Invoic
     */
    public function setTaxableAmount($taxableAmount)
    {
        $this->taxableAmount = self::addMOASegment('125', $taxableAmount);
        return $this;
    }

    /**
     * @return array
     */
    public function getPayableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * @param string|float $payableAmount
     * @return Invoic
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payableAmount = self::addMOASegment('9', $payableAmount);
        return $this;
    }

    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount  = self::addMOASegment('128', $totalAmount);
        return $this;
    }

    /**
     * @param string|float $value
     * @param string|float $amount
     * @return $this
     */
    public function setTax($value, $amount)
    {
        $this->tax = [
            'TAX',
            '7',
            'VAT',
            '',
            EdiFactNumber::convert($amount,2),
            [
                '',
                '',
                '',
                EdiFactNumber::convert($value, 0)
            ],
            'S'
        ];
        $this->taxAmount = self::addMOASegment('124', $amount);
        return $this;
    }

    public function setInvoiceReference(string $reference){
        $this->invoiceReference = $this->addRFFSegment('IV',$reference);
        return $this;
    }
}
