/**
 * @file student_dash.js
 * @description
 * Handles the student dashboard's display logic.
 * Loads grades and class schedule in read-only mode via AJAX.
 * Requires jQuery and Bootstrap.
 *
 * Author: Jonathan Ray Hendrix
 * License: MIT
 */

//////////////////////////////
// DOM READY INITIALIZATION
//////////////////////////////

/**
 * Binds tab navigation and initializes grades view on page load.
 */
$(document).ready(function () {
    initializeNotasModule();

    // Handle Bootstrap tab switching to trigger AJAX loads
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#students') {
            loadStudentGradesTable();
        } else if (target === '#schedule') {
            loadStudentScheduleTable();
        }
    });
});

//////////////////////////////
// MODULE INITIALIZER
//////////////////////////////

/**
 * Initializes the student's grades module by loading grades on initial view.
 *
 * @function
 * @returns {void}
 */
function initializeNotasModule() {
    loadStudentGradesTable();
}

//////////////////////////////
// LOAD GRADES
//////////////////////////////

/**
 * Loads the current student's grades from the server.
 * Injects the result into the #student-grades-table-container.
 * Displays an error message on failure.
 *
 * @function
 * @returns {void}
 */
function loadStudentGradesTable() {
    $.get('/api/student?action=getStudentGrades', function (data) {
        $('#student-grades-table-container').html(data);
    }).fail(function (xhr) {
        console.error('Failed to load grades:', xhr.responseText);
        $('#student-grades-table-container').html(
            `<div class="alert alert-danger">
                Server error: ${xhr.status}<br>${xhr.responseText}
             </div>`
        );
    });
}

//////////////////////////////
// LOAD SCHEDULE
//////////////////////////////

/**
 * Loads the current student's class schedule from the server.
 * Injects the result into the #student-schedule-table-container.
 * Displays a fallback error message on failure.
 *
 * @function
 * @returns {void}
 */
function loadStudentScheduleTable() {
    $.get('/api/student?action=getClassSchedule', function (html) {
        $('#student-schedule-table-container').html(html);
    }).fail(function (xhr) {
        console.error('Failed to load schedule:', xhr.responseText);
        $('#student-schedule-table-container').html(
            '<div class="alert alert-danger">Error loading schedule.</div>'
        );
    });
}
