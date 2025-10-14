// Tab navigation functions
        function nextTab(tabNumber) {
            const currentTab = new bootstrap.Tab(document.querySelector(`a[href="#tab-${tabNumber}"]`));
            currentTab.show();
        }

        function prevTab(tabNumber) {
            const currentTab = new bootstrap.Tab(document.querySelector(`a[href="#tab-${tabNumber}"]`));
            currentTab.show();
        }

        // Calculate interest based on duration (12% per week)
        function calculateInterest() {
            const loanAmount = parseFloat(document.getElementById('loan_amount').value) || 0;
            const duration = parseInt(document.getElementById('loan_duration').value) || 0;
            
            const interestRate = 0.12; // 12% per week
            const interestAmount = loanAmount * interestRate * duration;
            const totalRepayment = loanAmount + interestAmount;
            
            document.getElementById('interest_amount').textContent = interestAmount.toFixed(2);
            document.getElementById('interest_percentage').textContent = (interestRate * 100 * duration) + '%';
            document.getElementById('total_repayment').textContent = totalRepayment.toFixed(2);
            
            // Enable/disable review button based on form completion
            checkFormCompletion();
        }

        // Check if all required fields are filled
        function checkFormCompletion() {
            let allFieldsFilled = true;
            
            // Check personal details
            const personalFields = [
                'first_name', 'last_name', 'date_of_birth', 'occupation', 
                'nrc', 'nationality', 'phone', 'address', 'id_image'
            ];
            
            personalFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element && !element.value) {
                    allFieldsFilled = false;
                }
            });
            
            // Check collateral details
            const collateralFields = ['collateral_name', 'collateral_image1', 'collateral_image2'];
            collateralFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element && !element.value) {
                    allFieldsFilled = false;
                }
            });
            
            // Check kin details
            const kinFields = ['kin_first_name', 'kin_last_name', 'kin_nrc', 'kin_phone'];
            kinFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (element && !element.value) {
                    allFieldsFilled = false;
                }
            });
            
            // Check loan details
            const loanAmount = document.getElementById('loan_amount').value;
            const loanDuration = document.getElementById('loan_duration').value;
            if (!loanAmount || !loanDuration) {
                allFieldsFilled = false;
            }
            
            // Enable/disable review button
            const reviewButton = document.getElementById('reviewButton');
            reviewButton.disabled = !allFieldsFilled;
            
            return allFieldsFilled;
        }

        // File preview functionality
        function previewImage(input, previewElementId) {
            const preview = document.getElementById(previewElementId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height: 150px;">`;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = 'No image selected';
            }
            
            checkFormCompletion();
        }

        // Event listeners for form fields
        document.getElementById('loan_amount').addEventListener('input', calculateInterest);
        document.getElementById('loan_duration').addEventListener('change', calculateInterest);

        // Add event listeners to all required fields
        document.querySelectorAll('input[required], select[required]').forEach(field => {
            field.addEventListener('input', checkFormCompletion);
            field.addEventListener('change', checkFormCompletion);
        });

        document.querySelector('input[name="collateral_image1"]').addEventListener('change', function() {
            previewImage(this, 'image1_preview');
        });

        document.querySelector('input[name="collateral_image2"]').addEventListener('change', function() {
            previewImage(this, 'image2_preview');
        });

        // Populate review modal
        document.getElementById('reviewButton').addEventListener('click', function() {
            if (!checkFormCompletion()) {
                alert('Please fill in all required fields before reviewing.');
                return;
            }

            // Personal details
            document.getElementById('review_first_name').textContent = document.querySelector('input[name="first_name"]').value;
            document.getElementById('review_last_name').textContent = document.querySelector('input[name="last_name"]').value;
            document.getElementById('review_nrc').textContent = document.querySelector('input[name="nrc"]').value;
            document.getElementById('review_phone').textContent = document.querySelector('input[name="phone"]').value;
            document.getElementById('review_occupation').textContent = document.querySelector('select[name="occupation"]').value;
            document.getElementById('review_dob').textContent = document.querySelector('input[name="date_of_birth"]').value;
            document.getElementById('review_address').textContent = document.querySelector('input[name="address"]').value;
            
            // Loan details
            document.getElementById('review_loan_amount').textContent = document.getElementById('loan_amount').value;
            document.getElementById('review_duration').textContent = document.getElementById('loan_duration').value;
            document.getElementById('review_interest_amount').textContent = document.getElementById('interest_amount').textContent;
            document.getElementById('review_total_repayment').textContent = document.getElementById('total_repayment').textContent;
            
            // Kin details
            document.getElementById('review_kin_name').textContent = 
                document.querySelector('input[name="kin_first_name"]').value + ' ' + 
                document.querySelector('input[name="kin_last_name"]').value;
            document.getElementById('review_kin_nrc').textContent = document.querySelector('input[name="kin_nrc"]').value;
            document.getElementById('review_kin_phone').textContent = document.querySelector('input[name="kin_phone"]').value;
            
            // Collateral details
            document.getElementById('review_collateral').textContent = document.querySelector('input[name="collateral_name"]').value;
            
            // Trigger image preview updates
            previewImage(document.querySelector('input[name="collateral_image1"]'), 'image1_preview');
            previewImage(document.querySelector('input[name="collateral_image2"]'), 'image2_preview');
            
            // Show modal
            const reviewModal = new bootstrap.Modal(document.getElementById('modal-1'));
            reviewModal.show();
        });

        // Final apply button
        document.getElementById('finalApplyButton').addEventListener('click', function() {
            // Trigger the hidden submit button
            document.getElementById('realApplyButton').click();
        });

        // Form validation before submission
        document.getElementById('loanApplicationForm').addEventListener('submit', function(e) {
            if (!checkFormCompletion()) {
                e.preventDefault();
                alert('Please fill in all required fields before submitting.');
                return;
            }
            console.log('Submitting loan application...');
        });

        // Initialize form check on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkFormCompletion();
        });

        // Add this function to handle the final submission with loading state
document.getElementById('finalApplyButton').addEventListener('click', function() {
    const finalButton = this;
    const originalText = finalButton.innerHTML;
    
    // Show loading state
    finalButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
    finalButton.disabled = true;
    
    // Submit the form after a brief delay to show loading state
    setTimeout(() => {
        document.getElementById('realApplyButton').click();
    }, 500);
});

// Enhanced form submission with better validation
document.getElementById('loanApplicationForm').addEventListener('submit', function(e) {
    if (!checkFormCompletion()) {
        e.preventDefault();
        alert('Please fill in all required fields before submitting.');
        return;
    }
    
    // Additional validation for file sizes
    const idImage = document.querySelector('input[name="id_image"]');
    const collateralImage1 = document.querySelector('input[name="collateral_image1"]');
    const collateralImage2 = document.querySelector('input[name="collateral_image2"]');
    
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (idImage.files[0] && idImage.files[0].size > maxSize) {
        e.preventDefault();
        alert('ID image is too large. Please select a file smaller than 5MB.');
        return;
    }
    
    if (collateralImage1.files[0] && collateralImage1.files[0].size > maxSize) {
        e.preventDefault();
        alert('Collateral image 1 is too large. Please select a file smaller than 5MB.');
        return;
    }
    
    if (collateralImage2.files[0] && collateralImage2.files[0].size > maxSize) {
        e.preventDefault();
        alert('Collateral image 2 is too large. Please select a file smaller than 5MB.');
        return;
    }
    
    console.log('Submitting loan application...');
});