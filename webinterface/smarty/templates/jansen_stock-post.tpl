<?xml version='1.0' encoding='utf-8'?>
<rows>
	<page>
		{$data.page}
	</page>
	<total>
		{$data.total}
	</total>
{foreach $data.rows as $rowID => $jansenArticle}
	<row id='{$rowID}'>
		<cell>
			{$jansenArticle.EAN}
		</cell>
		<cell>
			{$jansenArticle.ExternalItemID}
		</cell>
		<cell>
			{$jansenArticle.PhysicalStock}
		</cell>
		<cell>
			{$jansenArticle.ItemID}
		</cell>
		<cell>
			{$jansenArticle.Name}
		</cell>
	</row>
{/foreach}
</rows>