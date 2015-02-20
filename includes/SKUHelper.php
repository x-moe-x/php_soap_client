<?php

/**
 * convert an SKU to it's components
 *
 * @param string $SKUString
 *
 * @return array(ItemID, PriceID, AttributeValueSetID)
 */
function SKU2Values($SKUString)
{
	if ((preg_match('/(\d+)-(\d+)-(\d+)/', $SKUString, $matches) == 1) && (count($matches) == 4))
	{
		return array(
			$matches[1],
			$matches[2],
			$matches[3]
		);
	} else
	{
		return null;
	}
}

/**
 * assemble an SKU from it's components
 *
 * @param int $ItemsID
 * @param int $AttributeValueSetID
 * @param int $PriceID
 *
 * @return string SKU-string
 */
function Values2SKU($ItemsID, $AttributeValueSetID = 0, $PriceID = 0)
{
	return $ItemsID . '-' . $PriceID . '-' . $AttributeValueSetID;
}
