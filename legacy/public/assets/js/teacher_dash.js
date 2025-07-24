/**
 * @file teacher_dash.js
 * @description
 * Handles student grade and schedule management on the teacher dashboard.
 * Enables grade editing via AJAX and dynamically loads schedule data.
 * Requires jQuery and Bootstrap.
 *
 * Author: Jonathan Ray Hendrix
 * License: MIT
 */

//////////////////////////////
// DOM READY INITIALIZATION
//////////////////////////////

/**
 * Initializes the grades module and binds tab switch handlers.
 */
$(document).ready(function () {
    initializeNotasModule();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#students') {
            loadTeacherStudentsTable();
        } else if (target === '#schedule') {
            loadTeacherScheduleTable();
        }
    });
});

//////////////////////////////
// MODULE INITIALIZER
//////////////////////////////

/**
 * Initializes grade management:
 * Loads students table and binds edit/save/cancel events.
 *
 * @function
 * @returns {void}
 */
function initializeNotasModule() {
    loadTeacherStudentsTable();

    $(document).on('click', '.edit-nota-btn', handleEditNotaBtnClick);
    $(document).on('click', '.cancel-nota-btn', handleCancelNotaBtnClick);
    $(document).on('click', '.save-nota-btn', handleSaveNotaBtnClick);
}

//////////////////////////////
// LOAD STUDENTS & GRADES
//////////////////////////////

/**
 * Loads the teacher's student and grades table via AJAX.
 * Displays error on failure.
 *
 * @function
 * @returns {void}
 */
function loadTeacherStudentsTable() {
    $.get('api/teacher?action=getStudentsAndGrades', function (data) {
        $('#teacher-students-table-container').html(data);
    }).fail(function (xhr) {
        console.error('Failed to load students:', xhr.responseText);
        $('#teacher-students-table-container').html(
            `<div class="alert alert-danger">
                Server error: ${xhr.status}<br>${xhr.responseText}
             </div>`
        );
    });
}

//////////////////////////////
// LOAD CLASS SCHEDULE
//////////////////////////////

/**
 * Loads the teacher's class schedule via AJAX.
 * Displays error on failure.
 *
 * @function
 * @returns {void}
 */
function loadTeacherScheduleTable() {
    $.get('api/teacher?action=getClassSchedule', function (html) {
        $('#teacher-schedule-table-container').html(html);
    }).fail(function (xhr) {
        console.error('Failed to load schedule:', xhr.responseText);
        $('#teacher-schedule-table-container').html(
            '<div class="alert alert-danger">Failed to load schedule.</div>'
        );
    });
}

//////////////////////////////
// GRADE EDITING HANDLERS
//////////////////////////////

/**
 * Enables inline editing for a student's grades.
 * Converts grade cells to input fields and toggles buttons.
 *
 * @function
 * @returns {void}
 */
function handleEditNotaBtnClick() {
    const row = $(this).closest('tr');
    row.find('.nota1, .nota2, .nota3').each(function () {
        const value = $(this).text().trim();
        $(this).html(
            `<input type='number' min='0' max='10' step='0.01' class='form-control' value='${value}' />`
        );
    });

    row.find('.edit-nota-btn').addClass('d-none');
    row.find('.save-nota-btn').removeClass('d-none');
    row.find('.cancel-nota-btn').removeClass('d-none');
}

/**
 * Cancels any ongoing grade edits and reloads the table.
 *
 * @function
 * @returns {void}
 */
function handleCancelNotaBtnClick() {
    loadTeacherStudentsTable();
}

/**
 * Validates and submits updated grades via AJAX.
 * Reloads the table if successful, otherwise shows error.
 *
 * @function
 * @returns {void}
 */
function handleSaveNotaBtnClick() {
    const row = $(this).closest('tr');
    const nota1val = row.find('.nota1 input').val();
    const nota2val = row.find('.nota2 input').val();
    const nota3val = row.find('.nota3 input').val();

    const nota1 = nota1val === "" ? null : parseFloat(nota1val);
    const nota2 = nota2val === "" ? null : parseFloat(nota2val);
    const nota3 = nota3val === "" ? null : parseFloat(nota3val);

    if ([nota1, nota2, nota3].some(n => n !== null && (isNaN(n) || n < 0 || n > 10))) {
        alert('Grades must be between 0 and 10 or left empty.');
        return;
    }

    $.post('/api/teacher?', {
        updateNota: 1,
        idAlumno: row.data('id'),
        nota1: nota1,
        nota2: nota2,
        nota3: nota3
    }).done(function (response) {
        console.log('Grade update response:', response);
        if (response.trim() === 'success') {
            loadTeacherStudentsTable();
        } else {
            alert('Failed to save grades: ' + response);
        }
    }).fail(function (xhr) {
        console.error('AJAX error while saving grades:', xhr.responseText);
        alert('Failed to save grades.');
    });
}
