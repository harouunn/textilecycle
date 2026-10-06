@once
<script>
(() => {
  const fieldMessage = field => {
    field.setCustomValidity('');
    if (field.type === 'hidden' || !field.willValidate) return;
    let message = '';
    if (field.type === 'file') {
      const file = field.files[0];
      if (field.required && !file) message = 'Sélectionnez une image.';
      else if (file && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) message = 'L’image doit être au format JPEG, PNG ou WebP.';
      else if (file && file.size > 2 * 1024 * 1024) message = 'L’image ne doit pas dépasser 2 Mo.';
    } else {
      const value = field.value.trim();
      if (field.required && !value) message = 'Ce champ est obligatoire.';
      else if (field.type === 'number' && value) {
        const number = Number(value);
        if (!Number.isInteger(number)) message = 'Saisissez un nombre entier.';
        else if (field.min && number < Number(field.min)) message = `La valeur doit être supérieure ou égale à ${field.min}.`;
        else if (field.max && number > Number(field.max)) message = `La valeur ne peut pas dépasser ${field.max}.`;
      } else if (value) {
        const length = Array.from(value).length;
        if (field.minLength > 0 && length < field.minLength) message = `Saisissez au moins ${field.minLength} caractères.`;
        else if (field.maxLength > 0 && length > field.maxLength) message = `Ne dépassez pas ${field.maxLength} caractères.`;
      }
    }
    field.setCustomValidity(message);
    field.classList.toggle('is-invalid', !field.validity.valid);
    field.setAttribute('aria-invalid', String(!field.validity.valid));
    const errorId = field.id + '-client-error';
    let feedback = document.getElementById(errorId);
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.id = errorId;
      feedback.className = field.classList.contains('tcu-input') ? 'tcu-error' : 'invalid-feedback';
      feedback.setAttribute('aria-live', 'polite');
      field.parentElement.append(feedback);
      field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), errorId].filter(Boolean).join(' '));
    }
    feedback.textContent = field.validity.valid ? '' : field.validationMessage;
    feedback.hidden = field.validity.valid;
    field.parentElement.querySelectorAll('[data-server-error]').forEach(error => { error.hidden = true; });
  };
  for (const event of ['input', 'change']) {
    document.addEventListener(event, e => {
      if (e.target.matches('input, textarea, select') && e.target.closest('form[data-upcycling-form]')) fieldMessage(e.target);
    });
  }
  document.addEventListener('invalid', e => {
    if (e.target.closest('form[data-upcycling-form]')) {
      fieldMessage(e.target);
    }
  }, true);
  document.addEventListener('submit', e => {
    const form = e.target;
    if (!form.matches('form[data-upcycling-form]')) return;
    form.querySelectorAll('input, textarea, select').forEach(fieldMessage);
    if (!form.checkValidity()) {
      e.preventDefault();
      form.reportValidity();
    }
  });
})();
</script>
@endonce
