document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('table');
    table.classList.add('sortable');
    
    const headers = table.querySelectorAll('th');
    
    headers.forEach(function(header, index) {
        header.addEventListener('click', function() {
            sortTable(index, this);
        });
    });
});

function sortTable(column, header) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Remove sorting classes from all headers
    table.querySelectorAll('th').forEach(th => {
        if (th !== header) {
            th.classList.remove('asc', 'desc');
        }
    });
    
    // Fix: Proper direction toggling
    let isAscending;
    if (header.classList.contains('asc')) {
        header.classList.remove('asc');
        header.classList.add('desc');
        isAscending = false;
    } else {
        header.classList.remove('desc');
        header.classList.add('asc');
        isAscending = true;
    }
    
    // Sort the rows
    rows.sort((a, b) => {
        let aValue = a.cells[column].textContent.trim();
        let bValue = b.cells[column].textContent.trim();
        
        // Check if the values are numbers
        if (!isNaN(aValue) && !isNaN(bValue)) {
            aValue = parseFloat(aValue);
            bValue = parseFloat(bValue);
        }
        
        if (aValue < bValue) return isAscending ? -1 : 1;
        if (aValue > bValue) return isAscending ? 1 : -1;
        return 0;
    });
    
    // Reorder the rows in the table
    rows.forEach(row => tbody.appendChild(row));
}