// <!-- Bootstrap Validation Script -->


// Bootstrap validation initialization
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()

function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);

    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "🙈";
    } else {
        input.type = "password";
        btn.textContent = "👁";
    }
}
// Custom validation for password matching
document.addEventListener('DOMContentLoaded', function() {
  const currentPasswordField = document.getElementById('current_password');
  const newPasswordField = document.getElementById('new_password');
  const confirmPasswordField = document.getElementById('confirm_password');
  
  // Password length validation (real-time)
  if (newPasswordField) {
    newPasswordField.addEventListener('input', function() {
      if (this.value.length >= 6) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }
  
  // Password matching validation (real-time)
  if (confirmPasswordField && newPasswordField) {
    confirmPasswordField.addEventListener('input', function() {
      if (this.value === newPasswordField.value) {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.setCustomValidity('Passwords do not match');
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }
});



// Bootstrap validation initialization
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()

// Custom validation for password matching and length (real-time visual feedback)
document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirm_password');
  m
  if (passwordField && confirmPasswordField) {
    // Password length validation (real-time)
    passwordField.addEventListener('input', function() {
      if (this.value.length >= 8) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
    
    // Password matching validation (real-time)
    confirmPasswordField.addEventListener('input', function() {
      // Only validate password matching, don't show alerts during typing
      if (this.value === passwordField.value) {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.setCustomValidity('Passwords do not match');
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }
});


function preventAtSymbol(event) {
        // Prevent typing @ symbol (key code 50 with Shift or 64)
        if (event.key === '@' || (event.shiftKey && event.key === '2')) {
          event.preventDefault();
        }
      }

      // Prevent pasting in email field
      function preventPasting(event) {
        event.preventDefault();
        const emailField = event.target;
        emailField.setCustomValidity('Pasting is not allowed in this field. Please type the username manually.');
        emailField.classList.remove('is-valid');
        emailField.classList.add('is-invalid');
      }

      // Prevent special characters in email field
      function preventSpecialChars(event) {
        const allowedChars = /^[A-Za-z][A-Za-z0-9._ -]*$/;
        const key = event.key;
        const emailField = event.target;
        
        // Allow control keys (Backspace, Delete, Tab, Enter, Arrow keys, etc.)
        if (event.ctrlKey || event.metaKey || 
            key === 'Backspace' || key === 'Delete' || key === 'Tab' || key === 'Enter' ||
            key === 'ArrowLeft' || key === 'ArrowRight' || key === 'ArrowUp' || key === 'ArrowDown' ||
            key === 'Home' || key === 'End' || key === 'Escape') {
          return;
        }
        
        // Prevent special characters (no alert - silent prevention)
        if (!allowedChars.test(key)) {
          event.preventDefault();
          emailField.setCustomValidity('Special characters are not allowed in email username.');
          emailField.classList.remove('is-valid');
          emailField.classList.add('is-invalid');
        } else {
          emailField.setCustomValidity('');
          emailField.classList.remove('is-invalid');
          emailField.classList.add('is-valid');
        }
      }

      // Initialize email field restrictions when DOM is loaded
      document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.querySelector('input[name="email"]');
        if (emailField) {
          // Apply restrictions to signup forms, manage coordinators form, and manage hod form
          // Also apply to manage profile form - users should be able to edit their email
          // but still need validation restrictions
          if (window.location.pathname.includes('/signup') || 
              window.location.pathname.includes('/register') || 
              window.location.pathname.includes('/manage/coordinators') ||
              window.location.pathname.includes('/manage/profile') ||
              window.location.pathname.includes('/manage/hod')) {
            emailField.addEventListener('paste', preventPasting);
            emailField.addEventListener('keydown', preventSpecialChars);
            
            // Additional validation for email field
            emailField.addEventListener('input', function() {
              // Prevent numbers at the start of email username
              if (/^[0-9]/.test(this.value)) {
                this.setCustomValidity('Email username cannot start with a number');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                this.value = this.value.replace(/^[0-9]+/, '');
              } else if (this.value.length < 3) {
                this.setCustomValidity('Email username must be at least 3 characters long');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
              } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
              }
            });
          }
        }

        // Initialize contact number field restrictions
        const contactField = document.querySelector('input[name="contact_number"]');
        if (contactField) {
          contactField.addEventListener('input', function(event) {
            // Remove non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 10 digits maximum
            if (this.value.length > 10) {
              this.value = this.value.slice(0, 10);
            }
            
            // Validate contact number format
            if (this.value.length > 0) {
              if (this.value.length !== 10) {
                this.setCustomValidity('Contact number must be exactly 10 digits');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
              } else if (!/^[7-9]/.test(this.value)) {
                this.setCustomValidity('Contact number must start with digits 7-9');
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
              } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
              }
            } else {
              this.setCustomValidity('Contact number is required');
              this.classList.remove('is-valid');
              this.classList.add('is-invalid');
            }
          });
        }

        // Initialize username field restrictions
        const usernameField = document.querySelector('input[name="username"]');
        if (usernameField) {
          usernameField.addEventListener('input', function(event) {
            
            // Prevent numbers at the start of username
             if (/^[0-9]/.test(this.value)) {
              this.setCustomValidity('Username cannot start with a number');
              this.classList.remove('is-valid');
              this.classList.add('is-invalid');
              this.value = this.value.replace(/^[0-9]+/, '');
            } else if (this.value.length < 3) {
              this.setCustomValidity('Username must be at least 3 characters long');
              this.classList.remove('is-valid');
              this.classList.add('is-invalid');
            } else {
              this.setCustomValidity('');
              this.classList.remove('is-invalid');
              this.classList.add('is-valid');
            }
          });
        }

        // Initialize recovery email field restrictions
        const recoveryEmailField = document.querySelector('input[name="recovery_email"]');
        if (recoveryEmailField) {
          recoveryEmailField.addEventListener('input', function(event) {
            const recoveryEmail = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (recoveryEmail === '') {
              this.setCustomValidity('Recovery email is required');
              this.classList.remove('is-valid');
              this.classList.add('is-invalid');
            } else if (!emailRegex.test(recoveryEmail)) {
              this.setCustomValidity('Please enter a valid recovery email address');
              this.classList.remove('is-valid');
              this.classList.add('is-invalid');
            } else {
              this.setCustomValidity('');
              this.classList.remove('is-invalid');
              this.classList.add('is-valid');
            }
          });
        }
      });

      // Custom validation for password matching in manage profile
document.addEventListener('DOMContentLoaded', function() {
  const currentPasswordField = document.getElementById('current_password');
  const newPasswordField = document.getElementById('new_password');
  const confirmPasswordField = document.getElementById('confirm_password');
  
  // Password length validation (real-time)
  if (newPasswordField) {
    newPasswordField.addEventListener('input', function() {
      if (this.value.length >= 6) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }
  
  // Password matching validation (real-time)
  if (confirmPasswordField && newPasswordField) {
    confirmPasswordField.addEventListener('input', function() {
      if (this.value === newPasswordField.value) {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.setCustomValidity('Passwords do not match');
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }
  
  // Password change validation - if new password is entered, require current and confirm
  if (newPasswordField && currentPasswordField && confirmPasswordField) {
    newPasswordField.addEventListener('input', function() {
      validatePasswordChange();
    });
    
    currentPasswordField.addEventListener('input', function() {
      validatePasswordChange();
    });
    
    confirmPasswordField.addEventListener('input', function() {
      validatePasswordChange();
    });
  }
  
  function validatePasswordChange() {
    const newPassword = newPasswordField.value.trim();
    const currentPassword = currentPasswordField.value.trim();
    const confirmPassword = confirmPasswordField.value.trim();
    
    // If new password is entered, all three fields are required
    if (newPassword.length > 0) {
      if (currentPassword.length === 0) {
        currentPasswordField.setCustomValidity('Current password is required when changing password');
        currentPasswordField.classList.remove('is-valid');
        currentPasswordField.classList.add('is-invalid');
      } else {
        currentPasswordField.setCustomValidity('');
        currentPasswordField.classList.remove('is-invalid');
        currentPasswordField.classList.add('is-valid');
      }
      
      if (confirmPassword.length === 0) {
        confirmPasswordField.setCustomValidity('Please confirm the new password');
        confirmPasswordField.classList.remove('is-valid');
        confirmPasswordField.classList.add('is-invalid');
      } else if (confirmPassword !== newPassword) {
        confirmPasswordField.setCustomValidity('Passwords do not match');
        confirmPasswordField.classList.remove('is-valid');
        confirmPasswordField.classList.add('is-invalid');
      } else {
        confirmPasswordField.setCustomValidity('');
        confirmPasswordField.classList.remove('is-invalid');
        confirmPasswordField.classList.add('is-valid');
      }
    } else {
      // If no new password, clear validation for password fields
      currentPasswordField.setCustomValidity('');
      confirmPasswordField.setCustomValidity('');
      currentPasswordField.classList.remove('is-invalid', 'is-valid');
      confirmPasswordField.classList.remove('is-invalid', 'is-valid');
    }
  }

  // Apply preventAtSymbol function to email field in manage profile
  const emailField = document.getElementById('email');
  if (emailField) {
    emailField.addEventListener('keydown', preventAtSymbol);
  }
});

      // Custom validation for HOD form (create and update)
document.addEventListener('DOMContentLoaded', function() {
  // Password validation for create HOD form
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirm_password');
  
  if (passwordField && confirmPasswordField) {
    // Password length validation (real-time)
    passwordField.addEventListener('input', function() {
      if (this.value.length >= 8) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
    
    // Password matching validation (real-time)
    confirmPasswordField.addEventListener('input', function() {
      if (this.value === passwordField.value) {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      } else {
        this.setCustomValidity('Passwords do not match');
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
      }
    });
  }

  // Apply preventAtSymbol function to email field in manage hod form
  const emailField = document.getElementById('email');
  if (emailField) {
    emailField.addEventListener('keydown', preventAtSymbol);
  }
});

      // File validation for signup forms
      function validateFiles() {
        const profileImage = document.querySelector('input[name="profile_image"]').files[0];
        const signImage = document.querySelector('input[name="sign_image"]').files[0];
        
        // File validation
        if (!profileImage || !signImage) {
          const profileField = document.querySelector('input[name="profile_image"]');
          const signField = document.querySelector('input[name="sign_image"]');
          
          if (!profileImage) {
            profileField.setCustomValidity('Please select a profile image');
            profileField.classList.remove('is-valid');
            profileField.classList.add('is-invalid');
          }
          
          if (!signImage) {
            signField.setCustomValidity('Please select a signature image');
            signField.classList.remove('is-valid');
            signField.classList.add('is-invalid');
          }
          
          return false;
        }

        // File type validation
        const allowedTypes = ['image/jpeg', 'image/png'];
        const profileField = document.querySelector('input[name="profile_image"]');
        const signField = document.querySelector('input[name="sign_image"]');
        
        if (profileImage && !allowedTypes.includes(profileImage.type)) {
          profileField.setCustomValidity('Profile image must be JPEG or PNG format');
          profileField.classList.remove('is-valid');
          profileField.classList.add('is-invalid');
          return false;
        } else {
          profileField.setCustomValidity('');
          profileField.classList.remove('is-invalid');
          profileField.classList.add('is-valid');
        }
        
        if (signImage && !allowedTypes.includes(signImage.type)) {
          signField.setCustomValidity('Signature image must be JPEG or PNG format');
          signField.classList.remove('is-valid');
          signField.classList.add('is-invalid');
          return false;
        } else {
          signField.setCustomValidity('');
          signField.classList.remove('is-invalid');
          signField.classList.add('is-valid');
        }

        // File size validation (2MB limit)
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (profileImage && profileImage.size > maxSize) {
          profileField.setCustomValidity('Profile image must be less than 2MB');
          profileField.classList.remove('is-valid');
          profileField.classList.add('is-invalid');
          return false;
        }
        
        if (signImage && signImage.size > maxSize) {
          signField.setCustomValidity('Signature image must be less than 2MB');
          signField.classList.remove('is-valid');
          signField.classList.add('is-invalid');
          return false;
        }

        return true;
      }
