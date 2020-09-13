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

// TODO docs,
// initially hide all non-root entries, especially in case of "setLoadAllRepos"
// only roots DON'T contain any delimiter for directories and files currently
$('table#listing > tbody > tr[title*="/"]').each(function()
{
    // "visibility: collapse" leaves some space at the bottom of the whole list, resulting in not
    // wanted scrollbars being shown by default.
    $(this).toggle();
});

// TODO docs, make parents toggle their DIRECT children only
$('table#listing > tbody').each(function()
{
    let body = $(this);

    // Each row needs to be checked for its direct children, so that only those can be toggled. The
    // "tbody" is necessary so that one can find direct children per row as well, because all those
    // are maintained on the same level and only distinguished by their textual path. So we either
    // need to search the "tbody" per row for all children or implement some other approach mapping
    // things by only iterating rows once. The current approach seems easier for now.
    body.children('tr').each(function()
    {
        let rowParent       = $(this);
        let titleParent     = rowParent.attr('title') || '';
        let selector        = 'tr[title^="' + titleParent + '/"]';
        let directChildren  = [];

        // Selectors don't support regular expressions, but direct children not only start with the
        // parent, but don't contain additional children in their title as well. One can't select
        // that condition easily, so find ALL children and filter later to direct ones only.
        body.children(selector).each(function()
        {
            let rowChild    = $(this);
            let titleChild  = rowChild.attr('title') || '';

            // TODO \Q...\E doesn't work, pattern needs to be escaped properly somehow
            // https://makandracards.com/makandra/15879-javascript-how-to-generate-a-regular-expression-from-a-string
            let ptChildDirs     = titleParent + '/$';
            let ptChildFiles    = titleParent + '/[^/]+$';
            let pattern         = ptChildDirs + '|' + ptChildFiles;

            if (titleChild.match(pattern))
            {
                directChildren.push(rowChild);
            }
        });

        rowParent.on('click', function()
        {
            $.each(directChildren, function()
            {
                $(this).toggle();
            });

            return false;
        });
    });
});
