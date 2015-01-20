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
			{$jansenArticle.ean}
		</cell>
		<cell>
			{$jansenArticle.externalItemID}
		</cell>
		<cell>
			{$jansenArticle.itemID}
		</cell>
		<cell>
			{$jansenArticle.name}
		</cell>
		<cell>
			{$jansenArticle.physicalStock|@intval}
		</cell>
		<cell>
			{$jansenArticle.data|@json_encode}
		</cell>
	</row>
{/foreach}
</rows>
