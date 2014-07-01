<?xml version='1.0' encoding='utf-8'?>
<rows>
	<page>1</page>
	<total>1</total>
{foreach $months as $month => $monthname}
{if $month == "average"}
	<row id='Average'>
{else}
	<row id='{$month}'>
{/if}
{foreach $data as $warehouseID => $dummy2}
{if $warehouseID == -1}
		<cell><![CDATA[{$monthname}]]></cell>
		<cell><![CDATA[{$data[$warehouseID][$month].percentage}]]></cell>
{else}
		<cell><![CDATA[{$data[$warehouseID][$month].absolute}]]></cell>
		<cell><![CDATA[{$data[$warehouseID][$month].percentageShippingRevenueCleared|@json_encode}]]></cell>
{/if}
{/foreach}
	</row>
{/foreach}
</rows>