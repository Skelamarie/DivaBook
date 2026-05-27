/* admin_customization.js */

/*for live color prev.*/
(function () {
    const root = document.documentElement;

    function isValidHex(h) {
        return /^#[0-9A-Fa-f]{6}$/.test(h);
    }

    // Convert hex to rgb components string "r, g, b"
    function hexToRgb(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `${r}, ${g}, ${b}`;
    }

    // Lighten a hex color by mixing with white at given ratio (0-1)
    function tint(hex, ratio) {
        const r = Math.round(parseInt(hex.slice(1, 3), 16) + (255 - parseInt(hex.slice(1, 3), 16)) * ratio);
        const g = Math.round(parseInt(hex.slice(3, 5), 16) + (255 - parseInt(hex.slice(3, 5), 16)) * ratio);
        const b = Math.round(parseInt(hex.slice(5, 7), 16) + (255 - parseInt(hex.slice(5, 7), 16)) * ratio);
        return `rgb(${r},${g},${b})`;
    }

    function applyColors(primary, secondary) {
        // CSS variables
        root.style.setProperty('--primary-container', primary);
        root.style.setProperty('--on-primary-container', secondary);
        root.style.setProperty('--secondary-container', tint(primary, 0.85)); 

        const rgb = hexToRgb(primary);
        const pale = tint(primary, 0.88); // very light tint for bg
        const light = tint(primary, 0.75); // medium tint for borders/badges
        const mid = tint(primary, 0.55); // stronger tint

        // Color preview bar
        const bar = document.getElementById('colorPreviewBar');
        if (bar) bar.style.background = `linear-gradient(90deg, ${primary} 50%, ${secondary} 50%)`;

        // Branding panel 
        const panel = document.getElementById('brandingPanel');
        if (panel) {
            panel.style.borderColor = light;
            panel.style.backgroundColor = pale;
        }

        // ALL section headings (h4 with primary color)
        document.querySelectorAll('h4[style*="color"]').forEach(el => el.style.color = primary);

        // Custom card wrapper
        document.querySelectorAll('.custom-card').forEach(el => {
            el.style.borderColor = light;
        });

        // Navbar brand
        document.querySelectorAll('.navbar-brand').forEach(el => el.style.color = primary);

        // Active nav links
        document.querySelectorAll('.nav-link.active').forEach(el => {
            el.style.color = primary;
            el.style.borderBottomColor = primary;
        });

        // All submit / primary button
        document.querySelectorAll('button[type=submit], .btn-success').forEach(btn => {
            btn.style.backgroundColor = primary;
            btn.style.borderColor = primary;
            btn.style.boxShadow = `0 4px 10px rgba(${rgb}, 0.3)`;
        });

        // for "Add New Service" button ni
        document.querySelectorAll('[data-bs-target="#serviceModal"].btn').forEach(btn => {
            btn.style.backgroundColor = primary;
            btn.style.borderColor = primary;
        });

        // Color rows border on focus
        document.querySelectorAll('.color-row').forEach(el => {
            el.style.backgroundColor = pale;
            el.style.borderColor = light;
        });

        // Form controls (focus ang glow color via CSS var)
        const focusStyle = document.getElementById('_dynamicFocusStyle') || (() => {
            const s = document.createElement('style');
            s.id = '_dynamicFocusStyle';
            document.head.appendChild(s);
            return s;
        })();
        focusStyle.textContent = `
            .form-control:focus, .form-select:focus {
                border-color: ${primary} !important;
                box-shadow: 0 0 0 0.2rem rgba(${rgb}, 0.18) !important;
            }
            .hex-input:focus {
                border-color: ${primary} !important;
                box-shadow: 0 0 0 3px rgba(${rgb}, 0.15) !important;
            }
            .service-card .badge-primary-theme {
                background-color: ${pale} !important;
                color: ${primary} !important;
                border-color: ${light} !important;
            }
            .service-card .btn-edit-theme {
                background-color: ${pale} !important;
                color: ${primary} !important;
                border-color: ${light} !important;
            }
            .service-card .btn-edit-theme:hover {
                background-color: ${light} !important;
            }
            .offcanvas-title { color: ${primary} !important; }
            .list-group-item.active, .list-group-item[style*="primary"] { color: ${primary} !important; }
            .alert-success { background-color: ${pale} !important; color: ${primary} !important; border-left: 4px solid ${primary} !important; }
            .logo-upload-area { border-color: ${light} !important; }
            #brandingPanel h4 { color: ${primary} !important; }
            .basic-info-heading { color: ${primary} !important; }
        `;

        // for "Basic Information" heading
        document.querySelectorAll('.basic-info-heading').forEach(el => el.style.color = primary);

        // Service card image placeholder bg
        document.querySelectorAll('.service-card .card-body').forEach(el => {
            el.style.borderTop = `3px solid ${light}`;
        });

        // Empty state icon circle
        document.querySelectorAll('.text-center .d-inline-flex.rounded-circle').forEach(el => {
            el.style.borderColor = light;
            el.style.backgroundColor = pale;
        });
    }

    function syncPair(colorInput, hexInput, cssVar) {
        // Color picker to hex field
        colorInput.addEventListener('input', () => {
            const val = colorInput.value;
            hexInput.value = val.toUpperCase();
            const pri = document.getElementById('colorPrimary').value;
            const sec = document.getElementById('colorSecondary').value;
            applyColors(pri, sec);
        });

        // Hex field to color picker
        hexInput.addEventListener('input', () => {
            let val = hexInput.value.trim();
            if (!val.startsWith('#')) val = '#' + val;
            if (isValidHex(val)) {
                colorInput.value = val;
                hexInput.value = val.toUpperCase();
                const pri = document.getElementById('colorPrimary').value;
                const sec = document.getElementById('colorSecondary').value;
                applyColors(pri, sec);
            }
        });

        // Normalise hex on blur
        hexInput.addEventListener('blur', () => {
            let val = hexInput.value.trim();
            if (!val.startsWith('#')) val = '#' + val;
            if (!isValidHex(val)) hexInput.value = colorInput.value.toUpperCase();
            else hexInput.value = val.toUpperCase();
        });
    }

    // Initialize if elements exist
    const cp = document.getElementById('colorPrimary');
    const hp = document.getElementById('hexPrimary');
    const cs = document.getElementById('colorSecondary');
    const hs = document.getElementById('hexSecondary');

    if (cp && hp) syncPair(cp, hp, '--primary-container');
    if (cs && hs) syncPair(cs, hs, '--on-primary-container');

    // Apply on page load to reflect saved colours
    if (cp && cs) {
        applyColors(cp.value, cs.value);
    }
})();

/* for Service Modal */

// handles instant preview when you pick a file
function previewMultipleImages(input, index) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {

            // finds specific preview img and placeholder div for this index
            const preview = document.getElementById(`photoPreview${index}`);
            const placeholder = document.getElementById(`photoPlaceholder${index}`);
            
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('d-none'); // pakita ang image
            }
            if (placeholder) {
                placeholder.classList.add('d-none'); // hides icon
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function prepareServiceModal(mode, data = null) {
    const label = document.getElementById('serviceModalLabel');
    const action = document.getElementById('modalAction');
    const id = document.getElementById('modalServiceId');
    const name = document.getElementById('modalServiceName');
    const category = document.getElementById('modalCategory');
    const rate = document.getElementById('modalRate');
    const desc = document.getElementById('modalDescription');
    const existingPhotosInput = document.getElementById('modalExistingPhotos');

    // Reset text fields
    id.value = '';
    name.value = '';
    category.value = '';
    rate.value = '';
    desc.value = '';

    // Refresh/clear all 3 preview slots AND the file inputs
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`photoPreview${i}`);
        const placeholder = document.getElementById(`photoPlaceholder${i}`);
        const fileInput = document.querySelector(`input[name="service_photo_${i}"]`);
        
        if (preview) {
            preview.src = "";
            preview.classList.add('d-none');
        }
        if (placeholder) {
            placeholder.classList.remove('d-none');
        }
        
        // clears the actual file selection so the "first" input is fresh
        if (fileInput) {
            fileInput.value = ""; 
        }
    }

    if (mode === 'add') {
        label.textContent = 'Add New Service';
        action.value = 'add_service';
        existingPhotosInput.value = "[]";
    } else {
        label.textContent = 'Edit Service';
        action.value = 'edit_service';
        
        // Fill data
        id.value = data.id || '';
        name.value = data.name || '';
        category.value = data.category || '';
        rate.value = data.rate || '';
        desc.value = data.description || '';

        // handle image previews from db
        if (data.photos && Array.isArray(data.photos)) {
            existingPhotosInput.value = JSON.stringify(data.photos);
            data.photos.forEach((url, index) => {
                const i = index + 1;
                const preview = document.getElementById(`photoPreview${i}`);
                const placeholder = document.getElementById(`photoPlaceholder${i}`);
                
                if (url && preview) {
                    preview.src = url;
                    preview.classList.remove('d-none');
                    if (placeholder) placeholder.classList.add('d-none');
                }
            });
        } else {
            existingPhotosInput.value = "[]";
        }
    }
}
/*for account deletion*/
document.addEventListener('DOMContentLoaded', function() {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteAccountForm = document.getElementById('deleteAccountForm');

    if (confirmDeleteBtn && deleteAccountForm) {
        confirmDeleteBtn.addEventListener('click', function() {

            // changes button state to show processing
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
            
            // magsubmit sa actual form
            deleteAccountForm.submit();
        });
    }
});

/* combined DOM Content Loaded Handler */
document.addEventListener('DOMContentLoaded', function() {

    //SELECT LOADING ELEMENTS
    const loader = document.getElementById('loading-overlay');
    const loadingText = document.getElementById('dynamic-loading-text');

    //FORM SUBMISSION WATCHER (Triggers Loading Overlay)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const action = form.querySelector('[name="action"]')?.value;
            
            // Set dynamic message based on action
            if (action === 'save_salon_profile') {
                loadingText.textContent = "Updating Salon Profile...";
            } else if (action === 'add_service' || action === 'edit_service') {
                loadingText.textContent = "Processing Service Catalog...";
            } else if (action === 'delete_full_account') {
                loadingText.textContent = "Permanently Deleting Data...";
            } else if (action === 'delete_service') {
                loadingText.textContent = "Removing Service...";
            }

            loader.classList.add('active');
        });
    });

    // EXISTING STATUS MODAL LOGIC
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status) {
        const successModal = new bootstrap.Modal(document.getElementById('saveSuccessModal'));
        const titleEl = document.getElementById('successModalTitle');
        const msgEl = document.getElementById('successModalMessage');

        if (status === 'service_added') {
            titleEl.textContent = "Service Added!";
            msgEl.textContent = "You have successfully added a new service to your catalog.";
            successModal.show();
        } 
        // status logic
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // EXISTING ACCOUNT DELETION HANDLER
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteAccountForm = document.getElementById('deleteAccountForm');

    if (confirmDeleteBtn && deleteAccountForm) {
        confirmDeleteBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

            deleteAccountForm.submit();
        });
    }
}); 

/**
 * Prepares the service deletion modal with specific service data
 * @param {string} id - MongoDB ID of the service
 * @param {string} name - name of the service for display
 */
function prepareDeleteModal(id, name) {
    document.getElementById('deleteServiceIdInput').value = id;
    document.getElementById('deleteServiceNameText').textContent = name;
}
