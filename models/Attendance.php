<?php
require_once dirname(__DIR__) . '/config/database.php';

class Attendance
{
    /** Semua siswa di kelas + status absen pada tanggal tertentu */
    public static function getByClassDate(int $classId, string $date): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.id AS student_id, s.name AS student_name, s.nis,
                    a.id, a.status, a.notes
             FROM students s
             LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
             WHERE s.class_id = ?
             ORDER BY s.name'
        );
        $stmt->execute([$date, $classId]);
        return $stmt->fetchAll();
    }

    /** Absensi satu siswa dalam satu bulan */
    public static function getByStudent(int $studentId, int $month, int $year): array
    {
        $stmt = getDB()->prepare(
            'SELECT * FROM attendance
             WHERE student_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
             ORDER BY date'
        );
        $stmt->execute([$studentId, $month, $year]);
        return $stmt->fetchAll();
    }

    /** Rekap hadir/izin/sakit/alpha satu siswa dalam satu tahun */
    public static function getSummary(int $studentId, int $year = 0): array
    {
        $year  = $year ?: (int) date('Y');
        $stmt  = getDB()->prepare(
            'SELECT status, COUNT(*) AS total FROM attendance
             WHERE student_id = ? AND YEAR(date) = ? GROUP BY status'
        );
        $stmt->execute([$studentId, $year]);
        $rows    = $stmt->fetchAll();
        $summary = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
        foreach ($rows as $r) $summary[$r['status']] = (int) $r['total'];
        return $summary;
    }

    /** Simpan batch absensi (upsert) */
    public static function saveBatch(int $classId, string $date, int $teacherId, array $records): void
    {
        $stmt = getDB()->prepare(
            'INSERT INTO attendance (student_id, class_id, date, status, teacher_id, notes)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), notes=VALUES(notes), teacher_id=VALUES(teacher_id)'
        );
        foreach ($records as $r) {
            $stmt->execute([
                (int) $r['student_id'],
                $classId,
                $date,
                $r['status'] ?? 'hadir',
                $teacherId,
                $r['notes']  ?? null,
            ]);
        }
    }

    /** Laporan bulanan per kelas */
    public static function getReportByClass(int $classId, int $month, int $year): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.name AS student_name, s.nis,
                    SUM(a.status="hadir") AS hadir,
                    SUM(a.status="izin")  AS izin,
                    SUM(a.status="sakit") AS sakit,
                    SUM(a.status="alpha") AS alpha,
                    COUNT(a.id)           AS total_days
             FROM students s
             LEFT JOIN attendance a ON a.student_id = s.id
                 AND MONTH(a.date) = ? AND YEAR(a.date) = ?
             WHERE s.class_id = ?
             GROUP BY s.id ORDER BY s.name'
        );
        $stmt->execute([$month, $year, $classId]);
        return $stmt->fetchAll();
    }

    /** Cek apakah absensi kelas pada tanggal tertentu sudah ada */
    public static function dateRecorded(int $classId, string $date): bool
    {
        $stmt = getDB()->prepare(
            'SELECT 1 FROM attendance WHERE class_id = ? AND date = ? LIMIT 1'
        );
        $stmt->execute([$classId, $date]);
        return (bool) $stmt->fetchColumn();
    }
}
