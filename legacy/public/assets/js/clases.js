/**
 * @file clases.js
 * @description
 * Manages class-related CRUD operations in the admin dashboard.
 * Handles:
 *  - Loading class list
 *  - Creating, updating, and deleting classes via AJAX
 *  - Dynamic dropdowns for available teachers
 *  - Refreshing dropdowns in related forms
 *
 * @author Jonathan Ray Hendrix
 * @license MIT
 */

/// ======================= LOAD & UI INIT ===========================

/**
 * Loads the list of classes from the server and injects it into the class table container.
 *
 * @function
 * @returns {void}
 */
function loadClasses() {
    $.get('/api/admin?loadClasses=1', function (data) {
        $('#class-table-container').html(data);
    }).fail(function (xhr) {
        console.error('Failed to load classes:', xhr.responseText);
    });
}

/**
 * Fetches a list of available teachers for the class form.
 * Builds a <select> dropdown with options.
 *
 * @function
 * @param {?number|string} [selectedId=null] - Teacher ID to preselect.
 * @returns {Promise<jQuery>} A promise resolving to a <select> element.
 */
function fetchAvailableTeachers(selectedId = null) {
    return $.get('/api/admin?availableTeachers=1').then(function (optionsHtml) {
        const select = $('<select class="form-control" name="profesor"></select>')
            .append(`<option value="">-- Unassigned --</option>`, optionsHtml);
        if (selectedId) {
            select.find(`option[value='${selectedId}']`).prop('selected', true);
        }
        return select;
    }).fail(function (xhr) {
        console.error('Failed to fetch available teachers:', xhr.responseText);
    });
}

/**
 * Injects the teacher dropdown into the class creation form.
 *
 * @function
 * @returns {void}
 */
function loadTeacherDropdown() {
    fetchAvailableTeachers().then(function (select) {
        $('#class-create-form select[name="profesor"]').replaceWith(select);
    });
}

/**
 * Updates class dropdown and resets user level in the user creation form.
 *
 * @function
 * @returns {void}
 */
function refreshUserFormDropdowns() {
    if (typeof fetchAvailableClasses === "function") {
        fetchAvailableClasses().then(function (select) {
            $('#user-create-form select[name="class"]').replaceWith(select);
            const $ulevel = $('#user-create-form select[name="ulevel"]');
            if ($ulevel.length) {
                $ulevel.prop('selectedIndex', 0);
            }
        });
    }
}

/// ======================= CLASS CREATION ===========================

/**
 * Handles submission of the class creation form.
 * Sends class data via AJAX to the server.
 *
 * @param {Event} e - Submit event
 * @returns {void}
 */
function handleClassCreateFormSubmit(e) {
    e.preventDefault();
    const classname = $(this).find('[name="classname"]').val();
    const profesor = $(this).find('[name="profesor"]').val();

    $.post('/api/admin', {
        createClass: 1,
        classname: classname,
        profesor: profesor || ''
    }).done(function (response) {
        console.log('Server response on class create:', response);
        response = response.trim();
        if (response === 'success') {
            $('#create-class-feedback')
                .removeClass('text-danger')
                .addClass('text-success')
                .text('Class created successfully.');
            $('#class-create-form')[0].reset();
            refreshAllTabs();
            loadTeacherDropdown();
            refreshUserFormDropdowns();
        } else {
            console.error('Unexpected server response:', response);
            $('#create-class-feedback')
                .removeClass('text-success')
                .addClass('text-danger')
                .text('Error creating class.');
        }
    }).fail(function (xhr) {
        console.error('AJAX error on class creation:', xhr.responseText);
        $('#create-class-feedback')
            .removeClass('text-success')
            .addClass('text-danger')
            .text('Error creating class.');
    });
}

/// ======================= EDIT CLASS ===========================

/**
 * Enables inline editing of a class row.
 * Replaces content with input fields and dropdowns.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
async function handleEditClassBtnClick(e) {
    const row = $(this).closest('tr');
    const classid = row.data('id');

    const classnameVal = row.find('.classname').text().trim();
    row.find('.classname').html(`<input type='text' class='form-control' value='${classnameVal}' />`);

    const profesorId = row.find('.profesor').data('profid');
    const select = await fetchAvailableTeachers(profesorId);
    row.find('.profesor').html(select);

    row.find('.edit-class-btn').replaceWith('<button class="btn btn-sm btn-success save-class-btn">Save</button>');
    row.find('.delete-class-btn').replaceWith('<button class="btn btn-sm btn-secondary cancel-class-btn">Cancel</button>');
}

/**
 * Cancels edit mode and reloads the class list.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleCancelClassBtnClick(e) {
    refreshAllTabs();
}

/**
 * Saves the edited class data via AJAX.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleSaveClassBtnClick(e) {
    const row = $(this).closest('tr');
    const classid = row.data('id');
    const classname = row.find('.classname input').val();
    const profesor = row.find('.profesor select').val();

    $.post('/api/admin', {
        updateClass: 1,
        classid: classid,
        classname: classname,
        profesor: profesor || ''
    }).done(function (response) {
        console.log('Server response on class update:', response);
        if (response.trim() === 'success') {
            refreshAllTabs();
            loadTeacherDropdown();
            refreshUserFormDropdowns();
        } else {
            console.error('Unexpected server response on update:', response);
            alert('Error updating class.');
        }
    }).fail(function (xhr) {
        console.error('AJAX error updating class:', xhr.responseText);
        alert('Error updating class.');
    });
}

/// ======================= DELETE CLASS ===========================

/**
 * Confirms and deletes a class via AJAX.
 *
 * @param {Event} e - Click event
 * @returns {void}
 */
function handleDeleteClassBtnClick(e) {
    const row = $(this).closest('tr');
    const classid = row.data('id');

    if (confirm('Are you sure you want to delete this class?')) {
        $.post('/api/admin', {
            deleteClass: 1,
            classid: classid
        }).done(function (response) {
            console.log('Server response on delete:', response);
            if (response.trim() === 'success') {
                refreshAllTabs();
                loadTeacherDropdown();
            } else {
                console.error('Unexpected server response on delete:', response);
                alert('Error deleting class.');
            }
        }).fail(function (xhr) {
            console.error('AJAX error deleting class:', xhr.responseText);
            alert('Error deleting class.');
        });
    }
}

/// ======================= FORM TOGGLE ===========================

/**
 * Toggles the visibility of the class creation form and button text.
 * Adds +/− indicator to the toggle button.
 */
document.addEventListener("DOMContentLoaded", function () {
    const toggleClassBtn = document.getElementById("toggleClassForm");
    const classFormContainer = document.getElementById("classFormContainer");

    if (toggleClassBtn && classFormContainer) {
        toggleClassBtn.addEventListener("click", function () {
            classFormContainer.classList.toggle("show");
            const isVisible = classFormContainer.classList.contains("show");
            toggleClassBtn.textContent = isVisible ? "− Hide Form" : "+ Add Class";
        });
    }
});

/// ======================= INIT ON PAGE LOAD ===========================

/**
 * Binds all class-related event handlers and loads initial data.
 */
$(document).ready(function () {
    loadClasses();
    loadTeacherDropdown();

    $('#class-create-form').on('submit', handleClassCreateFormSubmit);
    $(document).on('click', '.edit-class-btn', handleEditClassBtnClick);
    $(document).on('click', '.cancel-class-btn', handleCancelClassBtnClick);
    $(document).on('click', '.save-class-btn', handleSaveClassBtnClick);
    $(document).on('click', '.delete-class-btn', handleDeleteClassBtnClick);
});
