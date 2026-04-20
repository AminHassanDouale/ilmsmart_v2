<?php

namespace App\Console\Commands;

use App\Models\{User, Student, Course, Enrollment, Lesson, AcademicYear, Grade, LessonProgress};
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SetupIndividuAndTestApis extends Command
{
    protected $signature   = 'setup:individu-test {--email=sara@edu.dz : Student email to use}';
    protected $description = 'Enroll student in all courses, test all internal + UmmahAPI endpoints';

    const UMMAH_KEY  = 'umh_bb202cadd297cea98cd05e37e4ff0d1d4c1fe4a2';
    const UMMAH_BASE = 'https://ummahapi.com';

    public function handle(): int
    {
        $email = $this->option('email');

        $this->newLine();
        $this->line('<fg=cyan>╔══════════════════════════════════════════════╗</>');
        $this->line('<fg=cyan>║   INDIVIDU API TEST SUITE                    ║</>');
        $this->line("<fg=cyan>║   Student: {$email}</>   ");
        $this->line('<fg=cyan>╚══════════════════════════════════════════════╝</>');
        $this->newLine();

        // ─── 1. Load student ───────────────────────────────────────────────
        $this->line('<fg=yellow>▶ Step 1 — Load student</>');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->line("  <fg=red>✗ User {$email} not found. Run seeders first.</>");
            return self::FAILURE;
        }

        $student = $user->student;
        if (!$student) {
            // Create student record if missing
            $student = Student::create([
                'user_id'          => $user->id,
                'student_number'   => 'STU-AUTO-' . $user->id,
                'birth_date'       => '2000-01-01',
                'gender'           => 'male',
                'student_type'     => 'individual',
                'grade_id'         => Grade::first()?->id,
                'academic_year_id' => AcademicYear::where('is_current', true)->first()?->id,
            ]);
            $this->line("  Created missing student record #{$student->id}");
        }

        $this->line("  User    : #{$user->id} — {$user->name} ({$user->email})");
        $this->line("  Student : #{$student->id} — type={$student->student_type}");
        $this->newLine();

        // ─── 2. Enroll in ALL courses ──────────────────────────────────────
        $this->line('<fg=yellow>▶ Step 2 — Enroll in all courses</>');

        $courses  = Course::all();
        $enrolled = 0;
        $skipped  = 0;

        foreach ($courses as $course) {
            if (Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists()) {
                $skipped++;
                continue;
            }
            Enrollment::create([
                'student_id'       => $student->id,
                'course_id'        => $course->id,
                'enrolled_at'      => now(),
                'progress_percent' => 0,
                'status'           => 'active',
            ]);
            $enrolled++;
            $this->line("  <fg=green>+</> Enrolled in: {$course->title_fr}");
        }

        $this->line("  Total: {$courses->count()} courses | New: <fg=green>{$enrolled}</> | Skipped: {$skipped}");
        $this->newLine();

        // ─── 3. Internal API Tests ─────────────────────────────────────────
        $this->line('<fg=yellow>══════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  INTERNAL LARAVEL API                           </>');
        $this->line('<fg=yellow>══════════════════════════════════════════════════</>');

        Auth::login($user);
        $cid     = $courses->first()?->id ?? 1;
        $lid     = Lesson::whereHas('module', fn($q) => $q->where('course_id', $cid))->first()?->id ?? 1;

        $internal = [
            ['GET', '/api/user',                             'Auth — current user'],
            ['GET', '/api/courses',                          'Courses — list'],
            ['GET', "/api/courses/{$cid}",                   "Courses — show #{$cid}"],
            ['GET', "/api/courses/{$cid}/lessons",           'Courses — all lessons'],
            ['GET', "/api/courses/{$cid}/progress",          'Courses — my progress'],
            ['GET', "/api/lessons/{$lid}",                   "Lesson — show #{$lid}"],
            ['POST',"/api/lessons/{$lid}/complete",          "Lesson — mark complete #{$lid}"],
            ['GET', '/api/progress',                         'Progress — overview'],
            ['GET', "/api/progress/{$cid}",                  'Progress — course detail'],
            ['GET', '/api/notifications',                    'Notifications'],
            ['GET', '/api/conversations',                    'Messages — conversations'],
            ['GET', '/api/live-classes',                     'Live classes'],
            ['GET', '/api/payments',                         'Payments'],
            ['GET', '/api/schedule',                         'Schedule'],
            ['GET', '/api/quizzes',                          'Quizzes'],
            ['GET', '/api/assignments',                      'Assignments'],
            ['POST',"/api/courses/{$cid}/enroll",            'Courses — enroll (re-enroll)'],
        ];

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $iPass  = 0;
        $iFail  = 0;

        foreach ($internal as [$method, $uri, $label]) {
            try {
                $request = Request::create($uri, $method, [], [], [], [
                    'HTTP_ACCEPT' => 'application/json',
                ]);
                $request->setUserResolver(fn() => $user);
                Auth::setUser($user);

                $response = $kernel->handle($request);
                $status   = $response->getStatusCode();

                if ($status >= 200 && $status < 300) {
                    $body    = json_decode($response->getContent(), true);
                    $preview = $this->internalPreview($body);
                    $this->line("  <fg=green>✓</> {$label} — <fg=green>HTTP {$status}{$preview}</>");
                    $iPass++;
                } elseif ($status === 422) {
                    $this->line("  <fg=yellow>⚠</> {$label} — <fg=yellow>HTTP {$status} (validation)</>");
                    $iPass++;
                } else {
                    $msg = trim(substr(strip_tags($response->getContent()), 0, 80));
                    $this->line("  <fg=red>✗</> {$label} — <fg=red>HTTP {$status}: {$msg}</>");
                    $iFail++;
                }
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> {$label} — <fg=red>" . substr($e->getMessage(), 0, 70) . "</>");
                $iFail++;
            }
        }

        // ─── 4. Web routes check ───────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=yellow>══════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  WEB ROUTES (student pages)                     </>');
        $this->line('<fg=yellow>══════════════════════════════════════════════════</>');

        $webRoutes = [
            ["/student/courses",                     'Student courses list'],
            ["/student/courses/{$cid}",              "Course show #{$cid}"],
            ["/student/courses/{$cid}/lessons/{$lid}", "Lesson viewer #{$lid}"],
            ["/student/progress",                    'Student progress'],
            ["/dashboard",                           'Dashboard'],
        ];

        $wPass = 0;
        $wFail = 0;

        foreach ($webRoutes as [$uri, $label]) {
            try {
                $request = Request::create($uri, 'GET', [], [], [], [
                    'HTTP_ACCEPT' => 'text/html',
                ]);
                $request->setUserResolver(fn() => $user);
                Auth::setUser($user);

                $response = $kernel->handle($request);
                $status   = $response->getStatusCode();

                if ($status >= 200 && $status < 400) {
                    $this->line("  <fg=green>✓</> {$label} — <fg=green>HTTP {$status}</>");
                    $wPass++;
                } else {
                    $msg = trim(substr(strip_tags($response->getContent()), 0, 60));
                    $this->line("  <fg=red>✗</> {$label} — <fg=red>HTTP {$status}: {$msg}</>");
                    $wFail++;
                }
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> {$label} — <fg=red>" . substr($e->getMessage(), 0, 70) . "</>");
                $wFail++;
            }
        }

        // ─── 5. UmmahAPI tests ─────────────────────────────────────────────
        $this->newLine();
        $this->line('<fg=magenta>══════════════════════════════════════════════════</>');
        $this->line('<fg=magenta>  UMMAHAPI — Islamic Content                      </>');
        $this->line('<fg=magenta>══════════════════════════════════════════════════</>');

        $uHdrs = ['X-API-Key' => self::UMMAH_KEY, 'Accept' => 'application/json'];
        $ummah = [
            ['/api/hijri-date',                                'Hijri Date'],
            ['/api/asma-ul-husna',                             'Asma ul Husna (99 Names)'],
            ['/api/duas',                                      'Duas'],
            ['/api/hadith/bukhari?page=1&per_page=1',          'Hadith — Bukhari'],
            ['/api/quran/surah/1',                             'Quran — Al-Fatiha (7 verses)'],
            ['/api/quran/surah/36',                            'Quran — Ya-Sin (83 verses)'],
            ['/api/quran/surah/67',                            'Quran — Al-Mulk (30 verses)'],
            ['/api/quran/search?q=bismillah&limit=3',          'Quran — Search "bismillah"'],
            ['/api/prayer-times?latitude=36.7&longitude=3.05', 'Prayer Times — Algiers'],
            ['/api/qibla?latitude=36.7&longitude=3.05',        'Qibla Direction — Algiers'],
        ];

        $uPass = 0;
        $uFail = 0;

        foreach ($ummah as [$path, $label]) {
            try {
                $resp = Http::timeout(12)->withHeaders($uHdrs)->get(self::UMMAH_BASE . $path);
                $data = $resp->json();
                if ($resp->status() === 200 && !empty($data['success'])) {
                    $preview = $this->ummahPreview($data);
                    $this->line("  <fg=green>✓</> {$label} — <fg=green>{$preview}</>");
                    $uPass++;
                } else {
                    $this->line("  <fg=red>✗</> {$label} — <fg=red>HTTP {$resp->status()}</>");
                    $uFail++;
                }
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> {$label} — <fg=red>" . substr($e->getMessage(), 0, 50) . "</>");
                $uFail++;
            }
        }

        // ─── Summary ───────────────────────────────────────────────────────
        $this->newLine();
        $total = $iPass + $iFail + $wPass + $wFail + $uPass + $uFail;
        $pass  = $iPass + $wPass + $uPass;
        $fail  = $iFail + $wFail + $uFail;

        $this->line('──────────────────────────────────────────────────');
        $this->line("  Internal API  : <fg=green>{$iPass} ✓</> / <fg=red>{$iFail} ✗</>");
        $this->line("  Web pages     : <fg=green>{$wPass} ✓</> / <fg=red>{$wFail} ✗</>");
        $this->line("  UmmahAPI      : <fg=green>{$uPass} ✓</> / <fg=red>{$uFail} ✗</>");
        $this->line("  <fg=cyan>TOTAL         : {$pass}/{$total} passed</>");
        $this->newLine();
        $this->line('<fg=cyan>╔══════════════════════════════════╗</>');
        $this->line('<fg=cyan>║  LOGIN AS THIS STUDENT           ║</>');
        $this->line('<fg=cyan>╠══════════════════════════════════╣</>');
        $this->line("<fg=cyan>║</> Email    : {$email}      ");
        $this->line('<fg=cyan>║</> Password : password           ');
        $this->line("<fg=cyan>║</> Courses  : {$courses->count()} enrolled       ");
        $this->line("<fg=cyan>║</> URL      : http://127.0.0.1:8000/student/courses/{$cid}/lessons/{$lid}");
        $this->line('<fg=cyan>╚══════════════════════════════════╝</>');
        $this->newLine();

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function internalPreview(?array $body): string
    {
        if (!$body) return '';
        if (isset($body['id']))    return ' — id #' . $body['id'];
        if (isset($body['data']) && is_array($body['data'])) {
            $cnt = isset($body['data'][0]) ? count($body['data']) : 1;
            return " — {$cnt} record(s)";
        }
        if (isset($body['total']))   return ' — total: ' . $body['total'];
        if (isset($body['message'])) return ' — ' . $body['message'];
        return '';
    }

    private function ummahPreview(array $data): string
    {
        $d = $data['data'] ?? [];
        if (isset($d['date']))                 return $d['date'];
        if (isset($d['surah']['number']))      return 'Surah #' . $d['surah']['number'] . ', ' . count($d['verses'] ?? []) . ' verses';
        if (isset($d['prayer_times']['fajr'])) return 'Fajr=' . $d['prayer_times']['fajr'] . ' / Dhuhr=' . $d['prayer_times']['dhuhr'];
        if (isset($d['direction']))            return 'Direction=' . round($d['direction'], 1) . '°';
        if (isset($d[0]['name']))              return $d[0]['name'] . ' (' . count($d) . ' items)';
        if (isset($d[0]['arabic']))            return mb_substr($d[0]['arabic'], 0, 30) . '… (' . count($d) . ')';
        if (isset($d['results']))              return count($d['results']) . ' results';
        return 'OK';
    }
}
