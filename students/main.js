
let printExecuted = false;
function printDiv(tt1) {
// Avoid re-running the print function after it has already been executed
if (printExecuted) return;

var printContents = document.getElementById(tt1).innerHTML;
var originalContents = document.body.innerHTML;

document.body.innerHTML = printContents;

window.print();

// Restore original contents after printing
document.body.innerHTML = originalContents;

printExecuted = true; // Flag to indicate print has been executed

// Reload the page after printing (optional, based on your needs)
setTimeout(function() {
location.reload();
}, 1000); // Delay the reload to allow printing to complete
}

function reloadAndPrint(tt1) {
// Store the divId in sessionStorage before the reload
sessionStorage.setItem('printDivId', tt1);

// Reload the page first
location.reload();
}

window.onload = function() {
// Check if we have the divId stored in sessionStorage
if (sessionStorage.getItem('printDivId')) {
var divId = sessionStorage.getItem('printDivId');

// Call the print function after reload
printDiv(divId);

// Clear sessionStorage after printing
sessionStorage.removeItem('printDivId');
}
}

$(document).ready(function() {
    // Real-time username check
    $('#username').on('input', function() {
        var username = $(this).val();
        
        if(username.length > 5) { // Only check after 3 characters
            $.get(window.location.href, {
                check_username: 1,
                username: username
            }, function(data) {
                $('#usernameStatus').html(data);
            });
        } else {
            $('#usernameStatus').html('');
        }
    });
});

    $(document).ready(function() {
// Validate form before submission
// $('#form1').on('submit', function(e) {
//     var username = $('#username').val();
//     var password = $('#password').val();
    
//     if (username.length >6) {
//         alert('Username must be at least 6 characters long.');
//         e.preventDefault();
//         return false;
//     }
    
//     if (password.length < 6) {
//         alert('Password must be at least 6 characters long.');
//         e.preventDefault();
//         return false;
//     }
    
//     return true;
// });

// Real-time validation feedback
$('#username').on('input', function() {
    if ($(this).val().length < 6) {
        $(this).addClass('is-invalid').removeClass('is-valid');
    } else {
        $(this).addClass('is-valid').removeClass('is-invalid');
    }
});

$('#password').on('input', function() {
    if ($(this).val().length < 6) {
        $(this).addClass('is-invalid').removeClass('is-valid');
    } else {
        $(this).addClass('is-valid').removeClass('is-invalid');
    }
});
});
        // Toggle form visibility
        document.getElementById('toggleFormBtn').addEventListener('click', function() {
            const form = document.getElementById('studentForm');
            const collapse = new bootstrap.Collapse(form, {
                toggle: true
            });
            
            // Change button text based on form visibility
            if (form.classList.contains('show')) {
                this.innerHTML = '<i class="bi bi-person-plus"></i> Add New Student';
            } else {
                this.innerHTML = '<i class="bi bi-x"></i> Close Form';
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        
        // Zoom adjustment for very small devices
        function adjustZoom() {
            if (window.innerWidth <= 400) {
                document.documentElement.style.zoom = "0.9";
            } else {
                document.documentElement.style.zoom = "1";
            }
        }
        
        window.addEventListener('resize', adjustZoom);
        adjustZoom(); // Run on initial load
