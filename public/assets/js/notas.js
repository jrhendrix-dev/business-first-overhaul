/**
 * @file notas.js
 * @description
 * Handles grade (notas) management for the admin dashboard.
 * Includes:
 * - Loading grades table via AJAX
 * - Inline editing and validation of grades
 * - Saving updated grades
 *
 * @author Jonathan Ray Hendrix
 * @license MIT
 */

//////////////////////////////
// LOAD GRADES
//////////////////////////////

/**
 * Loads the list of student grades from the server
 * and injects the resulting HTML into the DOM.
 *
 * @function
 * @returns {void}
 */
function loadNotas() {
    $.get('/api/admin?loadNotas=1', function (data) {
        $('#notas-table-container').html(data);
    }).fail(function (xhr) {
        console.error('Failed to load grades:', xhr.responseText);
    });
}

//////////////////////////////
// INLINE EDITING
//////////////////////////////

/**
 * Enables inline editing of a grade row.
 * Replaces the grade cells with <input type="number"> fields.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleEditNotaBtnClick(e) {
    const row = $(this).closest('tr');

    row.find('.nota1, .nota2, .nota3').each(function () {
        const value = $(this).text().trim();
        $(this).html(`<input type='number' min='0' max='10' step='0.01' class='form-control' value='${value}' />`);
    });

    row.find('.edit-nota-btn').addClass('d-none');
    row.find('.save-nota-btn, .cancel-nota-btn').removeClass('d-none');
}

/**
 * Cancels the edit operation and reloads all dashboard tabs
 * to restore the original grade table.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleCancelNotaBtnClick(e) {
    refreshAllTabs();
}

/**
 * Saves the updated grades by sending a POST request.
 * Performs validation: values must be null or between 0–10.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleSaveNotaBtnClick(e) {
    const row = $(this).closest('tr');
    const nota1val = row.find('.nota1 input').val();
    const nota2val = row.find('.nota2 input').val();
    const nota3val = row.find('.nota3 input').val();

    const nota1 = nota1val === "" ? null : parseFloat(nota1val);
    const nota2 = nota2val === "" ? null : parseFloat(nota2val);
    const nota3 = nota3val === "" ? null : parseFloat(nota3val);

    // Validate input: must be null or between 0 and 10
    const invalid = [nota1, nota2, nota3].some(n => n !== null && (isNaN(n) || n < 0 || n > 10));
    if (invalid) {
        alert('Grades must be between 0 and 10 or left blank.');
        return;
    }

    $.post('/api/admin', {
        updateNota: 1,
        idAlumno: row.data('id'),
        nota1,
        nota2,
        nota3
    }).done(function (response) {
        console.log('Server response on saving grade:', response);
        if (response.trim() === 'success') {
            refreshAllTabs();
        } else if (response.includes('fuera de rango')) {
            alert('Grades must be between 0 and 10.');
        } else {
            alert('Failed to save grades.');
        }
    }).fail(function (xhr) {
        console.error('Error saving grades:', xhr.responseText);
        alert('Failed to save grades.');
    });
}

//////////////////////////////
// INITIALIZATION
//////////////////////////////

/**
 * Initializes the grade module:
 * - Loads the grades table
 * - Binds event listeners for edit, cancel, and save buttons
 *
 * @function
 * @returns {void}
 */
function initializeNotasModule() {
    loadNotas();

    $(document).on('click', '.edit-nota-btn', handleEditNotaBtnClick);
    $(document).on('click', '.cancel-nota-btn', handleCancelNotaBtnClick);
    $(document).on('click', '.save-nota-btn', handleSaveNotaBtnClick);
}

/**
 * Binds all event handlers and initializes the UI once the DOM is ready.
 */
$(document).ready(function () {
    initializeNotasModule();
});
