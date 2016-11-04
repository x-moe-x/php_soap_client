<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class PrepareUpdateItemPositions represents a pseudo-call to check for and correct brutto/netto issues between
 * plenty and paypal. Plenty internally uses 4 digits of decimal precision but hand out 2 digits precise decimals to
 * paypal
 */
class PriceCorrection extends NX_Executable
{
    /**
     * @var array
     */
    private $aModifiedPriceSets;

    const EPSILON = 0.00001;

    /**
     * PriceCorrection constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);

        $this->aModifiedPriceSets = array();
    }

    public function execute()
    {
        // get all prices (gross)
        $dbResult = DBQuery::getInstance()->select($this->getQuery());

        // check prices for problematic quantities:
        while ($currentPriceSet = $dbResult->fetchAssoc()) {
            $isUpdateNecessary = false;
            $modifiedPrice = 0.00;

            $calculationData = [
                [
                    "id" => $currentPriceSet['ItemID'],
                    "price" => $currentPriceSet['Price'],
                    "min" => 0,
                    "max" => $currentPriceSet['RebateLevelPrice6'] - 1,
                    "vatFactor" => 1.19,
                    "PriceName" => 'Price',
                ], [
                    "id" => $currentPriceSet['ItemID'],
                    "price" => $currentPriceSet['Price6'],
                    "min" => $currentPriceSet['RebateLevelPrice6'],
                    "max" => $currentPriceSet['RebateLevelPrice7'] - 1,
                    "vatFactor" => 1.19,
                    "PriceName" => 'Price6',
                ], [
                    "id" => $currentPriceSet['ItemID'],
                    "price" => $currentPriceSet['Price7'],
                    "min" => $currentPriceSet['RebateLevelPrice7'],
                    "max" => $currentPriceSet['RebateLevelPrice8'] - 1,
                    "vatFactor" => 1.19,
                    "PriceName" => 'Price7',
                ], [
                    "id" => $currentPriceSet['ItemID'],
                    "price" => $currentPriceSet['Price8'],
                    "min" => $currentPriceSet['RebateLevelPrice8'],
                    "max" => INF,
                    "vatFactor" => 1.19,
                    "PriceName" => 'Price8',
                ],
            ];

            $updateData = ["PriceSetID" => $currentPriceSet['PriceID']];

            foreach ($calculationData as $data) {
                if ($this->isPriceModificationNecessary($data['price'], $data['min'], $data['max'], $data['vatFactor'], $modifiedPrice)) {
                    $updateData[$data['PriceName']] = $modifiedPrice;
                    $isUpdateNecessary = true;
                }
            }

            if ($isUpdateNecessary) {
                $this->aModifiedPriceSets[] = $updateData;
            }
        }

        // store results
        $this->storeToDB();
    }

    /**
     * @param float     $price         initial price value (gross)
     * @param int|float $minQuantity
     * @param int|float $maxQuantity
     * @param float     $vat
     * @param float     $modifiedPrice reference to additional output parameter
     *
     * @return bool
     */
    private function isPriceModificationNecessary($price, $minQuantity, $maxQuantity, $vat, &$modifiedPrice)
    {
        $modifiedPrice = round($price, 2);
        $difference = $price - $modifiedPrice;

        // if modification is necessary ...
        if ($maxQuantity === INF && abs($price - $modifiedPrice) > self::EPSILON || abs(round($maxQuantity * $difference, 2)) > self::EPSILON || abs(round($minQuantity * $difference, 2)) > self::EPSILON) {
            // ... prepare net prices ...
            $roundedPriceNet = round($price / $vat, 2);
            $roundedModifiedPriceNet = round($modifiedPrice / $vat, 2);

            // ... and adjust modified price to match same net price
            if ($roundedPriceNet > $roundedModifiedPriceNet) {
                $modifiedPrice += 0.01;
            } elseif ($roundedPriceNet < $roundedModifiedPriceNet) {
                $modifiedPrice -= 0.01;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    private function getQuery()
    {
        return 'SELECT * FROM `PriceSets`';
    }

    private function storeToDB()
    {
        $countModifiedPriceSets = count($this->aModifiedPriceSets);
        //print(__FUNCTION__ . ", not yet implemented\n $countModifiedPriceSets PriceSets would have been updated");
        if ($countModifiedPriceSets > 0) {
            $this->debug(__FUNCTION__ . ": inserting $countModifiedPriceSets PriceSet updates into db");
            DBQuery::getInstance()->insert('INSERT INTO SetPriceSets' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->aModifiedPriceSets));
        } else {
            $this->debug(__FUNCTION__ . ": no PriceSet updates required");
        }
    }
}