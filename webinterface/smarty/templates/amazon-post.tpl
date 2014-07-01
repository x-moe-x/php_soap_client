<?xml version='1.0' encoding='utf-8'?>
<rows>
	<page>
		{$data.page}
	</page>
	<total>
		{$data.total}
	</total>
{foreach $data.rows as $itemVariant}
	<row id='{$itemVariant.RowID}'>
		<cell>
			<![CDATA[{$itemVariant.ItemID}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.ItemNo}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.Name}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.Marking1ID}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.Quantities|@json_encode}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.Marge|@json_encode}]]>
		</cell>
		<cell>
			<![CDATA[O]]>
		</cell>
		<cell>
			<![CDATA[P]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.TimeData|@json_encode}]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.PriceOldCurrent|@json_encode}]]>
		</cell>
		<cell>
			<![CDATA[S]]>
		</cell>
		<cell>
			<![CDATA[T]]>
		</cell>
		<cell>
			<![CDATA[{$itemVariant.PriceChange|@json_encode}]]>
		</cell>

	</row>
{/foreach}
</rows>