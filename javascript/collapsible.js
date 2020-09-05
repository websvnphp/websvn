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
function collapseAllDir() {
    for (var i = 1; i < tableRows.length; i++) {
        var strclass = tableRows[i].title;
        var res = strclass.split(" ");
        for (var j = i+1; j<tableRows.length; j++) {
            var strclasscheck = tableRows[j].title;
            var rescheck = strclasscheck.split(" ");
            if (rescheck.includes(res[res.length - 1])) {
                tableRows[j].style.visibility = 'collapse';
            }
            else {
                i = j-1;
                break;
            }
        }
    }
}
$("table.collapsible thead").find("th").on("click", function() {
    $(this).get(0).className = ($(this).get(0).className == 'open') ? 'closed' : 'open';
    $(this).closest("table").find("tbody").toggle();
    
});
$("tr").find("td.path").on("click",function(event) {
    event.stopPropagation();
    var $target = $(event.target);
	console.log($target.closest("tr").attr("title"));
	var strclass = $target.closest("tr").attr("title");
	var res = strclass.split(" ");
	console.log(res);
	console.log(res[res.length - 1]);
	// On Click Check
	//if (!$target.closest("tr").next().attr("title")) {
		var strclasscheck = $target.closest("tr").next().attr("title");
		var rescheck = strclasscheck.split(" ");
		var performaction = $target.closest("tr").attr("customaction") == 'close'? 'open' : 'close';
		$target.closest("tr").attr("customaction",performaction);
		while (rescheck.includes(res[res.length - 1])) {
			if ($target.closest("tr").next().attr("customaction") != performaction) {
				$target.closest("tr").next().attr("customaction",performaction);
				if (performaction == 'open') {
					$target.closest("tr").next().attr("style","visibility: visible");
					//$target.closest("tr").next().slideDown();
				}
				else {
					$target.closest("tr").next().attr("style","visibility: collapse");
					//$target.closest("tr").next().slideUp();
				}
			}
			else {
				if (performaction == 'open') {
					$target.closest("tr").next().attr("style","visibility: visible");
					//$target.closest("tr").next().slideDown();
				}
				else {
					$target.closest("tr").next().attr("style","visibility: collapse");
					//$target.closest("tr").next().slideUp();
				}
			}
			$target = $target.closest("tr").next();
			// Figure out condition to stop the looping. Find the last TR element to stop the loop.
			// Currently using the error in JS to break the loop.
			//if (!$target.closest("tr").next().attr("title")) {
				strclasscheck = $target.closest("tr").next().attr("title");
				rescheck = strclasscheck.split(" ");
			//}
			//else {
			//	break;
			//}
		}
	//}
});
