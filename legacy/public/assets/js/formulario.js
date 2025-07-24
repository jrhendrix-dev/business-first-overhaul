/**
 * @file formulario.js
 * @description
 * Manages loading and deletion of contact form submissions
 * for the admin dashboard interface. Uses AJAX to fetch
 * the latest data and to delete entries without page reload.
 *
 * Functions:
 *  - loadFormularioTable()
 *  - handleDeleteFormTabBtnClick()
 *
 * Events:
 *  - $(document).ready → loads form table
 *  - click on `.delete-formtab-btn` → triggers deletion
 *
 * @author Jonathan Ray Hendrix
 * @license MIT
 */

//////////////////////////////
// Initialization
//////////////////////////////

/**
 * On DOM ready, load the contact form submissions table.
 */
$(document).ready(function () {
    loadFormularioTable();
});

//////////////////////////////
// Functions
//////////////////////////////

/**
 * Loads the contact form submissions table from the server
 * and injects the resulting HTML into the DOM container.
 *
 * @function
 * @returns {void}
 */
function loadFormularioTable() {
    $.get('/api/admin?action=getFormulario', function (html) {
        $('#formulario-table-container').html(html);
    }).fail(function (xhr) {
        $('#formulario-table-container').html(
            `<div class="alert alert-danger">Failed to load form data: ${xhr.statusText}</div>`
        );
    });
}

/**
 * Handles the deletion of a contact form submission.
 * Prompts for confirmation, then sends an AJAX POST request.
 * If successful, refreshes all dashboard data.
 *
 * @function
 * @param {Event} e - Click event from delete button
 * @returns {void}
 */
function handleDeleteFormTabBtnClick(e) {
    const row = $(this).closest('tr');
    const idformulario = row.data('id');

    if (confirm('Are you sure you want to delete this message?')) {
        $.post('/api/admin', {
            deleteForm: 1,
            idformulario: idformulario
        }).done(function (response) {
            console.log('Server response to form delete:', response);
            if (response.trim() === 'success') {
                refreshAllTabs();
            } else {
                console.error('Unexpected response while deleting form:', response);
                alert('Failed to delete the message.');
            }
        }).fail(function (xhr) {
            console.error('AJAX error while deleting form:', xhr.responseText);
            alert('Failed to delete the message.');
        });
    }
}

//////////////////////////////
// Event Binding
//////////////////////////////

/**
 * Binds the delete event handler to all `.delete-formtab-btn` buttons.
 * Uses event delegation to account for dynamic content.
 */
$(document).on('click', '.delete-formtab-btn', handleDeleteFormTabBtnClick);
