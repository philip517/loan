<div class="modal fade" role="dialog" tabindex="-1" id="modal-1">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header text-center">
                <h4 class="modal-title">Loan Application Review</h4>
                <button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <section class="p-3" id="pending_loan_details-1" style="background: rgba(246,194,62,0.13); border-radius: 8px;">
                    <div class="row">
                        <!-- Client Details Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client Details</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>First Name:</strong> <span id="review_first_name"></span></p>
                                    <p class="mb-2"><strong>Last Name:</strong> <span id="review_last_name"></span></p>
                                    <p class="mb-2"><strong>NRC:</strong> <span id="review_nrc"></span></p>
                                    <p class="mb-2"><strong>Phone:</strong> <span id="review_phone"></span></p>
                                    <p class="mb-2"><strong>Occupation:</strong> <span id="review_occupation"></span></p>
                                    <p class="mb-2"><strong>Date Of Birth:</strong> <span id="review_dob"></span></p>
                                    <p class="mb-0"><strong>Address:</strong> <span id="review_address"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loan & Kin Details Card -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-success text-white py-2">
                                    <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Loan Details</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Amount:</strong> K<span id="review_loan_amount"></span></p>
                                    <p class="mb-2"><strong>Duration:</strong> <span id="review_duration"></span> week(s)</p>
                                    <p class="mb-2"><strong>Interest:</strong> K<span id="review_interest_amount"></span></p>
                                    <p class="mb-3"><strong>Total Repayment:</strong> K<span id="review_total_repayment"></span></p>
                                    
                                    <div class="card-header bg-info text-white py-2 mt-3">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Next of Kin</h6>
                                    </div>
                                    <div class="pt-2">
                                        <p class="mb-2"><strong>Name:</strong> <span id="review_kin_name"></span></p>
                                        <p class="mb-2"><strong>NRC:</strong> <span id="review_kin_nrc"></span></p>
                                        <p class="mb-0"><strong>Phone:</strong> <span id="review_kin_phone"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collateral Details Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-warning text-dark py-2">
                                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Collateral Details</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3"><strong>Item Name:</strong> <span id="review_collateral"></span></p>
                                    <div class="row">
                                        <div class="col-md-4 text-center mb-3">
                                            <h6>ID Image</h6>
                                            <div id="review_id_preview" class="border p-2 text-center clickable-image-preview" 
                                                 style="min-height: 150px; cursor: pointer; border-radius: 8px; display: flex; align-items: center; justify-content: center;"
                                                 onclick="openReviewImageModal('review_id_preview')">
                                                <div class="text-muted">No image selected</div>
                                            </div>
                                            <p class="small text-muted mt-2">User Identification</p>
                                        </div>
                                        <div class="col-md-4 text-center mb-3">
                                            <h6>Collateral Image 1</h6>
                                            <div id="review_image1_preview" class="border p-2 text-center clickable-image-preview" 
                                                 style="min-height: 150px; cursor: pointer; border-radius: 8px; display: flex; align-items: center; justify-content: center;"
                                                 onclick="openReviewImageModal('review_image1_preview')">
                                                <div class="text-muted">No image selected</div>
                                            </div>
                                            <p class="small text-muted mt-2">Collateral Front View</p>
                                        </div>
                                        <div class="col-md-4 text-center mb-3">
                                            <h6>Collateral Image 2</h6>
                                            <div id="review_image2_preview" class="border p-2 text-center clickable-image-preview" 
                                                 style="min-height: 150px; cursor: pointer; border-radius: 8px; display: flex; align-items: center; justify-content: center;"
                                                 onclick="openReviewImageModal('review_image2_preview')">
                                                <div class="text-muted">No image selected</div>
                                            </div>
                                            <p class="small text-muted mt-2">Collateral Alternate View</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="button" id="finalApplyButton">Apply for Loan</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal for Review -->
<div class="modal fade" role="dialog" tabindex="-1" id="reviewImageModal">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content image-modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="reviewImageModalTitle">Image Preview</h5>
                <button class="btn-close btn-close-white" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="reviewModalImage" src="" class="modal-image" alt="Enlarged Image">
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .clickable-image-preview {
        transition: transform 0.2s ease-in-out;
        border: 2px solid #dee2e6;
    }
    .clickable-image-preview:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        border-color: #007bff !important;
    }
    .modal-image {
        max-width: 100%;
        max-height: 80vh;
        width: auto;
        height: auto;
    }
    .image-modal-content {
        background: transparent;
        border: none;
    }
    .card-header {
        font-weight: 600;
    }
</style>

<script>
    function openReviewImageModal(previewId) {
        const previewElement = document.getElementById(previewId);
        const imgElement = previewElement.querySelector('img');
        
        if (imgElement && imgElement.src) {
            document.getElementById('reviewModalImage').src = imgElement.src;
            document.getElementById('reviewImageModalTitle').textContent = previewId.replace('review_', '').replace('_preview', '').replace(/_/g, ' ') + ' Preview';
            new bootstrap.Modal(document.getElementById('reviewImageModal')).show();
        }
    }
</script>