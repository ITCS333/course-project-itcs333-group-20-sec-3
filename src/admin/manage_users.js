/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

const API_URL = 'http://localhost:8000'; //MAKESURE DOOOO
const API_ENDPOINT = API_URL + '/admin/index.php';
// let currentUser = null;

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
let studentTableBody = document.getElementById("student-table-body");

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
let addStudentForm = document.getElementById("add-student-form");
// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
let changePasswordForm = document.getElementById("password-form");
// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
let searchInput = document.getElementById("search-input");
// TODO: Select all table header (th) elements in thead.
let tableHeaders = document.querySelectorAll("#student-table thead th");

let logoutBtn = document.getElementById("logout-btn");

// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */

async function checkAuth() {
    try {
        const response = await fetch(API_ENDPOINT + '?verify=true', {
            method: 'GET',
            credentials: 'include'
        });
        
        if (response.status === 401 || response.status === 403) {
            // either session invalid or not admin
            window.location.href = "../auth/login.html";
            return null;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success && data.user) {
            currentUser = data.user;
            updateUIForUser(data.user);
            return data.user;
        }
        
        return null;
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = "../auth/login.html";
        return null;
    }
}


async function apiRequest(method, params = {}, data = null) {
  const options = {
    method: method,
    headers: {
      'Content-Type': 'application/json'
    },
    credentials: 'include'
  };

  if (data) {
    options.body = JSON.stringify(data);
  }

  const url = new URL(API_ENDPOINT);
  Object.keys(params).forEach(key => {
    url.searchParams.append(key, params[key]);
  });

  try {
    const response = await fetch(url, options);

    if (response.status === 401 || response.status === 403) {
      window.location.href = "../auth/login.html";
      return null;
    }

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP error! status: ${response.status}`);
    }

    return await response.json();

  }catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
}

async function fetchStudents(searchTerm = '') {
    const params = {};
    if (searchTerm) {
        params.search = searchTerm;
    }
    
    const result = await apiRequest('GET', params);
    if (result.success) {
        return result.data;
    } else {
        throw new Error(result.message || 'Failed to fetch students');
    }
}

async function createStudent(studentData) {
    const result = await apiRequest('POST', {}, studentData);
    if (!result.success) {
        throw new Error(result.message || 'Failed to create student');
    }
    return result;
}

async function updateStudent(studentData) {
    const result = await apiRequest('PUT', {}, studentData);
    if (!result.success) {
        throw new Error(result.message || 'Failed to update student');
    }
    return result;
}

async function deleteStudent(studentId) {
    const result = await apiRequest('DELETE', { student_id: studentId });
    if (!result.success) {
        throw new Error(result.message || 'Failed to delete student');
    }
    return result;
}

async function changePassword(passwordData) {
    const result = await apiRequest('POST', { action: 'change_password' }, passwordData);
    if (!result.success) {
        throw new Error(result.message || 'Failed to change password');
    }
    return result;
}

function createStudentRow(student) {
  const row = document.createElement("tr");

  const nameCell = document.createElement("td");
  nameCell.textContent = student.name;
  row.appendChild(nameCell);

  // const idCell = document.createElement("td");
  // idCell.textContent = student.student_id;
  // row.appendChild(idCell);

  const emailCell = document.createElement("td");
  emailCell.textContent = student.email;
  row.appendChild(emailCell);

  const createdDate = new Date(user.created_at);
  dateCell.textContent = createdDate.toLocaleDateString() + ' ' + createdDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
  row.appendChild(dateCell);

  const actionCell = document.createElement("td");

  const editButton = document.createElement("button");
  editButton.textContent = "Edit";
  editButton.className = "edit-btn";
  editButton.setAttribute("data-id", student.student_id);

  const deleteButton = document.createElement("button");
  deleteButton.textContent = "Delete";
  deleteButton.className = "delete-btn";
  deleteButton.setAttribute("data-id", student.student_id);

  actionCell.appendChild(editButton);
  actionCell.appendChild(deleteButton);
  row.appendChild(actionCell);

  return row;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
async function renderTable(studentArray) {
    studentTableBody.innerHTML = "";

    studentArray.forEach(student => {
        const row = createStudentRow(student);
        studentTableBody.appendChild(row);
    });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
async function handleChangePassword(event) {
  event.preventDefault();

  const currentPasswordInput = document.getElementById("current-password");
  const newPasswordInput = document.getElementById("new-password");
  const confirmPasswordInput = document.getElementById("confirm-password");
  
  const currentPassword = currentPasswordInput.value;
  const newPassword = newPasswordInput.value;
  const confirmPassword = confirmPasswordInput.value;

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  if (newPassword.length < 8){
    alert("Password must be at least 8 characters.");
    return;
  }

  try {

      await changePassword({
            student_id: currentUser.id,
            current_password: currentPassword,
            new_password: newPassword
      });

      alert("Password updated successfully!");
    
      currentPasswordInput.value = "";
      newPasswordInput.value = "";
      confirmPasswordInput.value = "";

  }catch (error) {
    alert(`Error: ${error.message}`);
  }

}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
async function handleAddStudent(event) {
  event.preventDefault();

  const nameInput = document.getElementById("student-name");
  // const idInput = document.getElementById("student-id");
  const emailInput = document.getElementById("student-email");
  const passwordInput = document.getElementById("default-password");
  
  const name = nameInput.value.trim();
  // const studentId = idInput.value.trim();
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!name || !email || !password){
    alert("Please fill out all required fields.");
    return;
  }

  try {
    await createStudent({
            name: name,
            email: email,
            password: password
        });

    //check dups from the db, use fetch students!! DOOOO

    nameInput.value = "";
    emailInput.value = "";
    passwordInput.value = "password123";

    await loadAndRenderStudents();
  }catch (error) {
    alert(`Error: ${error.message}`);
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
async function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const studentId = event.target.getAttribute("data-id");

    try {
      await deleteStudent(studentId);
      alert("Student deleted successfully!");
      await loadAndRenderStudents();
    }catch (error) {
      alert(`Error: ${error.message}`);
    }
  }
}



/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
async function handleSearch(event) {
  const searchTerm = searchInput.value.toLowerCase().trim();

  try {
    const students = await fetchStudents(searchTerm);
    await renderTable(students);
  }catch (error) {
    console.error("Search error:", error);
    studentTableBody.innerHTML = "";
  }
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
async function handleSort(event) {
  const th = event.currentTarget;
  const sortBy = th.getAttribute("data-sort");
  let sortDir = th.getAttribute("data-sort-dir") || "asc";

  sortDir = sortDir === "asc" ? "desc" : "asc";
  th.setAttribute("data-sort-dir", sortDir);

  try {
    const students = await fetchStudents(searchInput.value.trim());

    students.sort( (a, b) => {
      let comp = 0;
  
      if (sortBy === "id") {
        comp = a.id.localeCompare(b.id); // WHY SORT BY ID? AIN"T GOOD FOR SECURITY DOOO
      } else if (sortBy === "name") {
        comp = a.name.localeCompare(b.name);
      } else if (sortBy === "email") {
        comp = a.email.localeCompare(b.email);
      }
  
      return sortDir === "desc" ? -comp : comp;
    })
  
    await renderTable(students);

  }catch (error) {
    console.error("Sort error:", error);
  }
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */

async function loadAndRenderStudents() {
    try {
        const students = await fetchStudents('');
        await renderTable(students);
    } catch (error) {
        console.error("Failed to load students:", error);
        alert("Failed to load students. Please check your connection.");
        studentTableBody.innerHTML = "<tr><td colspan='4'>Failed to load students. Please check your connection.</td></tr>";
    }
}

function setupLogout() {
  const logoutBtn = document.getElementById("logout-btn");
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            if (confirm('Are you sure you want to logout?')) {
                try {
                    // Optional: Call logout endpoint
                    await fetch(API_URL + '/auth/logout.php', {
                        method: 'POST',
                        credentials: 'include'
                    });
                } catch (error) {
                    console.error('Logout error:', error);
                }
                
              //remove session!! DOOOO
                window.location.href = "../auth/login.html";
            }
        });
    }
}

async function loadStudentsAndInitialize() {
  try{

    const user = await checkAuth();
    if (!user) return;
        
    setupLogout();

    await loadAndRenderStudents();

    changePasswordForm.addEventListener("submit", handleChangePassword);
    addStudentForm.addEventListener("submit", handleAddStudent);
    studentTableBody.addEventListener("click", handleTableClick);
    searchInput.addEventListener("input", handleSearch);

    tableHeaders.forEach(header => {
      header.addEventListener("click", handleSort);
    });

  }catch (error) {
    console.error("Error loading students data:", error);
    students = [];
    renderTable(students);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
