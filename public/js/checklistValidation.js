// ===============================
// Guest Table Functions
// ===============================
function addGuestRow() {

    const tbody = document.querySelector("#guestTable tbody");
    if (!tbody) return;

    const newRow = tbody.insertRow();

    newRow.innerHTML = `
        <td>
            <input type="hidden" name="guest_id[]" value="">
            <input type="text" name="guest_name[]" class="form-control" required>
        </td>
        <td><input type="text" name="company_name[]" class="form-control" required></td>
        <td><input type="text" name="designation[]" class="form-control" required></td>
        <td><input type="email" name="guest_email[]" class="form-control"></td>
        <td><input type="file" name="bio_image[]" class="form-control"></td>
        <td>
            <input type="text" name="contact_no[]" 
                   class="form-control guest-contact"
                   maxlength="10" pattern="\\d{10}" required>
        </td>
        <td class="text-center">
            <button type="button"
                    class="btn btn-danger btn-sm"
                    onclick="deleteGuestRow(this)">
                Delete
            </button>
        </td>
    `;
}




function deleteGuestRow(button) {

    const row = button.closest("tr");
    if (!row) return;

    row.remove();
}



// ===============================
// Program Incharge Table Functions
// ===============================
function addInchargeRow() {

    const table = document.getElementById("incharge_table");
    if (!table) return;

    const tbody = table.querySelector("tbody");
    if (!tbody) return;

    const rowCount = tbody.rows.length;
    const row = tbody.insertRow(-1);

    row.innerHTML = `
        <td>${rowCount + 1}</td>

        <!-- Hidden ID (empty for new record) -->
        <input type="hidden" name="incharge_id[]" value="">

        <td>
            <input type="text" 
                   name="incharge_name[]" 
                   placeholder="Enter Incharge Name"
                   pattern="[A-Za-z\\s]+"
                   title="Only letters and spaces allowed"
                   required>
        </td>

      <td>
    <textarea name="task[]" 
              placeholder="Enter Task" 
              rows="0"
              class="form-control mb-3"
               style="height: 32px; overflow: hidden;"
             ></textarea>
</td>
<td class="text-center">
            <button type="button" 
                    onclick="deleteInchargeRow(this)">
                Delete
            </button>
        </td>
    `;
}


function reindexInchargeRows() {
    const rows = document.querySelectorAll("#incharge_table tbody tr");
    rows.forEach((row, index) => {
        row.cells[0].innerText = index + 1;
    });
}




function deleteInchargeRow(button) {

    const row = button.closest("tr");
    if (!row) return;

    const form = button.closest("form");

    const idInput = row.querySelector('input[name="incharge_id[]"]');

    // If record exists in DB
    if (idInput && idInput.value !== '') {

        const deleteInput = document.createElement("input");
        deleteInput.type = "hidden";
        deleteInput.name = "delete_incharge_ids[]";
        deleteInput.value = idInput.value;

        form.appendChild(deleteInput);
    }

    row.remove();
}



// ===============================
// Multi-day Checkbox Logic
// ===============================
document.addEventListener("DOMContentLoaded", function () {

    const multiDay = document.getElementById("multi_day");
    const programmeDates = document.getElementById("programme_dates");
    const singleDate = document.getElementById("programme_date");

    if (!multiDay || !programmeDates || !singleDate) return;

    function toggleDates() {
        if (multiDay.checked) {
            programmeDates.style.display = "block";
            singleDate.disabled = true;
        } else {
            programmeDates.style.display = "none";
            singleDate.disabled = false;
        }
    }

    // ✅ Run immediately on page load
    toggleDates();

    // ✅ Run when checkbox changes
    multiDay.addEventListener("change", toggleDates);

});