<div class="pagination">
    <ul class='paginationLinks'>
        <li class='paginateFirst'>
            {if $pagenum == 1}
            ⇤
            {else}
            <a href='?pagenum=1&pagerows={$pagerows}'>⇤</a>
            {/if}
        <li class='paginatePrevious'>
            {if $pagenum == 1}
            ←
            {else}
            <a href='?pagenum={$pagenum-1}&pagerows={$pagerows}'>←</a>
            {/if}
        <li class='paginatePagenum'>
			<select onchange="window.location.href = '?pagenum=' + this.options[this.selectedIndex].value + '&pagerows={$pagerows}'">
        		{for $page=1 to $last}
        		{if $page==$pagenum}
        		<option selected>Seite {$page}</option>
        		{else}
        		<option value='{$page}'>Seite {$page}</option>
        		{/if}
        		{/for}
        	</select>
        <li class='paginateNext'>
            {if $pagenum == $last}
            →
            {else}
            <a href='?pagenum={$pagenum+1}&pagerows={$pagerows}'>→</a>
            {/if}
        <li class='paginateLast'>
            {if $pagenum == $last}
            ⇥
            {else}
            <a href='?pagenum={$last}&pagerows={$pagerows}'>⇥</a>
            {/if}
    </ul>
    <div class='paginationPagerows'>
        <select onchange="window.location.href = '?pagenum=1&pagerows=' + this.options[this.selectedIndex].value">
            <option class='paginationPagerowsCaption'>Artikel / Seite</option>
            <option value='10'>10</option>
            <option value='20'>20</option>
            <option value='50'>50</option>
        </select>
    </div>
</div><!-- pagination -->