//
// Navigation functions.
//
function toggleNav() {
    var nav = document.querySelector('nav');
    if(nav.hasAttribute('closed')) {
        nav.removeAttribute('closed');
    }
    else {
        nav.setAttribute('closed', '');
    }
}

// On ready function.
document.addEventListener('DOMContentLoaded', function() {
    var brand = document.querySelector('nav > .brand');
    brand.addEventListener('click', toggleNav);
});