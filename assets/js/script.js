// Small progressive enhancements. Every page also works without JavaScript.
document.addEventListener('DOMContentLoaded', function () {
  // Fade out flash messages after a few seconds.
  document.querySelectorAll('.flash').forEach(function (el) {
    setTimeout(function () { el.style.display = 'none'; }, 7000);
  });

  // Ask before destructive actions: <form data-confirm="Delete this notice?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  // Filters that submit on change: <select data-autosubmit>
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Dim a meal label when its checkbox is unticked.
  document.querySelectorAll('.mealcell input[type="checkbox"]').forEach(function (box) {
    box.addEventListener('change', function () {
      box.closest('.mealcell').classList.toggle('off', !box.checked);
    });
  });
});
