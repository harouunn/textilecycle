{{--
  Shows the "adresse de collecte" field only when "collecte à domicile" is selected.
  Expects a select[data-toggle-collecte] (or radios with that attribute) and a #adresse-collecte-group wrapper.
--}}
@once
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var controls = document.querySelectorAll('[data-toggle-collecte]');
    var group = document.getElementById('adresse-collecte-group');
    if (!controls.length || !group) return;
    var input = group.querySelector('input, textarea');

    function currentMode() {
      var mode = '';
      controls.forEach(function (el) {
        if (el.tagName === 'SELECT' || el.checked) mode = el.value;
      });
      return mode;
    }

    function toggle() {
      var show = currentMode() === 'collecte_a_domicile';
      group.hidden = !show;
      if (input) input.required = show;
    }

    controls.forEach(function (el) { el.addEventListener('change', toggle); });
    toggle();
  });
</script>
@endpush
@endonce
