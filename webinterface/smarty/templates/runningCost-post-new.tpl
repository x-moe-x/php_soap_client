<?xml version='1.0' encoding='utf-8'?>
<rows>
	<page>
		1
	</page>
	<total>
		1
	</total>
{foreach $data as $month => $row}
	<row id='{$month}'>
		<cell><![CDATA[{$months.$month}]]></cell>
		<cell><![CDATA[{$generalCosts.$month|@json_encode}]]></cell>
{foreach $row as $groupID => $values}
		<cell><![CDATA[{$values|@json_encode}]]></cell>
{/foreach}
	</row>
{/foreach}
	<row id='average'>
		<cell><![CDATA[Durchschnitt]]></cell>
		<cell><![CDATA[xxx]]></cell>
{foreach $averageRunningCosts as $groupID => $value}
		<cell><![CDATA[{$value}]]></cell>
{/foreach}
	</row>
</rows>
