   // Tab navigation functions
        function nextTab(tabNumber) {
            const nextTabElement = document.querySelector(`a[href="#tab-${tabNumber}"]`);
            if (nextTabElement) {
                const nextTab = new bootstrap.Tab(nextTabElement);
                nextTab.show();
            }
        }

        function prevTab(tabNumber) {
            const prevTabElement = document.querySelector(`a[href="#tab-${tabNumber}"]`);
            if (prevTabElement) {
                const prevTab = new bootstrap.Tab(prevTabElement);
                prevTab.show();
            }
        }

        // Image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Interest calculation function
        function calculateInterest() {
            const loanAmount = parseFloat(document.getElementById('loan_amount').value) || 0;
            const duration = parseInt(document.getElementById('loan_duration').value) || 0;
            
            // Interest rate: 10% per week
            const interestRate = 0.10;
            const interest = loanAmount * interestRate * duration;
            const totalRepayment = loanAmount + interest;
            
            // Update display
            document.getElementById('display_loan_amount').textContent = loanAmount.toFixed(2);
            document.getElementById('interest_amount').textContent = interest.toFixed(2);
            document.getElementById('total_repayment').textContent = totalRepayment.toFixed(2);
            
            // Calculate and display interest percentage
            const interestPercentage = loanAmount > 0 ? (interest / loanAmount) * 100 : 0;
            document.getElementById('interest_percentage').textContent = interestPercentage.toFixed(1) + '%';
        }

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateInterest();
            
            // Review button functionality
            document.getElementById('reviewButton').addEventListener('click', function() {
                // Populate review modal with form data
                document.getElementById('review_first_name').textContent = document.getElementById('first_name').value;
                document.getElementById('review_last_name').textContent = document.getElementById('last_name').value;
                document.getElementById('review_nrc').textContent = document.querySelector('input[name="nrc"]').value;
                document.getElementById('review_phone').textContent = document.querySelector('input[name="phone"]').value;
                document.getElementById('review_occupation').textContent = document.querySelector('select[name="occupation"]').value;
                document.getElementById('review_dob').textContent = document.querySelector('input[name="date_of_birth"]').value;
                document.getElementById('review_address').textContent = document.querySelector('input[name="address"]').value;
                document.getElementById('review_loan_amount').textContent = document.getElementById('loan_amount').value;
                document.getElementById('review_duration').textContent = document.getElementById('loan_duration').value;
                document.getElementById('review_interest_amount').textContent = document.getElementById('interest_amount').textContent;
                document.getElementById('review_total_repayment').textContent = document.getElementById('total_repayment').textContent;
                document.getElementById('review_kin_name').textContent = document.getElementById('kin_first_name').value + ' ' + document.getElementById('kin_last_name').value;
                document.getElementById('review_kin_nrc').textContent = document.getElementById('kin_nrc').value;
                document.getElementById('review_kin_phone').textContent = document.querySelector('input[name="kin_phone"]').value;
                document.getElementById('review_collateral').textContent = document.getElementById('collateral_name').value;

                // Update image previews in review modal
                const idPreview = document.getElementById('id_preview');
                const image1Preview = document.getElementById('image1_preview');
                const image2Preview = document.getElementById('image2_preview');

                if (idPreview.src) {
                    document.getElementById('review_id_preview').innerHTML = `<img src="${idPreview.src}" class="img-fluid" style="max-height: 140px;">`;
                }
                if (image1Preview.src) {
                    document.getElementById('review_image1_preview').innerHTML = `<img src="${image1Preview.src}" class="img-fluid" style="max-height: 140px;">`;
                }
                if (image2Preview.src) {
                    document.getElementById('review_image2_preview').innerHTML = `<img src="${image2Preview.src}" class="img-fluid" style="max-height: 140px;">`;
                }

                // Show review modal
                new bootstrap.Modal(document.getElementById('modal-1')).show();
            });

            // Final apply button functionality
            document.getElementById('finalApplyButton').addEventListener('click', function() {
                document.getElementById('realApplyButton').click();
            });

            // Initialize tab functionality
            const firstTab = document.querySelector('.nav-tabs .nav-link');
            if (firstTab) {
                firstTab.click();
            }
        });

        function openImageModal(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }