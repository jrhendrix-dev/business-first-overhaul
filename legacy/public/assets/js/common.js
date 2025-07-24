/**
 * @file adminUtils.js
 * @description
 * Utility and event handler logic for the admin dashboard.
 * Handles:
 *  - Tab refreshes
 *  - Login form behavior
 *  - Lockout countdown for failed login attempts
 *  - Demo notice dismiss logic
 *
 * @author Jonathan Ray Hendrix
 * @license MIT
 */

//////////////////////////////
// Global Variables
//////////////////////////////

/**
 * Stores the interval ID for the login lockout countdown timer.
 * Used to clear and manage countdown state.
 * @type {number|null}
 */
let lockoutInterval = null;

//////////////////////////////
// Utility Functions
//////////////////////////////

/**
 * Refreshes all dashboard tabs by calling their respective load functions,
 * if those functions are available in the current scope.
 *
 * @function
 * @returns {void}
 */
function refreshAllTabs() {
    if (typeof loadUsers === "function") loadUsers();
    if (typeof loadClasses === "function") loadClasses();
    if (typeof loadTeacherDropdown === "function") loadTeacherDropdown();
    if (typeof loadNotas === "function") loadNotas();
    if (typeof loadHorarios === "function") loadHorarios();
    if (typeof loadFormularioTable === "function") loadFormularioTable();
}

//////////////////////////////
// Login Lockout Logic
//////////////////////////////

/**
 * Starts a countdown to temporarily lock the login form after failed attempts.
 * Disables form inputs and displays remaining time until reactivation.
 *
 * @param {number} seconds - The number of seconds to lock the form.
 * @returns {void}
 */
function startLockoutCountdown(seconds) {
    clearInterval(lockoutInterval);
    let remaining = seconds;
    $('#login_error').text(`Too many failed attempts. Try again in ${remaining} seconds.`);
    $('#login-form :input').prop('disabled', true);

    lockoutInterval = setInterval(function () {
        remaining--;
        if (remaining > 0) {
            $('#login_error').text(`Too many failed attempts. Try again in ${remaining} seconds.`);
        } else {
            clearInterval(lockoutInterval);
            $('#login_error').text('');
            $('#login-form :input').prop('disabled', false);
        }
    }, 1000);
}

//////////////////////////////
// Event Handlers
//////////////////////////////

/**
 * Handles submission of the standard login modal form.
 * Sends AJAX request to login.php and processes JSON response.
 *
 * @param {Event} e - Form submit event
 * @returns {boolean} Always returns false to prevent default form behavior
 */
function handleLoginFormSubmit(e) {
    e.preventDefault();
    const username = $('#username').val();
    const password = $('#password').val();

    $.post('login.php', { username, password }, function (response) {
        if (response.success) {
            location.reload();
        } else {
            if (response.wait) {
                startLockoutCountdown(response.wait);
            } else {
                $('#login_error').text(response.message);
            }
        }
    }, 'json').fail(function () {
        $('#login_error').text("Could not connect to the server.");
    });

    return false;
}

/**
 * Handles submission of the full-screen login form.
 * Redirects to /dashboard on success.
 *
 * @param {Event} e - Form submit event
 * @returns {boolean} Always returns false to prevent default form behavior
 */
function handleLoginScreenFormSubmit(e) {
    e.preventDefault();
    const username = $('#login-screen-user').val();
    const password = $('#login-screen-password').val();

    $.post('login.php', { username, password }, function (response) {
        if (response.success) {
            window.location.href = '/dashboard';
        } else {
            if (response.wait) {
                startLockoutCountdown(response.wait);
            } else {
                $('#login-screen-error').text(response.message);
            }
        }
    }, 'json').fail(function () {
        $('#login-screen-error').text("Could not connect to the server.");
    });

    return false;
}

/**
 * Event triggered when the login modal is hidden.
 * Resets login form and clears any lockout timers.
 *
 * @param {Event} e - Modal hidden event
 * @returns {void}
 */
function handleLoginModalHidden(e) {
    clearInterval(lockoutInterval);
    $('#login_error').text('');
    $('#login-form')[0].reset();
    $('#login-form :input').prop('disabled', false);
}

//////////////////////////////
// Demo Notice Banner Logic
//////////////////////////////

/**
 * Controls visibility and close behavior for the red demo notice banner.
 * Stores dismissal state in localStorage to hide it temporarily (1 hour).
 */
document.addEventListener('DOMContentLoaded', function () {
    const notice = document.getElementById('demo-notice');
    const closeBtn = document.getElementById('close-notice');

    const hideUntil = localStorage.getItem('hideDemoNoticeUntil');
    const now = new Date().getTime();

    if (hideUntil && now < parseInt(hideUntil)) {
        if (notice) notice.style.display = 'none';
        return;
    }

    if (notice && closeBtn) {
        closeBtn.addEventListener('click', () => {
            notice.style.display = 'none';
            const oneHourLater = now + 60 * 60 * 1000;
            localStorage.setItem('hideDemoNoticeUntil', oneHourLater.toString());
        });
    }
});

//////////////////////////////
// DOM Ready Bindings
//////////////////////////////

/**
 * Binds login modal and form events on document ready.
 */
$(document).ready(function () {
    $('#login-modal').on('hidden.bs.modal', handleLoginModalHidden);
    $('#login-form').on('submit', handleLoginFormSubmit);
    $('#login-screen-form').on('submit', handleLoginScreenFormSubmit);
});
