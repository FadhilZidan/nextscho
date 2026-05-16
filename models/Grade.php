<?php
require_once dirname(__DIR__) . '/config/database.php';

class Grade
{
    /** Semua siswa di kelas + nilai mereka untuk 1 mata pelajaran */
    public static function getByClassSubject(int $classId, int $subjectId, int $semester, string $year): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.id AS student_id, s.name AS student_name, s.nis,
                    g.id, g.score_daily, g.score_mid, g.score_final, g.score_average
             FROM students s
             LEFT JOIN grades g ON g.student_id = s.id
                 AND g.subject_id = ? AND g.semester = ? AND g.academic_year = ?
             WHERE s.class_id = ?
             ORDER BY s.name'
        );
        $stmt->execute([$subjectId, $semester, $year, $classId]);
        return $stmt->fetchAll();
    }

    /** Semua nilai satu siswa */
    public static function getByStudent(int $studentId, int $semester, string $year): array
    {
        $stmt = getDB()->prepare(
            'SELECT g.*, sub.name AS subject_name, sub.code AS subject_code
             FROM grades g
             LEFT JOIN subjects sub ON g.subject_id = sub.id
             WHERE g.student_id = ? AND g.semester = ? AND g.academic_year = ?
             ORDER BY sub.name'
        );
        $stmt->execute([$studentId, $semester, $year]);
        return $stmt->fetchAll();
    }

    /** Upsert satu nilai siswa */
    public static function save(int $studentId, int $subjectId, array $scores, int $semester, string $year): void
    {
        $daily = is_numeric($scores['daily'] ?? '') ? (float) $scores['daily'] : null;
        $mid   = is_numeric($scores['mid']   ?? '') ? (float) $scores['mid']   : null;
        $final = is_numeric($scores['final'] ?? '') ? (float) $scores['final'] : null;

        // Bobot: 30% harian + 30% UTS + 40% UAS
        $avg = ($daily !== null && $mid !== null && $final !== null)
            ? round($daily * 0.3 + $mid * 0.3 + $final * 0.4, 2)
            : null;

        getDB()->prepare(
            'INSERT INTO grades (student_id, subject_id, score_daily, score_mid, score_final, score_average, semester, academic_year)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               score_daily=VALUES(score_daily), score_mid=VALUES(score_mid),
               score_final=VALUES(score_final), score_average=VALUES(score_average)'
        )->execute([$studentId, $subjectId, $daily, $mid, $final, $avg, $semester, $year]);
    }

    /** Laporan nilai per kelas (rata-rata per siswa) */
    public static function getReportByClass(int $classId, int $semester, string $year): array
    {
        $stmt = getDB()->prepare(
            'SELECT s.name AS student_name, s.nis,
                    sub.name AS subject_name,
                    g.score_daily, g.score_mid, g.score_final, g.score_average
             FROM students s
             LEFT JOIN grades g   ON g.student_id  = s.id AND g.semester = ? AND g.academic_year = ?
             LEFT JOIN subjects sub ON g.subject_id = sub.id
             WHERE s.class_id = ?
             ORDER BY s.name, sub.name'
        );
        $stmt->execute([$semester, $year, $classId]);
        return $stmt->fetchAll();
    }

    /** Ringkasan: rata-rata semua nilai satu siswa */
    public static function getAverageByStudent(int $studentId, int $semester, string $year): ?float
    {
        $stmt = getDB()->prepare(
            'SELECT AVG(score_average) FROM grades
             WHERE student_id = ? AND semester = ? AND academic_year = ? AND score_average IS NOT NULL'
        );
        $stmt->execute([$studentId, $semester, $year]);
        $val = $stmt->fetchColumn();
        return $val !== false ? round((float) $val, 2) : null;
    }
}
