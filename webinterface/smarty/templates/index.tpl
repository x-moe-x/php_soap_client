<!DOCTYPE HTML>
<html>
    <head>
        <meta charset='utf-8'>
        <title>Net-Xpress, Plenty-Soap GUI</title>
        <link rel='stylesheet' type='text/css' href='style.css'/>
        <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
    </head>
    <body>
        <div id='errorMessages'>
            <!-- display error and debug messages -->
        </div>
        <ul id='variableManipulation'>
            <li>
                <label for='calculationTimeSingleWeighted'> Zeitraum zur Berechnung (einfach gewichtet): </label>
                <input id='calculationTimeSingleWeighted' />
            <li>
                <label for='calcualtionTimeDoubleWeighted'> Zeitraum zur Berechnung (doppelt gewichtet): </label>
                <input id='calcualtionTimeDoubleWeighted'/>
            <li>
                <label for='standardDeviationFaktor'> Faktor Standardabweichung: </label>
                <input id='standardDeviationFaktor'/>
        </ul>
        {include file="pagination.tpl"}
        <div id='filterSelection'>
            Filter: Alle anzeigen
        </div>
        <table id='resultTable'>
            {foreach from=$rows item=row name=rows}
            {if $smarty.foreach.rows.index == 0}
            <tr>
                {elseif $smarty.foreach.rows.index is even}
            <tr class='articleTableEven'>
                {else}
            <tr class='articleTableOdd'>
                {/if}
                {foreach from=$row item=item}
                {if $smarty.foreach.rows.index == 0}
                <th>{$item}</th>
                {else}
                <td>{$item}</td>
                {/if}
                {/foreach}
            </tr>
            {/foreach}
        </table>
        {include file="pagination.tpl"}
    </body>
</html>
