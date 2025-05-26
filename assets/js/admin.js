document.addEventListener('DOMContentLoaded', function() {
    // Handle collapsible groups
    document.querySelectorAll('.group-header').forEach(function(header) {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isOpen = this.classList.toggle('open');
            
            if (isOpen) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    });

    // Handle group select all checkboxes
    document.querySelectorAll('.group-select-all').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const table = this.closest('table');
            const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
            checkboxes.forEach(box => box.checked = this.checked);
        });
    });
    
    // Handle form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('input[name="sizes_to_delete[]"]:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Please select at least one size to delete.');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${checked} selected thumbnail sizes? This cannot be undone.`)) {
                e.preventDefault();
            }
        });
    }
});