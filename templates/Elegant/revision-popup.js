var revnum = document.getElementById('revnum');
var popup = document.getElementById('rev-popup');
document.getElementById('wrapper').removeChild(popup);
if (document.getElementById('revision') == null) {
  popup.style.display = 'block';
  addEvent(revnum, 'mouseover', function() {this.parentNode.appendChild(popup)});
  addEvent(revnum, 'mouseout',  function() {this.parentNode.removeChild(popup)});
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
