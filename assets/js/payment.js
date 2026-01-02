// Payment JavaScript Functions

// Format card number with spaces
function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formattedValue.substring(0, 19); // Max 16 digits + 3 spaces
}

// Format expiry date (MM/YY)
function formatExpiryDate(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    input.value = value.substring(0, 5);
}

// Validate card number using Luhn algorithm
function validateCardNumber(cardNumber) {
    const digits = cardNumber.replace(/\D/g, '');
    if (digits.length < 13 || digits.length > 19) {
        return false;
    }
    
    let sum = 0;
    let isEven = false;
    
    for (let i = digits.length - 1; i >= 0; i--) {
        let digit = parseInt(digits[i]);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

// Validate expiry date
function validateExpiryDate(expiry) {
    const parts = expiry.split('/');
    if (parts.length !== 2) return false;
    
    const month = parseInt(parts[0]);
    const year = parseInt('20' + parts[1]);
    
    if (month < 1 || month > 12) return false;
    
    const now = new Date();
    const expiryDate = new Date(year, month - 1);
    
    return expiryDate > now;
}

// Validate CVV
function validateCVV(cvv) {
    return /^\d{3,4}$/.test(cvv);
}

// Device detection for mobile
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Initialize payment form handlers
document.addEventListener('DOMContentLoaded', function() {
    // Card number formatting
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function() {
            formatCardNumber(this);
        });
        
        cardNumberInput.addEventListener('blur', function() {
            const value = this.value.replace(/\s/g, '');
            if (value.length > 0 && !validateCardNumber(value)) {
                this.classList.add('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && !feedback.classList.contains('invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'Invalid card number';
                    this.parentElement.appendChild(errorDiv);
                }
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Expiry date formatting
    const expiryInput = document.getElementById('card_expiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            formatExpiryDate(this);
        });
        
        expiryInput.addEventListener('blur', function() {
            if (this.value.length > 0 && !validateExpiryDate(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // CVV validation
    const cvvInput = document.getElementById('card_cvv');
    if (cvvInput) {
        cvvInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
        
        cvvInput.addEventListener('blur', function() {
            if (this.value.length > 0 && !validateCVV(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Card form submission validation
    const cardForm = document.getElementById('card-payment-form');
    if (cardForm) {
        cardForm.addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            const expiry = document.getElementById('card_expiry').value;
            const cvv = document.getElementById('card_cvv').value;
            
            let isValid = true;
            
            if (!validateCardNumber(cardNumber)) {
                document.getElementById('card_number').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!validateExpiryDate(expiry)) {
                document.getElementById('card_expiry').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!validateCVV(cvv)) {
                document.getElementById('card_cvv').classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });
    }
    
    // GPay deep link for mobile
    const gpayLink = document.querySelector('a[href^="upi://"]');
    if (gpayLink && isMobileDevice()) {
        gpayLink.addEventListener('click', function(e) {
            // Let the link work normally - it should open GPay app on mobile
            // On desktop, it might not work but that's expected
        });
    }
});

// Handle Razorpay payment response
function handleRazorpayResponse(response) {
    console.log('Payment response:', response);
    // Redirect is handled in payment_process.php
}



