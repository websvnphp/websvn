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
