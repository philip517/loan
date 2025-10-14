 function setLoanId(loanId) {
            document.getElementById('modal_loan_id').value = loanId;
        }
        
        // Clear modal form when closed
        document.getElementById('messageModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modal_loan_id').value = '';
            this.querySelector('form').reset();
        });
        
        // Image modal functionality
        function openImageModal(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = title;
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
        
        // Close image modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                var imageModal = bootstrap.Modal.getInstance(document.getElementById('imageModal'));
                if (imageModal) {
                    imageModal.hide();
                }
            }
        });
        
        // Add click effect to images
        document.querySelectorAll('.clickable-image').forEach(img => {
            img.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1.02)';
                }, 150);
            });
        });