// Prosty JS: walidacja formularzy i drobne interakcje
document.addEventListener('DOMContentLoaded', function(){
  // prosty client-side required
  document.querySelectorAll('form[novalidate]').forEach(function(form){
    form.addEventListener('submit', function(e){
      let invalid = false;
      form.querySelectorAll('[required]').forEach(function(inp){
        if (!inp.value) {
          invalid = true;
          inp.classList.add('input-error');
        } else {
          inp.classList.remove('input-error');
        }
      });
      if (invalid) {
        e.preventDefault();
        alert('Proszę wypełnić wymagane pola.');
      }
    });
  });
});