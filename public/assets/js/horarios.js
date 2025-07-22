/**
 * @file horarios.js
 * @description
 * Admin dashboard module for managing weekly class schedules.
 * Handles:
 *  - Loading schedule table via AJAX
 *  - Inline editing of schedule rows
 *  - Saving or cancelling edits
 *
 * @author Jonathan Ray Hendrix
 * @license MIT
 */

//////////////////////////////
// LOAD DATA
//////////////////////////////

/**
 * Loads the list of class schedules from the server
 * and injects the resulting HTML into the DOM.
 *
 * @function
 * @returns {void}
 */
function loadHorarios() {
    $.get('/api/admin?loadHorarios=1', function (data) {
        $('#horarios-table-container').html(data);
    }).fail(function (xhr) {
        console.error('Failed to load schedule:', xhr.responseText);
    });
}

//////////////////////////////
// INLINE EDITING
//////////////////////////////

/**
 * Enables inline editing of a schedule row.
 * Replaces class name cells with <select> dropdowns.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleEditHorarioBtnClick(e) {
    const row = $(this).closest('tr');

    ['firstclass', 'secondclass', 'thirdclass'].forEach(col => {
        const originalText = row.find('.' + col).text().trim();
        const select = $('<select class="form-control"></select>').append(window.classOptions);

        // Try to match option by visible text
        select.find('option').filter(function () {
            return $(this).text().trim() === originalText;
        }).prop('selected', true);

        row.find('.' + col).html(select);
    });

    row.find('.edit-horario-btn').addClass('d-none');
    row.find('.save-horario-btn, .cancel-horario-btn').removeClass('d-none');
}

/**
 * Cancels editing and reloads the schedule table to reset state.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleCancelHorarioBtnClick(e) {
    refreshAllTabs();
}

/**
 * Saves the updated schedule data to the server.
 * Sends AJAX POST request with selected class IDs.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleSaveHorarioBtnClick(e) {
    const row = $(this).closest('tr');
    const day_id = row.data('id');
    const firstclass = row.find('.firstclass select').val();
    const secondclass = row.find('.secondclass select').val();
    const thirdclass = row.find('.thirdclass select').val();

    $.post('/api/admin', {
        updateHorario: 1,
        day_id,
        firstclass,
        secondclass,
        thirdclass
    }).done(function (response) {
        if (response.trim() === 'success') {
            refreshAllTabs();
        } else {
            alert('Failed to save schedule.');
        }
    }).fail(function (xhr) {
        console.error('Error saving schedule:', xhr.responseText);
        alert('Failed to save schedule.');
    });
}

//////////////////////////////
// INITIALIZATION
//////////////////////////////

/**
 * Initializes the schedule module:
 * - Loads the schedule table
 * - Binds event listeners to edit, save, and cancel buttons
 *
 * @function
 * @returns {void}
 */
function initializeHorariosModule() {
    loadHorarios();

    $(document).on('click', '.edit-horario-btn', handleEditHorarioBtnClick);
    $(document).on('click', '.cancel-horario-btn', handleCancelHorarioBtnClick);
    $(document).on('click', '.save-horario-btn', handleSaveHorarioBtnClick);
}

/**
 * Binds all events and initializes the UI once the DOM is ready.
 */
$(document).ready(function () {
    initializeHorariosModule();
});
