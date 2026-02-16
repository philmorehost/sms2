document.addEventListener('DOMContentLoaded', function() {
    const requestOtpForm = document.getElementById('request-otp-form');
    const resetPasswordForm = document.getElementById('reset-password-form');
    const alertContainer = document.getElementById('alert-container');
    const modalAlertContainer = document.getElementById('modal-alert-container');
    const resetEmailInput = document.getElementById('reset-email');
    const emailInput = document.getElementById('email');
    const resetPasswordModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));

    // Handle the initial email submission to request an OTP
    requestOtpForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

        const formData = new FormData(this);

        fetch('ajax/send_password_reset_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success', alertContainer);
                resetEmailInput.value = emailInput.value;
                resetPasswordModal.show();
            } else {
                showAlert(data.message, 'danger', alertContainer);
            }
        })
        .catch(error => {
            showAlert('An unexpected error occurred. Please try again.', 'danger', alertContainer);
            console.error('Error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });

    // Handle the final password reset submission from the modal
    resetPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Resetting...';

        const formData = new FormData(this);

        fetch('ajax/reset_password_with_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success', modalAlertContainer);
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 2000);
            } else {
                showAlert(data.message, 'danger', modalAlertContainer);
            }
        })
        .catch(error => {
            showAlert('An unexpected error occurred. Please try again.', 'danger', modalAlertContainer);
            console.error('Error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });

    function showAlert(message, type, container) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('');
        container.innerHTML = '';
        container.append(wrapper);
    }
});
