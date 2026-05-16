<?php
require_once dirname(__DIR__) . '/config/database.php';

class AcademicCalendar
{
    private static bool $ensured = false;

    private static function ensureTable(): void
    {
        if (self::$ensured) return;
        getDB()->exec("
            CREATE TABLE IF NOT EXISTS academic_calendar (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                title       VARCHAR(255) NOT NULL,
                description TEXT NULL,
                start_date  DATE NOT NULL,
                end_date    DATE NULL,
                type        ENUM('libur','ujian','kegiatan','semester','lainnya') NOT NULL DEFAULT 'kegiatan',
                created_by  INT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        self::$ensured = true;
    }

    public static function getAll(int $limit = 500, int $offset = 0): array
    {
        self::ensureTable();
        $stmt = getDB()->prepare(
            'SELECT ac.*, u.name AS creator_name
             FROM academic_calendar ac LEFT JOIN users u ON ac.created_by = u.id
             ORDER BY ac.start_date ASC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public static function getByYear(int $year): array
    {
        self::ensureTable();
        $stmt = getDB()->prepare(
            "SELECT ac.*, u.name AS creator_name
             FROM academic_calendar ac LEFT JOIN users u ON ac.created_by = u.id
             WHERE YEAR(ac.start_date) = ?
                OR (ac.end_date IS NOT NULL AND YEAR(ac.end_date) = ?)
             ORDER BY ac.start_date ASC"
        );
        $stmt->execute([$year, $year]);
        return $stmt->fetchAll();
    }

    public static function getUpcoming(int $limit = 5): array
    {
        self::ensureTable();
        $stmt = getDB()->prepare(
            "SELECT * FROM academic_calendar
             WHERE start_date >= CURDATE()
             ORDER BY start_date ASC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array
    {
        self::ensureTable();
        $stmt = getDB()->prepare('SELECT * FROM academic_calendar WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        self::ensureTable();
        return (int) getDB()->query('SELECT COUNT(*) FROM academic_calendar')->fetchColumn();
    }

    public static function countUpcoming(): int
    {
        self::ensureTable();
        return (int) getDB()->query(
            "SELECT COUNT(*) FROM academic_calendar WHERE start_date >= CURDATE()"
        )->fetchColumn();
    }

    public static function create(array $d): int
    {
        self::ensureTable();
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO academic_calendar (title, description, start_date, end_date, type, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $d['title'],
            $d['description'] ?: null,
            $d['start_date'],
            $d['end_date'] ?: null,
            $d['type'],
            $d['created_by'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        self::ensureTable();
        getDB()->prepare(
            'UPDATE academic_calendar SET title=?, description=?, start_date=?, end_date=?, type=? WHERE id=?'
        )->execute([
            $d['title'],
            $d['description'] ?: null,
            $d['start_date'],
            $d['end_date'] ?: null,
            $d['type'],
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        self::ensureTable();
        getDB()->prepare('DELETE FROM academic_calendar WHERE id = ?')->execute([$id]);
    }

    public static function typeLabel(string $type): string
    {
        return match($type) {
            'libur'    => 'Libur',
            'ujian'    => 'Ujian',
            'kegiatan' => 'Kegiatan',
            'semester' => 'Semester',
            default    => 'Lainnya',
        };
    }

    public static function typeChip(string $type): string
    {
        return match($type) {
            'libur'    => 'ns-chip-red',
            'ujian'    => 'ns-chip-yellow',
            'kegiatan' => 'ns-chip-blue',
            'semester' => 'ns-chip-violet',
            default    => 'ns-chip-ink',
        };
    }

    public static function typeBorderColor(string $type): string
    {
        return match($type) {
            'libur'    => '#FB7185',
            'ujian'    => '#FCD34D',
            'kegiatan' => '#60A5FA',
            'semester' => '#A78BFA',
            default    => '#D1D5DB',
        };
    }

    public static function dateRange(string $startDate, ?string $endDate): string
    {
        static $bulan = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
            7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
        ];
        $s = new DateTime($startDate);
        if (!$endDate) {
            return $s->format('d') . ' ' . $bulan[(int)$s->format('n')] . ' ' . $s->format('Y');
        }
        $e = new DateTime($endDate);
        if ($s->format('Y-m') === $e->format('Y-m')) {
            return $s->format('d') . '–' . $e->format('d') . ' ' . $bulan[(int)$s->format('n')] . ' ' . $s->format('Y');
        }
        if ($s->format('Y') === $e->format('Y')) {
            return $s->format('d') . ' ' . $bulan[(int)$s->format('n')] . ' – '
                 . $e->format('d') . ' ' . $bulan[(int)$e->format('n')] . ' ' . $s->format('Y');
        }
        return $s->format('d') . ' ' . $bulan[(int)$s->format('n')] . ' ' . $s->format('Y')
             . ' – '
             . $e->format('d') . ' ' . $bulan[(int)$e->format('n')] . ' ' . $e->format('Y');
    }

    public static function eventStatus(string $startDate, ?string $endDate): string
    {
        $today = date('Y-m-d');
        $end   = $endDate ?? $startDate;
        if ($end < $today)        return 'past';
        if ($startDate > $today)  return 'upcoming';
        return 'ongoing';
    }

    public static function groupByMonth(array $events): array
    {
        $grouped = [];
        foreach ($events as $ev) {
            $key = date('Y-m', strtotime($ev['start_date']));
            $grouped[$key][] = $ev;
        }
        return $grouped;
    }
}
