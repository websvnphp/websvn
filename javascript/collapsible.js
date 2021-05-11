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

/**
 * Hide all non-root entries currently visible.
 * <p>
 * Depending on the config, the site is able to load ALL files and directories of the current repo
 * recursively to prevent additional requests. The use case is to hide all of the root-dirs and let
 * users simply toggle the next level of interest without additional waiting. This is implemented by
 * using the {@code title}-attribute and the {@code /}-character to represent some path and ONLY the
 * root-dirs themself to show DON'T contain such. All other entries have some, either because they
 * belong to a subdir or file within some parent dir.
 * </p>
 * <p>
 * There's currently no additional config necessary to apply this JS or not, because the rows worked
 * on are only generated in case recursive loading is enabled already!
 * </p>
 */
$('table#listing > tbody > tr[title*="/"]').each(function()
{
    // "visibility: collapse" leaves some space at the bottom of the whole list, resulting in not
    // wanted scrollbars being shown by default.
    $(this).hide();
});

/**
 * Select all direct children for the given parent.
 * <p>
 * The markup doesn't model parent-child relationships currently, but instead all directories,
 * files etc. are maintained as individual rows one after each other. In theory not even the order
 * of those rows needs to be alphabetically or fit the parent-child-order in the repo or else, all
 * can be mixed-up. So this function searches all rows of the given {@code body} for DIRECT children
 * of the given parent row, which can be identified by their paths maintained in the {@code title}-
 * attribute. Such filtering is non-trivial and one can't use CSS-selectors only, because those
 * paths need to fulfill certain conditions.
 * </p>
 * @param[in] body
 * @param[in] rowParent
 * @return Array with direct children of the given parent.
 */
function recursiveLoadDirectChildrenSelect(body, rowParent)
{
    // The parent title is used in some reg exp, so properly escape/quote it. Sadly "\Q...\E" does
    // not work and JS doesn't seem to provide anythign else on its own as well.
    // https://stackoverflow.com/a/3561711/2055163
    let titleParent     = rowParent.attr('title') || '';
        titleParent     = titleParent.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    let selector        = 'tr[title^="' + titleParent + '/"]';
    let directChildren  = [];

    // Selectors don't support regular expressions, but direct children not only start with the
    // parent, but don't contain additional children in their title as well. One can't select
    // that condition easily, so find ALL children and filter later to direct ones only.
    body.children(selector).each(function()
    {
        let rowChild    = $(this);
        let titleChild  = rowChild.attr('title') || '';

        let ptChildDirs     = titleParent + '/$';
        let ptChildFiles    = titleParent + '/[^/]+$';
        let pattern         = ptChildDirs + '|' + ptChildFiles;

        if (titleChild.match(pattern))
        {
            directChildren.push(rowChild);
        }
    });

    return directChildren;
}

/**
 * Click-handler for some parent directory.
 * <p>
 * What needs to happen depends on the visibility of the direct children associated with some parent
 * and therefore given: If children are NOT visible, simply show them and ONLY those, as showing
 * should not be recursive currently. If children are visible OTOH, ALL of those need to be hidden
 * or otherwise some lower level children would still be shown. This is because of the currently
 * used markup, which doesn't model parent-child-relationships properly, but places everything at
 * one level. Someone needs to take care of hiding children of children of children, when hiding
 * associated markup itself only hides some of those. To hide recursively, a custom event named
 * {@code hide_children} seems the easiest approach currently, which is then simply handled by the
 * parent directory again.
 * </p>
 * @param[in] directChildren
 * @return {@code false} to stop propagation of the current event.
 */
function recursiveLoadRowParentOnClick(directChildren)
{
    $.each(directChildren, function()
    {
        let self        = $(this);
        let isVisible   = self.is(':visible');

        if (!isVisible)
        {
            self.show();
            return true;
        }

        self.trigger('hide_children');
        self.hide();
    });

    return false;
}

/**
 * Handler to hide direct children.
 * <p>
 * While showing only the next level of children is wanted, when hiding ALL levels need to be hidden
 * instead. This can not easily be achieved with the current markup placing ALL directories, files
 * etc. regardless of their depth on the same level. So a special event is registered on each dir
 * to simply hide ALL of it's own children and that event is triggered on ALL children of some dir
 * as necessary.
 * </p>
 * @param[in] directChildren
 * @return {@code false} to stop propagation of the current event.
 */
function recursiveLoadRowParentOnHideChildren(directChildren)
{
    $.each(directChildren, function()
    {
        let self = $(this);

        self.trigger('hide_children');
        self.hide();
    });

    return false;
}

/**
 * Process one row when loading all directories and files of some repo recursively.
 * <p>
 * Markup currently doesn't proiperly model parent-child-relationships, so the current approach is
 * to iterated all rows of some rendered table to find direct children on our own. Those children
 * are the once to show or hide in the end any by iterating all rows and search the whole body for
 * children, all of those can be found easily to register necessary event handlers.
 * </p>
 * @param[in] body
 * @param[in] rowParent
 */
function recursiveLoadRowProc(body, rowParent)
{
    let directChildren = recursiveLoadDirectChildrenSelect(body, rowParent);

    rowParent.find('td.path a[href^="listing.php?"]').on('click', function()
    {
        return recursiveLoadRowParentOnClick(directChildren);
    });

    rowParent.on('hide_children', function()
    {
        return recursiveLoadRowParentOnHideChildren(directChildren);
    });
}

/**
 * Register event handlers to hide and show children of root-directories.
 */
$('table#listing > tbody').each(function()
{
    let body = $(this);

    // Each row needs to be checked for its direct children, so that only those can be toggled. The
    // "tbody" is necessary so that one can find direct children per row as well, because all those
    // are maintained on the same level and only distinguished by their textual path. So we either
    // need to search the "tbody" per row for all children or implement some other approach mapping
    // things by only iterating rows once. The current approach seems easier for now.
    body.children('tr').each(function() { recursiveLoadRowProc(body, $(this)); });
});
