<?php

namespace App\Console\Commands;

use App\Models\{Subject, Course, Module, Lesson, Teacher, AcademicYear, Enrollment, Student};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedIslamicCourses extends Command
{
    protected $signature   = 'seed:islamic-courses {--fresh : Delete existing Islamic courses first}';
    protected $description = 'Seed subjects and courses from UmmahAPI Islamic content';

    const KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const BASE = 'https://ummahapi.com';

    private array $headers;
    private ?Teacher $teacher;
    private ?AcademicYear $year;

    public function handle(): int
    {
        $this->headers = ['X-API-Key' => self::KEY, 'Accept' => 'application/json'];
        $this->teacher = Teacher::first();
        $this->year    = AcademicYear::where('is_current', true)->first();

        if (!$this->teacher) {
            $this->line('<fg=red>✗ No teacher found. Run seeders first.</>');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('<fg=green>╔══════════════════════════════════════════════╗</>');
        $this->line('<fg=green>║   SEED ISLAMIC COURSES FROM UMMAHAPI         ║</>');
        $this->line('<fg=green>╚══════════════════════════════════════════════╝</>');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->line('<fg=yellow>Removing old Islamic subjects & courses...</>');
            Subject::whereIn('name', $this->islamicSubjectNames())->each(function ($s) {
                Course::where('subject_id', $s->id)->each(fn($c) => $c->forceDelete());
                $s->delete();
            });
        }

        // ── 1. Quran ────────────────────────────────────────────────────────
        $this->seedQuran();

        // ── 2. Hadith ───────────────────────────────────────────────────────
        $this->seedHadith();

        // ── 3. Asma ul Husna ────────────────────────────────────────────────
        $this->seedAsma();

        // ── 4. Duas ─────────────────────────────────────────────────────────
        $this->seedDuas();

        // ── 5. Prayer & Qibla ───────────────────────────────────────────────
        $this->seedPrayer();

        // ── 6. Enroll all students ──────────────────────────────────────────
        $this->enrollAllStudents();

        $this->newLine();
        $this->line('<fg=green>╔══════════════════════════════════════════════╗</>');
        $this->line('<fg=green>║  Done! Check /admin/courses                  ║</>');
        $this->line('<fg=green>╚══════════════════════════════════════════════╝</>');
        $this->newLine();

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────────────────────────────

    private function seedQuran(): void
    {
        $this->line('<fg=cyan>▶ Quran — fetching surahs from API...</>');

        $subject = $this->subject(
            'Coran',         'القرآن الكريم',    'Coran',       'Holy Quran',
            'o-book-open',   '#10b981'
        );

        // Course: Al-Fatiha to Al-Baqara (selected surahs)
        $surahs = [1, 2, 36, 55, 67, 78, 112, 113, 114];
        $course = $this->course($subject, 'Apprentissage du Coran', 'تعلم القرآن الكريم', 'Apprentissage du Coran', 'free');

        $moduleOrder = 1;
        foreach ($surahs as $surahNum) {
            $data = $this->api("/api/quran/surah/{$surahNum}");
            if (!$data) continue;

            $surah   = $data['data']['surah']  ?? [];
            $verses  = $data['data']['verses'] ?? [];

            $nameAr  = $surah['name_arabic']    ?? "سورة {$surahNum}";
            $nameFr  = $surah['name_transliteration'] ?? "Sourate {$surahNum}";
            $meaning = $surah['name_english']   ?? '';

            $module = Module::create([
                'course_id'   => $course->id,
                'title'       => $nameFr,
                'title_ar'    => $nameAr,
                'title_fr'    => "Sourate {$nameFr}" . ($meaning ? " ({$meaning})" : ''),
                'title_en'    => $meaning,
                'description' => ($surah['revelation_type'] ?? '') . ' — ' . ($surah['total_verses'] ?? count($verses)) . ' versets',
                'order'       => $moduleOrder++,
            ]);

            $verseCount = $surah['total_verses'] ?? count($verses);
            $this->line("  Module: {$module->title_fr} ({$verseCount} versets)");

            // Add up to 5 verse-lessons per surah
            $lessonOrder = 1;
            foreach (array_slice($verses, 0, 5) as $verse) {
                $arabic = $verse['arabic'] ?? '';
                $transFr = $verse['translations']['french'] ?? ($verse['translations']['sahih_international'] ?? '');
                $key     = $verse['verse_key'] ?? "{$surahNum}:{$lessonOrder}";

                Lesson::create([
                    'module_id'       => $module->id,
                    'title'           => "Verset {$key}",
                    'title_ar'        => $arabic ? mb_substr($arabic, 0, 60) . '…' : "الآية {$key}",
                    'title_fr'        => "Verset {$key}",
                    'title_en'        => "Verse {$key}",
                    'type'            => 'text',
                    'content'         => "﴿ {$arabic} ﴾\n\n{$transFr}",
                    'duration_minutes' => 5,
                    'is_free_preview' => $lessonOrder === 1,
                    'order'           => $lessonOrder++,
                    'status'          => 'published',
                ]);
            }
        }

        $count = $moduleOrder - 1;
        $this->line("  <fg=green>✓ Quran course seeded ({$count} modules)</>");
    }

    // ────────────────────────────────────────────────────────────────────────

    private function seedHadith(): void
    {
        $this->line('<fg=cyan>▶ Hadith — fetching from Bukhari...</>');

        $subject = $this->subject(
            'Hadith',        'الحديث النبوي',    'Hadith',      'Hadith',
            'o-chat-bubble-left-right', '#f59e0b'
        );

        $course  = $this->course($subject, 'Hadiths Sahih Bukhari', 'صحيح البخاري', 'Hadiths Sahih Bukhari', 'free');

        // Fetch multiple pages
        $pages   = [1, 2, 3, 4, 5];
        $modOrder = 1;

        foreach ($pages as $page) {
            $data    = $this->api("/api/hadith/bukhari?page={$page}&per_page=5");
            $hadiths = $data['data']['hadiths'] ?? [];
            if (empty($hadiths)) continue;

            $module = Module::create([
                'course_id'   => $course->id,
                'title'       => "Hadiths — Page {$page}",
                'title_ar'    => "أحاديث — الصفحة {$page}",
                'title_fr'    => "Hadiths de Bukhari — Page {$page}",
                'description' => '5 hadiths authentiques',
                'order'       => $modOrder++,
            ]);

            $lessonOrder = 1;
            foreach ($hadiths as $h) {
                $arabic  = $h['arabic']  ?? '';
                $english = $h['english'] ?? '';
                $hadithNum = $h['hadithnumber'] ?? $lessonOrder;
                $grade   = $h['grade'] ?? '';

                Lesson::create([
                    'module_id'       => $module->id,
                    'title'           => "Hadith #{$hadithNum}",
                    'title_ar'        => $arabic ? mb_substr($arabic, 0, 60) . '…' : "حديث #{$hadithNum}",
                    'title_fr'        => "Hadith #{$hadithNum}" . ($grade ? " — {$grade}" : ''),
                    'type'            => 'text',
                    'content'         => ($arabic ? "عَنْ: {$arabic}\n\n" : '') . $english,
                    'duration_minutes' => 10,
                    'is_free_preview' => $lessonOrder === 1,
                    'order'           => $lessonOrder++,
                    'status'          => 'published',
                ]);
            }

            $this->line("  Module page {$page}: " . count($hadiths) . " hadiths");
        }

        $this->line("  <fg=green>✓ Hadith course seeded</>");
    }

    // ────────────────────────────────────────────────────────────────────────

    private function seedAsma(): void
    {
        $this->line('<fg=cyan>▶ Asma ul Husna — 99 Names of Allah...</>');

        $subject = $this->subject(
            'Asma ul Husna', 'أسماء الله الحسنى', 'Asma ul Husna', '99 Names of Allah',
            'o-star',        '#8b5cf6'
        );

        $course  = $this->course($subject, 'Les 99 Noms d\'Allah', 'أسماء الله الحسنى التسعة والتسعون', '99 Names of Allah', 'free');
        $data    = $this->api('/api/asma-ul-husna');
        $names   = $data['data']['names'] ?? [];

        if (empty($names)) {
            $this->line('  <fg=red>✗ Could not fetch Asma ul Husna</>');
            return;
        }

        // 11 modules of 9 names each
        $chunks   = array_chunk($names, 9);
        $modOrder = 1;

        foreach ($chunks as $chunk) {
            $first = $chunk[0]['number'] ?? $modOrder;
            $last  = end($chunk)['number'] ?? ($first + count($chunk) - 1);

            $module = Module::create([
                'course_id'   => $course->id,
                'title'       => "Noms {$first}–{$last}",
                'title_ar'    => "الأسماء {$first}–{$last}",
                'title_fr'    => "Les Noms {$first} à {$last}",
                'order'       => $modOrder++,
            ]);

            $lessonOrder = 1;
            foreach ($chunk as $name) {
                $arabic  = $name['arabic']      ?? '';
                $nameTxt = $name['english']      ?? '';
                $meaning = $name['meaning']     ?? ($name['transliteration'] ?? '');
                $translit = $name['transliteration'] ?? $nameTxt;
                $num     = $name['number']      ?? $lessonOrder;

                Lesson::create([
                    'module_id'       => $module->id,
                    'title'           => "{$num}. {$translit}",
                    'title_ar'        => $arabic,
                    'title_fr'        => "{$num}. {$translit}",
                    'title_en'        => "{$num}. {$nameTxt}",
                    'type'            => 'text',
                    'content'         => "﴿ {$arabic} ﴾\n\n{$translit}\n\n{$meaning}",
                    'duration_minutes' => 3,
                    'is_free_preview' => $lessonOrder === 1,
                    'order'           => $lessonOrder++,
                    'status'          => 'published',
                ]);
            }

            $this->line("  Module noms {$first}–{$last}");
        }

        $this->line('  <fg=green>✓ Asma ul Husna course seeded (' . count($names) . ' names)</>');
    }

    // ────────────────────────────────────────────────────────────────────────

    private function seedDuas(): void
    {
        $this->line('<fg=cyan>▶ Duas — daily supplications...</>');

        $subject = $this->subject(
            'Duas',           'الأدعية',          'Duas',        'Supplications',
            'o-hand-raised',  '#ef4444'
        );

        $course = $this->course($subject, 'Duas Quotidiennes', 'الأدعية اليومية', 'Daily Duas', 'free');
        $data   = $this->api('/api/duas');
        $duas   = $data['data']['duas'] ?? [];

        if (empty($duas)) {
            $this->line('  <fg=red>✗ Could not fetch Duas</>');
            return;
        }

        // Group by category if available, else one module
        $categories = [];
        foreach ($duas as $dua) {
            $cat = $dua['category'] ?? $dua['occasion'] ?? 'Général';
            $categories[$cat][] = $dua;
        }

        $modOrder = 1;
        foreach ($categories as $catName => $catDuas) {
            $module = Module::create([
                'course_id'   => $course->id,
                'title'       => $catName,
                'title_ar'    => $catName,
                'title_fr'    => $catName,
                'order'       => $modOrder++,
            ]);

            $lessonOrder = 1;
            foreach ($catDuas as $dua) {
                $arabic   = $dua['arabic']        ?? '';
                $title    = $dua['title']         ?? ($dua['name'] ?? "Dua #{$lessonOrder}");
                $translat = $dua['translation']   ?? ($dua['english'] ?? '');
                $translit = $dua['transliteration'] ?? '';
                $source   = $dua['source']        ?? ($dua['reference'] ?? '');

                $content = '';
                if ($arabic)   $content .= "﴿ {$arabic} ﴾\n\n";
                if ($translit) $content .= "{$translit}\n\n";
                if ($translat) $content .= "{$translat}\n";
                if ($source)   $content .= "\n— Source: {$source}";

                Lesson::create([
                    'module_id'       => $module->id,
                    'title'           => $title,
                    'title_ar'        => $arabic ? mb_substr($arabic, 0, 60) . '…' : $title,
                    'title_fr'        => $title,
                    'type'            => 'text',
                    'content'         => $content,
                    'duration_minutes' => 5,
                    'is_free_preview' => $lessonOrder === 1,
                    'order'           => $lessonOrder++,
                    'status'          => 'published',
                ]);
            }

            $this->line("  Module [{$catName}]: " . count($catDuas) . " duas");
            if ($modOrder > 8) break; // cap at 7 modules
        }

        $this->line('  <fg=green>✓ Duas course seeded</>');
    }

    // ────────────────────────────────────────────────────────────────────────

    private function seedPrayer(): void
    {
        $this->line('<fg=cyan>▶ Prayer Times & Qibla...</>');

        $subject = $this->subject(
            'Salat & Qibla',  'الصلاة والقبلة',  'Salat & Qibla', 'Prayer & Qibla',
            'o-map-pin',      '#0ea5e9'
        );

        $course = $this->course($subject, 'Horaires de Prière & Qibla', 'مواقيت الصلاة والقبلة', 'Prayer Times & Qibla', 'free');

        // Algiers data
        $ptData = $this->api('/api/prayer-times?latitude=36.7&longitude=3.05');
        $qbData = $this->api('/api/qibla?latitude=36.7&longitude=3.05');

        $pt = $ptData['data']['prayer_times'] ?? [];
        $qb = $qbData['data'] ?? [];

        // Module 1: Prayer times
        $mod1 = Module::create([
            'course_id' => $course->id,
            'title'     => 'Horaires de Prière — Alger',
            'title_ar'  => 'مواقيت الصلاة — الجزائر العاصمة',
            'title_fr'  => 'Horaires de Prière — Alger',
            'order'     => 1,
        ]);

        $prayers = [
            ['Fajr',    'الفجر',    $pt['fajr']    ?? '--:--', 'La prière de l\'aube, avant le lever du soleil.'],
            ['Dhuhr',   'الظهر',    $pt['dhuhr']   ?? '--:--', 'La prière de midi, après le zénith du soleil.'],
            ['Asr',     'العصر',    $pt['asr']     ?? '--:--', 'La prière de l\'après-midi.'],
            ['Maghrib', 'المغرب',   $pt['maghrib'] ?? '--:--', 'La prière du coucher du soleil.'],
            ['Isha',    'العشاء',   $pt['isha']    ?? '--:--', 'La prière du soir.'],
        ];

        foreach ($prayers as $i => [$name, $nameAr, $time, $desc]) {
            Lesson::create([
                'module_id'       => $mod1->id,
                'title'           => "{$name} — {$time}",
                'title_ar'        => "{$nameAr} — {$time}",
                'title_fr'        => "{$name} — {$time}",
                'type'            => 'text',
                'content'         => "Heure: {$time}\n\n{$desc}",
                'duration_minutes' => 10,
                'is_free_preview' => $i === 0,
                'order'           => $i + 1,
                'status'          => 'published',
            ]);
        }

        // Module 2: Qibla
        $mod2 = Module::create([
            'course_id' => $course->id,
            'title'     => 'Direction de la Qibla',
            'title_ar'  => 'اتجاه القبلة',
            'title_fr'  => 'Direction de la Qibla',
            'order'     => 2,
        ]);

        $direction = $qb['direction'] ?? null;
        $compass   = $qb['compass_direction'] ?? '';
        Lesson::create([
            'module_id'       => $mod2->id,
            'title'           => 'Qibla depuis Alger',
            'title_ar'        => 'القبلة من الجزائر العاصمة',
            'title_fr'        => 'Qibla depuis Alger',
            'type'            => 'text',
            'content'         => "Direction: " . ($direction ? round($direction, 2) . "° ({$compass})" : 'N/A') . "\n\nLa Qibla est la direction vers la Kaaba à La Mecque.",
            'duration_minutes' => 5,
            'is_free_preview' => true,
            'order'           => 1,
            'status'          => 'published',
        ]);

        $this->line("  <fg=green>✓ Prayer & Qibla course seeded</>");
    }

    // ────────────────────────────────────────────────────────────────────────

    private function enrollAllStudents(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>▶ Enrolling all students in new Islamic courses...</>');

        $newCourses = Course::whereHas('subject', fn($q) =>
            $q->whereIn('name', $this->islamicSubjectNames())
        )->get();

        $students = Student::all();
        $enrolled = 0;

        foreach ($students as $student) {
            foreach ($newCourses as $course) {
                \App\Models\Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'course_id' => $course->id],
                    ['enrolled_at' => now(), 'progress_percent' => 0, 'status' => 'active']
                );
                $enrolled++;
            }
        }

        $this->line("  <fg=green>✓ {$enrolled} enrollments created for {$students->count()} students in {$newCourses->count()} courses</>");
    }

    // ────────────────────────────────────────────────────────────────────────

    private function subject(string $name, string $ar, string $fr, string $en, string $icon, string $color): Subject
    {
        return Subject::firstOrCreate(
            ['name' => $name],
            ['grade_id' => null, 'name_ar' => $ar, 'name_fr' => $fr, 'name_en' => $en, 'icon' => $icon, 'color' => $color, 'coefficient' => 1]
        );
    }

    private function course(Subject $subject, string $titleFr, string $titleAr, string $titleEn, string $type): Course
    {
        return Course::create([
            'subject_id'       => $subject->id,
            'teacher_id'       => $this->teacher->id,
            'academic_year_id' => $this->year?->id,
            'title'            => $titleFr,
            'title_fr'         => $titleFr,
            'title_ar'         => $titleAr,
            'title_en'         => $titleEn,
            'description'      => "Cours basé sur UmmahAPI — contenu islamique authentique.",
            'description_fr'   => "Cours basé sur UmmahAPI — contenu islamique authentique.",
            'description_ar'   => "مقرر مبني على محتوى إسلامي أصيل من UmmahAPI.",
            'status'           => 'published',
            'type'             => $type,
            'price'            => 0,
            'duration_hours'   => 0,
        ]);
    }

    private function api(string $path): ?array
    {
        try {
            $resp = Http::timeout(15)->withHeaders($this->headers)->get(self::BASE . $path);
            if ($resp->successful() && !empty($resp->json('success'))) {
                return $resp->json('data') !== null ? ['data' => $resp->json('data')] + $resp->json() : $resp->json();
            }
        } catch (\Exception $e) {
            $this->line("  <fg=red>API error {$path}: " . substr($e->getMessage(), 0, 50) . "</>");
        }
        return null;
    }

    private function islamicSubjectNames(): array
    {
        return ['Coran', 'Hadith', 'Asma ul Husna', 'Duas', 'Salat & Qibla'];
    }
}
