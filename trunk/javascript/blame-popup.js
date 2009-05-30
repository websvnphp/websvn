// Find all tags that match <a class="blame-revision" ...> and add events.
var a = document.getElementsByTagName('a');
for (var i = 0; i < a.length; i++) {
  if (a[i].className == 'blame-revision') {
    addEvent(a[i], 'mouseover', function() { mouseover(this) } );
    addEvent(a[i], 'mouseout', function() { mouseout(this) } );
  }
}

function addEvent(obj, type, func) {
  if (obj.addEventListener) {
    obj.addEventListener(type, func, false);
    return true;
  } else if (obj.attachEvent) {
    return obj.attachEvent('on'+type, func);
  } else {
    return false;
  }
}

function mouseover(a) {
  // Find the revision number within the hyperlink text
  var m = /rev=(\d+)/.exec(a.href);
  var r = m[1];
  var div = document.createElement('div');
  div.className = 'blame-popup';
  div.innerHTML = rev[r];
  a.parentNode.appendChild(div);
}

function mouseout(a) {
  var div = a.parentNode.parentNode.getElementsByTagName('div');
  for (var i = 0; i < div.length; i++) {
    if (div[i].className = 'blame-popup') {
      div[i].parentNode.removeChild(div[i]);
    }
  }
}
