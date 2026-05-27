// Wait for the page to fully load before running the code
document.addEventListener('DOMContentLoaded', () => {
    console.log("DivaBook script initialized.");

    // Controls opening and closing the mobile menu on small screens
    const hamburger = document.querySelector('.hamburger-menu');
    const sidebar = document.querySelector('.mobile-sidebar');
    const closeBtn = document.querySelector('.close-sidebar');

    if (hamburger && sidebar && closeBtn) {
        // Open the menu when you click the hamburger button
        hamburger.addEventListener('click', () => {
            sidebar.classList.add('open');
        });

        // Close the menu when you click the close button
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('open');
        });
    }
});

// Opens the large pop-up picture when you click on a small image
function openModal(imgElement) {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("zoomedImg");
    
    if (modal && modalImg) {
        modalImg.src = imgElement.src;
        modal.style.display = "flex"; 
        
        // This stops the background page from scrolling while you look at the picture
        document.body.style.overflow = "hidden"; 
    }
}

// Closes the pop-up picture
function closeModal() {
    const modal = document.getElementById("imageModal");
    if (modal) {
        modal.style.display = "none";
        
        // This turns scrolling back on
        document.body.style.overflow = "auto"; 
    }
}