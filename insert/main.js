function calculateRow(row) {
    // Semester 1
    const mid1 = parseFloat(row.querySelector('.semester1[name="mid1[]"]').value) || 0;
    const assignment1 = parseFloat(row.querySelector('.semester1[name="assignment1[]"]').value) || 0;
    const final1 = parseFloat(row.querySelector('.semester1[name="final1[]"]').value) || 0;
    const total1 = mid1 + assignment1 + final1;
    row.querySelector('.total1').value = total1.toFixed(2);
    
    // Semester 2
    const mid2 = parseFloat(row.querySelector('.semester2[name="mid2[]"]').value) || 0;
    const assignment2 = parseFloat(row.querySelector('.semester2[name="assignment2[]"]').value) || 0;
    const final2 = parseFloat(row.querySelector('.semester2[name="final2[]"]').value) || 0;
    const total2 = mid2 + assignment2 + final2;
    row.querySelector('.total2').value = total2.toFixed(2);
}

// Auto-calculate when inputs change
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('editable')) {
        const row = e.target.closest('tr');
        calculateRow(row);
        
        // Validate individual field
        validateField(e.target);
    }
});

function validateField(input) {
    const value = parseFloat(input.value);
    const max = parseFloat(input.getAttribute('max'));
    
    if (isNaN(value)) {
        input.classList.add('is-invalid');
        return false;
    }
    
    if (value < 0 || value > max) {
        input.classList.add('is-invalid');
        return false;
    }
    
    input.classList.remove('is-invalid');
    return true;
}

function validateAllMarks() {
    let allValid = true;
    const inputs = document.querySelectorAll('.editable');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            allValid = false;
        }
    });
    
    if (allValid) {
        alert('All marks are valid and within acceptable ranges!');
    } else {
        alert('Some marks are invalid. Please check highlighted fields.');
    }
    
    return allValid;
}

function validateForm() {
    if (!validateAllMarks()) {
        return false;
    }
    
    // Calculate all totals before submission
    const rows = document.querySelectorAll('.marks-entry-table tbody tr');
    rows.forEach(row => {
        calculateRow(row);
    });
    
    return true;
}
