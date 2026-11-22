(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.digits input'));
  if (!boxes.length) {
    return;
  }

  function digitsOnly(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function fill(start, digits) {
    var take = digits.slice(0, boxes.length - start);
    var i;
    for (i = 0; i < take.length; i += 1) {
      boxes[start + i].value = take.charAt(i);
    }
    boxes[Math.min(start + take.length, boxes.length - 1)].focus();
  }

  var pasted = false;

  boxes.forEach(function (box, index) {
    box.addEventListener('paste', function (event) {
      var clip = event.clipboardData ? event.clipboardData.getData('text') : '';
      var digits = digitsOnly(clip);
      if (!digits) {
        return;
      }
      event.preventDefault();
      pasted = true;
      fill(digits.length >= boxes.length ? 0 : index, digits);
      setTimeout(function () {
        pasted = false;
      }, 0);
    });

    box.addEventListener('input', function () {
      if (pasted) {
        return;
      }
      var digits = digitsOnly(box.value);
      if (digits.length > 1) {
        fill(digits.length >= boxes.length ? 0 : index, digits);
        return;
      }
      box.value = digits;
      if (digits && index < boxes.length - 1) {
        boxes[index + 1].focus();
      }
    });

    box.addEventListener('keydown', function (event) {
      if (event.key === 'Backspace' && box.value === '' && index > 0) {
        boxes[index - 1].value = '';
        boxes[index - 1].focus();
      }
    });
  });
}());
