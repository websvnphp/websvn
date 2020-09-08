var tableRows = document.getElementsByTagName('tr');

function toggleGroup(groupName) {
  for (var i = 0; i < tableRows.length; i++) {
    if (tableRows[i].title == groupName) {
      if (tableRows[i].style.display == 'none') {
        tableRows[i].style.display = 'table-row';
      } else {
        tableRows[i].style.display = 'none';
      }
    }
  }
}

function collapseAllGroups() {
  for (var i = 0; i < tableRows.length; i++) {
    if (tableRows[i].title != '')
      tableRows[i].style.display = 'none';
  }
}

var strclass = '';
var res = '';
$("tbody > tr").each(function() {

    if ($(this).attr("title") == undefined) {
        return;
    }

    if (strclass == '') {
        strclass = $(this).attr("title");
        res = strclass.split(" ");
        return;
    }

    let strclasscheck = $(this).attr("title");
    let rescheck = strclasscheck.split(" ");

    if (rescheck.includes(res[res.length - 1])) {
        $(this).attr("style","visibility: collapse");
    }
    else {
        strclass = $(this).attr("title");
        res = strclass.split(" ");
    }

});

$("table.collapsible thead").find("th").on("click", function() {
    $(this).get(0).className = ($(this).get(0).className == 'open') ? 'closed' : 'open';
    $(this).closest("table").find("tbody").toggle();
});

$("tr").find("td.path").on("click",function(event) {

    event.stopPropagation();

    let $target = $(event.target);
    let strclass = $target.closest("tr").attr("title");
    let res = strclass.split(" ");

    if ($target.closest("tr").next().attr("title") == undefined) {
        return;
    }

    let strclasscheck = $target.closest("tr").next().attr("title");
    let rescheck = strclasscheck.split(" ");
    let performaction = $target.closest("tr").attr("customaction") == 'close'? 'open' : 'close';

    $target.closest("tr").attr("customaction",performaction);

    while (rescheck.includes(res[res.length - 1])) {
        if ($target.closest("tr").next().attr("customaction") != performaction) {
            $target.closest("tr").next().attr("customaction",performaction);

            if (performaction == 'open') {
                $target.closest("tr").next().attr("style","visibility: visible");
                $target.closest("tr").next().attr("customaction",performaction);
            }
            else {
                $target.closest("tr").next().attr("style","visibility: collapse");
                $target.closest("tr").next().attr("customaction",performaction);
            }
        }
        else {

            if (performaction == 'open') {
                $target.closest("tr").next().attr("style","visibility: visible");
            }
            else {
                $target.closest("tr").next().attr("style","visibility: collapse");
            }
        }

        $target = $target.closest("tr").next();

        if ($target.closest("tr").next().attr("title") == undefined) {
            break;
        }
        else {
            strclasscheck = $target.closest("tr").next().attr("title");
            rescheck = strclasscheck.split(" ");
        }
    }

});
