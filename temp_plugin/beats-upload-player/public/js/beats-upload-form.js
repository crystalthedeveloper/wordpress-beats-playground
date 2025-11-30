(function () {
  function handleUploadForm(form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const responseEl = form.querySelector('.beats-upload-response');
      if (responseEl) {
        responseEl.textContent = (window.beatsUploadForm && beatsUploadForm.uploadingText) || 'Uploading...';
        responseEl.className = 'beats-upload-response uploading';
      }
      const formData = new FormData(form);
      const ajaxUrl = (window.beatsUploadForm && beatsUploadForm.ajaxUrl) || form.action;
      fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (payload) {
          if (!responseEl) return;
          if (payload && payload.success) {
            responseEl.textContent = payload.data && payload.data.message ? payload.data.message : (beatsUploadForm && beatsUploadForm.successText) || 'Upload complete.';
            responseEl.className = 'beats-upload-response success';
            form.reset();
          } else {
            const errorMessage = payload && payload.data && payload.data.message
              ? payload.data.message
              : (beatsUploadForm && beatsUploadForm.errorText) || 'Upload failed. Please try again.';
            responseEl.textContent = errorMessage;
            responseEl.className = 'beats-upload-response error';
          }
        })
        .catch(function () {
          if (!responseEl) return;
          responseEl.textContent = (window.beatsUploadForm && beatsUploadForm.errorText) || 'Upload failed. Please try again.';
          responseEl.className = 'beats-upload-response error';
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.beats-upload-form').forEach(handleUploadForm);
  });
})();
