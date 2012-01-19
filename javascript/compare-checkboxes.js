// Enforce only two compare boxes being checked at once.
function enforceOnlyTwoChecked(clickedCheckbox) {
  count = 0;
  first = null;
  form = clickedCheckbox.form;
  for (i = 0; i < form.elements.length; i++) {
    if (form.elements[i].type == 'checkbox' && form.elements[i].checked) {
      if (first == null && form.elements[i] != clickedCheckbox) {
        first = form.elements[i];
      }
      count += 1;
    }
  }
  if (count > 2) {
    first.checked = false;
    count -= 1;
  }
}
