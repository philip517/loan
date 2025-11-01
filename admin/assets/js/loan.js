
        function viewLoanDetails(loanId) {
            // Redirect to loan details page with the loan ID
            window.location.href = 'loan_review.php?loan_id=' + loanId;
        }
        
        // Search functionality for all tables
        function initializeSearch(searchInputId, tableId) {
            const searchInput = document.getElementById(searchInputId);
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = document.getElementById(tableId);
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }
        }
        
        // Optional: Add keyboard navigation support
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr[onclick]');
            rows.forEach(row => {
                row.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const onclickAttr = this.getAttribute('onclick');
                        const match = onclickAttr.match(/viewLoanDetails\((\d+)\)/);
                        if (match) {
                            viewLoanDetails(match[1]);
                        }
                    }
                });
                
                // Make rows focusable for accessibility
                row.setAttribute('tabindex', '0');
                row.classList.add('clickable-row');
            });
            
            // Initialize search functionality for all tabs
            initializeSearch('searchPending', 'pendingTable');
            initializeSearch('searchApproved', 'approvedTable');
            initializeSearch('searchRejected', 'rejectedTable');
            
            // Clear search when switching tabs
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Clear all search inputs
                    document.getElementById('searchPending').value = '';
                    document.getElementById('searchApproved').value = '';
                    document.getElementById('searchRejected').value = '';
                    
                    // Show all rows again
                    const tables = ['pendingTable', 'approvedTable', 'rejectedTable'];
                    tables.forEach(tableId => {
                        const table = document.getElementById(tableId);
                        if (table) {
                            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                            for (let i = 0; i < rows.length; i++) {
                                rows[i].style.display = '';
                            }
                        }
                    });
                });
            });
        });
    