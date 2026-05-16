function setAll(status) {
    document.querySelectorAll('#attendanceTable tr[data-student]').forEach(function (row) {
        var sid   = row.dataset.student;
        var radio = row.querySelector('input[name="status[' + sid + ']"][value="' + status + '"]');
        if (radio) radio.checked = true;
    });
}
