document.addEventListener('DOMContentLoaded', function () {
  var sicilInput = document.getElementById('sicilNo');
  var adSoyadInput = document.getElementById('adSoyad');
  var birimSelect = document.querySelector('select[name="birim_id"]');
  var formSubmitBtn = document.querySelector('#talepForm button[type="submit"]');
  var errorDiv = null;

  if (!sicilInput || !adSoyadInput || !birimSelect) {
    return;
  }

  function showError(message) {
    removeError();
    errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-2 fade-in';
    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> <span>' + message + '</span>';
    sicilInput.parentElement.appendChild(errorDiv);
    
    if (formSubmitBtn) {
      formSubmitBtn.disabled = true;
    }
  }

  function removeError() {
    if (errorDiv) {
      errorDiv.remove();
      errorDiv = null;
    }
    if (formSubmitBtn) {
      formSubmitBtn.disabled = false;
    }
  }

  function showSuccess(message) {
    removeError();
    errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-success mt-2 fade-in';
    errorDiv.innerHTML = '<i class="bi bi-check-circle-fill"></i> <span>' + message + '</span>';
    sicilInput.parentElement.appendChild(errorDiv);
    
    setTimeout(removeError, 3000);
  }

  var timer = null;
  sicilInput.addEventListener('input', function () {
    clearTimeout(timer);
    var val = sicilInput.value.trim().toUpperCase();
    sicilInput.value = val;

    adSoyadInput.value = '';
    birimSelect.value = '';
    birimSelect.style.pointerEvents = 'auto';
    birimSelect.style.backgroundColor = '';
    removeError();

    if (val.length < 3) {
      return;
    }

    sicilInput.style.borderColor = '#f59e0b';
    
    timer = setTimeout(function () {
      fetch('api/sicil.php?sicil_no=' + encodeURIComponent(val))
        .then(function (response) { return response.json(); })
        .then(function (data) {
          sicilInput.style.borderColor = '';
          
          if (data.found) {
            adSoyadInput.value = data.ad_soyad;
            birimSelect.value = data.birim_id;
            
            adSoyadInput.readOnly = true;
            birimSelect.style.pointerEvents = 'none';
            birimSelect.style.backgroundColor = '#e9ecef';
            
            showSuccess('Personel bilgileri bulundu ve otomatik dolduruldu.');
          } else {
            showError(data.error || 'Sicil numarasi bulunamadi.');
            
            adSoyadInput.readOnly = false;
            birimSelect.style.pointerEvents = 'auto';
            birimSelect.style.backgroundColor = '';
          }
        })
        .catch(function (error) {
          sicilInput.style.borderColor = '';
          showError('Hata: ' + error.message);
          console.error('API Error:', error);
        });
    }, 500);
  });

  var talepForm = document.getElementById('talepForm');
  if (talepForm) {
    talepForm.addEventListener('submit', function(e) {
      if (errorDiv && errorDiv.classList.contains('alert-danger')) {
        e.preventDefault();
        alert('Lutfen once sicil numaranizi dogru giriniz.');
        return false;
      }
    });
  }
});
