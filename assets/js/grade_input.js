function calcAvg(studentId) {
    var row = document.getElementById('row-' + studentId);
    if (!row) return;

    var daily = row.querySelector('input[name="grades[' + studentId + '][daily]"]');
    var mid   = row.querySelector('input[name="grades[' + studentId + '][mid]"]');
    var fin   = row.querySelector('input[name="grades[' + studentId + '][final]"]');
    var cell  = document.getElementById('avg-' + studentId);
    if (!cell) return;

    var dv = daily ? daily.value.trim() : '';
    var mv = mid   ? mid.value.trim()   : '';
    var fv = fin   ? fin.value.trim()   : '';

    if (dv === '' && mv === '' && fv === '') {
        cell.innerHTML = '<span class="text-gray-400">—</span>';
        return;
    }

    var avg = (parseFloat(dv) || 0) * 0.3
            + (parseFloat(mv) || 0) * 0.3
            + (parseFloat(fv) || 0) * 0.4;

    var cls = avg >= 70 ? 'font-bold text-indigo-700' : 'font-bold text-red-600';
    cell.innerHTML = '<span class="' + cls + '">' + avg.toFixed(1) + '</span>';
}
