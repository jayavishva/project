// Cart functionality JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Update cart quantity with AJAX (optional enhancement)
    const quantityInputs = document.querySelectorAll('input[name="quantity"]');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                // Auto-submit on change (optional)
                // form.submit();
            }
        });
    });
    
    // Confirm before removing item
    const removeButtons = document.querySelectorAll('button[name="remove_item"]');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to remove this item from cart?')) {
                e.preventDefault();
            }
        });
    });
    
    // Update cart count in navbar (if using AJAX)
    function updateCartCount() {
        // This would be used with AJAX to update cart count without page reload
        // Implementation depends on whether AJAX is used
    }
});


