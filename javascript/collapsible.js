var tableRows = document.getElementsByTagName('tr');

function toggleGroup(groupName) 
{
    for (var i = 0; i < tableRows.length; i++) 
    {
        if (tableRows[i].title == groupName) 
        {
            if (tableRows[i].style.display == 'none') 
            {
                tableRows[i].style.display = 'table-row';
            }
            else 
            {
                tableRows[i].style.display = 'none';
            }
        }
    }
}

function collapseAllGroups() 
{
    for (var i = 0; i < tableRows.length; i++) 
    {
        if (tableRows[i].title != '')
        {
            tableRows[i].style.display = 'none';
        }
    }
}

var strClass = '';
var res = '';

$("tbody > tr").each(function() {
    if ($(this).attr("title") == undefined) 
    {
        return;
    }

    if (strClass == '') 
    {
        strClass = $(this).attr("title");
        res = strClass.split(" ");
        return;
    }

    let strClassCheck = $(this).attr("title");
    let resCheck = strClassCheck.split(" ");

    if (resCheck.includes(res[res.length - 1])) 
    {
        $(this).attr("style","visibility: collapse");
    }
    else 
    {
        strClass = $(this).attr("title");
        res = strClass.split(" ");
    }
});

$("table.collapsible thead").find("th").on("click", function() 
{
    $(this).get(0).className = ($(this).get(0).className == 'open') ? 'closed' : 'open';
    $(this).closest("table").find("tbody").toggle();
});

$("tr").find("td.path").on("click",function(event) 
{
    event.stopPropagation();

    let $target = $(event.target);
    let strClass = $target.closest("tr").attr("title");
    let res = strClass.split(" ");

    if ($target.closest("tr").next().attr("title") == undefined) 
    {
        return;
    }

    if ($target.children('a').children('img').attr('alt') == '[FILE]')
    {
        return;
    }

    let strClassCheck = $target.closest("tr").next().attr("title");
    let resCheck = strClassCheck.split(" ");
    let performAction = $target.closest("tr").attr("customaction") == 'close'? 'open' : 'close';

    $target.closest("tr").attr("customaction",performAction);

    while (resCheck.includes(res[res.length - 1])) 
    {
        if ($target.closest("tr").next().attr("customaction") != performAction) 
        {
            $target.closest("tr").next().attr("customaction",performAction);

            if (performAction == 'open') 
            {
                $target.closest("tr").next().attr("style","visibility: visible");
                $target.closest("tr").next().attr("customaction",performAction);
            }
            else 
            {
                $target.closest("tr").next().attr("style","visibility: collapse");
                $target.closest("tr").next().attr("customaction",performAction);
            }
        }
        else 
        {
            if (performAction == 'open') 
            {
                $target.closest("tr").next().attr("style","visibility: visible");
            }
            else 
            {
                $target.closest("tr").next().attr("style","visibility: collapse");
            }
        }

        $target = $target.closest("tr").next();

        if ($target.closest("tr").next().attr("title") == undefined) 
        {
            break;
        }
        else 
        {
            strClassCheck = $target.closest("tr").next().attr("title");
            resCheck = strClassCheck.split(" ");
        }
    }
});
