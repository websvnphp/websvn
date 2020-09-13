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

$("table.collapsible thead").find("th").on("click", function()
{
    let oldClass = $(this).get(0).className;
    let newClass = (oldClass == 'open') ? 'closed' : 'open';

    $(this).get(0).className = newClass;
    $(this).closest("table").find("tbody").toggle();
});

$("table#listing > tbody > tr").each(function()
{
    if ($(this).attr("title") == undefined)
    {
        return;
    }

    let strClass  = $(this).attr("title");
    let res       = strClass.split(" ");

    if (res.length <= 1)
    {
        return;
    }

    $(this).attr("style", "visibility: collapse");
});

$("tr").find("td.path").on("click", function(event)
{
    event.stopPropagation();

    let $target   = $(event.target);
    let trItself  = $target.closest("tr");
    let trNext    = trItself.next();
    let strClass  = trItself.attr("title");
    let res       = strClass.split(" ");

    if (trNext.attr("title") == undefined)
    {
        return;
    }

    if ($target.children('a').children('img').attr('alt') != '[DIRECTORY]')
    {
        return;
    }

    let strClassCheck = trNext.attr("title");
    let resCheck      = strClassCheck.split(" ");
    let oldAction     = trItself.attr("customaction");
    let newAction     = oldAction == 'close'? 'open' : 'close';

    trItself.attr("customaction", newAction);

    while (resCheck.includes(res[res.length - 1]))
    {
        if (trNext.attr("customaction") != newAction)
        {
            trNext.attr("customaction", newAction);

            if (newAction == 'open')
            {
                trNext.attr("style",        "visibility: visible");
                trNext.attr("customaction", newAction);
            }
            else
            {
                trNext.attr("style",        "visibility: collapse");
                trNext.attr("customaction", newAction);
            }
        }
        else
        {
            if (newAction == 'open')
            {
                trNext.attr("style", "visibility: visible");
            }
            else
            {
                trNext.attr("style", "visibility: collapse");
            }
        }

        if (trNext.attr("title") == undefined)
        {
            break;
        }

        strClassCheck = trNext.attr("title");
        resCheck      = strClassCheck.split(" ");
    }
});
