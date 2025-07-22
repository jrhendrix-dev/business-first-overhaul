/**
 * @file usuarios.js
 * @fileoverview
 * Handles user management in the admin dashboard, including:
 * - AJAX-based user CRUD operations
 * - Dynamic dropdown for class assignment (based on user role)
 * - Form toggling, validation, and resets
 *
 * Author: Jonathan Ray Hendrix
 * License: MIT
 */

let originalUserCreateFormHtml = null;

//////////////////////////////
// LOAD USERS
//////////////////////////////

/**
 * Loads all users via AJAX and injects them into the user table container.
 *
 * @function
 * @returns {void}
 */
function loadUsers() {
    $.get('/api/admin?loadUsers=1', function(data) {
        $('#user-table-container').html(data);
    });
}

//////////////////////////////
// CLASS DROPDOWN LOGIC
//////////////////////////////

/**
 * Fetches available classes from the server.
 * Ensures the currently selected class is included (if any).
 *
 * @param {?number|string} selectedId - Class ID to pre-select (optional).
 * @returns {JQuery.Promise} A promise resolving to a <select> jQuery element.
 */
function fetchAvailableClasses(selectedId = null) {
    const url = selectedId
        ? `/api/admin?availableClasses=1&include=${selectedId}`
        : '/api/admin?availableClasses=1';

    return $.get(url).then(function (optionsHtml) {
        const select = $('<select class="form-control" name="class"></select>')
            .append(`<option value="">-- No class --</option>`, optionsHtml);

        if (selectedId) {
            select.find(`option[value='${selectedId}']`).prop('selected', true);
        }

        return select;
    }).fail(function(xhr) {
        console.error('Failed to load available classes:', xhr.responseText);
    });
}

/**
 * Loads the teacher class dropdown for the user creation form.
 *
 * @function
 * @returns {void}
 */
function loadAvailableClassesDropdown() {
    fetchAvailableClasses().then(function(select) {
        $('#user-create-form select[name="class"]').replaceWith(select);
    });
}

//////////////////////////////
// FORM LOGIC: CREATE FORM
//////////////////////////////

/**
 * Adjusts the class dropdown dynamically based on selected user level.
 *
 * @param {Event} e - jQuery change event
 */
function handleUserLevelChangeInCreateForm(e) {
    const selected = $(this).val();
    const $form = $('#user-create-form');
    $form.find('[name="class"]').remove();

    if (selected === '1') {
        // Admin: no class needed
        $form.find('button[type="submit"]').before(
            $('<input>', { type: 'hidden', name: 'class', value: '' })
        );
    } else if (selected === '2') {
        // Teacher: fetch dropdown with only unassigned classes
        fetchAvailableClasses().then(function(select) {
            select.attr({ name: 'class', class: 'form-control mb-2', required: false });
            $form.find('button[type="submit"]').before(select);
        });
    } else if (selected === '3') {
        // Student: dropdown with all classes
        const $classField = $(`
            <select name="class" class="form-control mb-2" required>
                <option value="" disabled selected>Select a class</option>
                ${window.classOptions}
            </select>
        `);
        $form.find('button[type="submit"]').before($classField);
    }
}

/**
 * Handles form submission for creating a new user.
 *
 * @param {Event} e - Submit event
 */
function handleUserCreateFormSubmit(e) {
    e.preventDefault();

    $.post('/api/admin', $(this).serialize())
        .done(function() {
            $('#create-user-feedback')
                .removeClass('text-danger')
                .addClass('text-success')
                .text('User created successfully.');

            $('#user-create-form').replaceWith(originalUserCreateFormHtml);
            refreshAllTabs();
        })
        .fail(function(xhr) {
            $('#create-user-feedback')
                .removeClass('text-success')
                .addClass('text-danger')
                .text('Failed to create user: ' + xhr.responseText);
        });
}

//////////////////////////////
// EDIT USER LOGIC
//////////////////////////////

/**
 * Handles click on edit button for a user.
 * Converts row fields into editable inputs and dropdowns.
 *
 * @function
 * @returns {void}
 */
function handleEditUserClick() {
    const row = $(this).closest('tr');
    const id = row.data('id');
    const username = row.find('.username').text();
    const email = row.find('.email').text();
    const currentClassId = row.find('.class').data('classid');
    const currentUlevel = row.find('.ulevel').text();

    row.find('.username').html(`<input class="form-control" value="${username}">`);
    row.find('.email').html(`<input class="form-control" value="${email}">`);

    const ulevelSelect = $(`<select class='form-control'>
        <option value=''>User Role</option>
        <option value='1'>Admin</option>
        <option value='2'>Teacher</option>
        <option value='3'>Student</option>
    </select>`);
    ulevelSelect.val(currentUlevel);
    row.find('.ulevel').html(ulevelSelect);

    const updateButtons = () => {
        row.find('.edit-btn').replaceWith('<button class="btn btn-sm btn-success save-btn">Save</button>');
        row.find('.delete-btn').replaceWith('<button class="btn btn-sm btn-secondary cancel-btn">Cancel</button>');
    };

    if (currentUlevel === '2') {
        fetchAvailableClasses(currentClassId).then(function (select) {
            row.find('.class').html(select);
            updateButtons();
        });
    } else {
        const classSelect = $('<select class="form-control"></select>')
            .append(`<option value="">-- Unassigned --</option>`, window.classOptions)
            .val(currentClassId);
        row.find('.class').html(classSelect);
        updateButtons();
    }
}

/**
 * Handles user level change in the edit form row.
 * Updates class field based on new user type.
 */
function handleUserLevelChangeInEditRow() {
    const row = $(this).closest('tr');
    const selected = $(this).val();
    const currentClassId = row.find('.class select').val();

    if (selected === '2') {
        fetchAvailableClasses(currentClassId).then(function (select) {
            row.find('.class').html(select);
        });
    } else if (selected === '1') {
        row.find('.class').html('<input type="hidden" name="class" value="">');
    } else {
        const classSelect = $('<select class="form-control"></select>')
            .append(`<option value="">-- Unassigned --</option>`, window.classOptions)
            .val(currentClassId);
        row.find('.class').html(classSelect);
    }
}

/**
 * Cancels editing and reloads the full user list.
 *
 * @param {Event} e
 */
function handleCancelEditUserClick(e) {
    refreshAllTabs();
}

/**
 * Submits edited user data to the server.
 *
 * @param {Event} e
 */
function handleSaveUserEditClick(e) {
    const row = $(this).closest('tr');
    const id = row.data('id');
    const username = row.find('.username input').val();
    const email = row.find('.email input').val();
    const clase = row.find('.class select').val();
    const ulevel = row.find('.ulevel select').val();

    $.post('/api/admin', {
        updateUser: 1,
        user_id: id,
        username: username,
        email: email,
        class: clase,
        ulevel: ulevel
    }).done(function () {
        refreshAllTabs();
    });
}

/**
 * Deletes a user after confirmation.
 */
function handleDeleteUserClick() {
    const row = $(this).closest('tr');
    const id = row.data('id');
    if (confirm('Are you sure you want to delete this user?')) {
        $.post('/api/admin', {
            deleteUser: 1,
            user_id: id
        }).done(function () {
            refreshAllTabs();
        });
    }
}

//////////////////////////////
// FORM & TAB EVENTS
//////////////////////////////

/**
 * Handles UI cleanup and form reset when tab is shown.
 *
 * @param {Event} e
 */
function handleTabShown(e) {
    const target = $(e.target).attr("href");
    if (target === "#usuarios") {
        $('#user-create-form')[0].reset();
        const $ulevel = $('#user-create-form select[name="ulevel"]');
        if ($ulevel.length) {
            $ulevel.prop('selectedIndex', 0).trigger('change');
        }
        $('#create-user-feedback').text('').removeClass('text-success text-danger');
    }
}

/**
 * Fully resets form on tab switch to 'Usuarios' or 'Clases'.
 */
function handleFullResetOnTabShown() {
    $('#user-create-form').replaceWith(originalUserCreateFormHtml);
    $('#create-user-feedback').text('').removeClass('text-success text-danger');
}

//////////////////////////////
// DOM READY
//////////////////////////////

$(document).ready(function() {
    originalUserCreateFormHtml = $('#user-create-form').prop('outerHTML');

    if ($('#user-create-form select[name="ulevel"]').val() === '2') {
        loadAvailableClassesDropdown();
    }

    // Toggle add user form
    const toggleBtn = $('#toggleUserForm');
    const formContainer = $('#userFormContainer');
    toggleBtn.on('click', function () {
        formContainer.toggleClass('show');
        toggleBtn.text(formContainer.hasClass('show') ? '- Hide form' : '+ Add user');
    });

    loadUsers();

    // Event bindings
    $(document).on('change', '#user-create-form select[name="ulevel"]', handleUserLevelChangeInCreateForm);
    $(document).on('submit', '#user-create-form', handleUserCreateFormSubmit);
    $(document).on('click', '.edit-btn', handleEditUserClick);
    $(document).on('change', 'tr .ulevel select', handleUserLevelChangeInEditRow);
    $(document).on('click', '.cancel-btn', handleCancelEditUserClick);
    $(document).on('click', '.save-btn', handleSaveUserEditClick);
    $(document).on('click', '.delete-btn', handleDeleteUserClick);

    $('a[data-toggle="tab"]').on('shown.bs.tab', handleTabShown);
    $('a[data-toggle="tab"][href="#usuarios"], a[data-toggle="tab"][href="#clases"]').on('shown.bs.tab', handleFullResetOnTabShown);
});
