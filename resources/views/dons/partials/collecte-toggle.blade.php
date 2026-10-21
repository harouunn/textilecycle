{{--
  Shows the "adresse de collecte" field only when "collecte à domicile" is selected.
  Expects a select[data-toggle-collecte] (or radios with that attribute) and a #adresse-collecte-group wrapper.
  Optional: elements with data-collecte="oui" are shown only for a home collection, data-collecte="non" only otherwise
  (their fields become required / not required accordingly).
--}}
@once
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var controls = document.querySelectorAll('[data-toggle-collecte]');
    if (!controls.length) return;

    var groupes = [];
    var adresse = document.getElementById('adresse-collecte-group');
    if (adresse) groupes.push({ el: adresse, collecte: true });
    document.querySelectorAll('[data-collecte]').forEach(function (el) {
      if (el !== adresse) groupes.push({ el: el, collecte: el.dataset.collecte === 'oui' });
    });

    function currentMode() {
      var mode = '';
      controls.forEach(function (el) {
        if (el.tagName === 'SELECT' || el.checked) mode = el.value;
      });
      return mode;
    }

    function toggle() {
      var collecte = currentMode() === 'collecte_a_domicile';
      groupes.forEach(function (g) {
        var visible = g.collecte === collecte;
        g.el.hidden = !visible;
        g.el.querySelectorAll('input, textarea, select').forEach(function (champ) { champ.required = visible; });
      });
    }

    controls.forEach(function (el) { el.addEventListener('change', toggle); });
    toggle();
  });
</script>
@endpush
@endonce
