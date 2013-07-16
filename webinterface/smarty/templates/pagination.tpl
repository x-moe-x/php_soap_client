        <div class="pagination">
            <ul class='paginationLinks'>
                <li class='paginateFirst'>
                    {if $pagenum == 1}
                    ⇤
                    {else}
                    <a href='?pagenum=1&pagerows={$pagerows}'>⇤</a>
                    {/if}
                </li>
                <li class='paginatePrevious'>
                    {if $pagenum == 1}
                    ←
                    {else}
                    <a href='?pagenum={$pagenum-1}&pagerows={$pagerows}'>←</a>
                    {/if}
                </li>
                <li class='paginatePagenum'>
                    {$pagenum}
                </li>
                <li class='paginateNext'>
                    {if $pagenum == $last}
                    →
                    {else}
                    <a href='?pagenum={$pagenum+1}&pagerows={$pagerows}'>→</a>
                    {/if}
                </li>
                <li class='paginateLast'>
                    {if $pagenum == $last}
                    ⇥
                    {else}
                    <a href='?pagenum={$last}&pagerows={$pagerows}'>⇥</a>
                    {/if}
                </li>
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