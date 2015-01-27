<?xml version='1.0' encoding='utf-8'?>
<rows>
	<page>
		{$data.page}
	</page>
	<total>
		{$data.total}
	</total>
{foreach $data.rows as $rowID => $jansenUnmatched}
	<row id='{$rowID}'>
		<cell>
			{$jansenUnmatched.ean}
		</cell>
		<cell>
			{$jansenUnmatched.externalItemID}
		</cell>
		<cell>
			{$jansenUnmatched.itemID}
		</cell>
		<cell>
			{$jansenUnmatched.name}
		</cell>
	</row>
{/foreach}
</rows>
