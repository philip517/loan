<?php
// loan_edit_review_modal.php
?>
<!-- Review Changes Modal -->
<div class="modal fade" role="dialog" tabindex="-1" id="reviewModal">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Review Your Changes</h4>
                <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please review your changes before submitting. Your loan will need to be re-approved after these changes.
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Personal Information</h6>
                        <p><strong>Name:</strong> <span id="review_name"></span></p>
                        <p><strong>Phone:</strong> <span id="review_phone"></span></p>
                        <p><strong>Occupation:</strong> <span id="review_occupation"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Loan Details</h6>
                        <p><strong>Amount:</strong> K<span id="review_amount"></span></p>
                        <p><strong>Duration:</strong> <span id="review_duration"></span> weeks</p>
                        <p><strong>Total Repayment:</strong> K<span id="review_total"></span></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Collateral</h6>
                        <p><strong>Item:</strong> <span id="review_collateral"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Next of Kin</h6>
                        <p><strong>Name:</strong> <span id="review_kin_name"></span></p>
                        <p><strong>Phone:</strong> <span id="review_kin_phone"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" type="button" id="confirmUpdateButton">
                    <i class="fas fa-check me-2"></i>Confirm Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Review button click handler
    document.getElementById('reviewButton').addEventListener('click', function() {
        // Collect form data for review
        const firstName = document.getElementById('first_name').value;
        const lastName = document.getElementById('last_name').value;
        const phone = document.querySelector('input[name="phone"]').value;
        const occupation = document.querySelector('select[name="occupation"]').value;
        const collateralName = document.getElementById('collateral_name').value;
        const kinFirstName = document.getElementById('kin_first_name').value;
        const kinLastName = document.getElementById('kin_last_name').value;
        const kinPhone = document.querySelector('input[name="kin_phone"]').value;
        const loanAmount = document.getElementById('loan_amount').value;
        const loanDuration = document.getElementById('loan_duration').value;
        const totalRepayment = document.getElementById('total_repayment').textContent;
        
        // Populate review modal
        document.getElementById('review_name').textContent = firstName + ' ' + lastName;
        document.getElementById('review_phone').textContent = phone;
        document.getElementById('review_occupation').textContent = occupation;
        document.getElementById('review_collateral').textContent = collateralName;
        document.getElementById('review_kin_name').textContent = kinFirstName + ' ' + kinLastName;
        document.getElementById('review_kin_phone').textContent = kinPhone;
        document.getElementById('review_amount').textContent = parseFloat(loanAmount).toFixed(2);
        document.getElementById('review_duration').textContent = loanDuration;
        document.getElementById('review_total').textContent = totalRepayment;
        
        // Show modal
        const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
        reviewModal.show();
    });
    
    // Confirm update button handler
    document.getElementById('confirmUpdateButton').addEventListener('click', function() {
        // Submit the form
        document.getElementById('realUpdateButton').click();
    });
</script>