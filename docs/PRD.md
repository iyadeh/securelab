# Product Requirements Document — SecureLab

**Product Name:** SecureLab  
**Product Type:** Interactive Web Security Learning Platform  
**Version:** 1.0 — MVP  
**Status:** Draft  
**Primary Platform:** Web  
**Target Users:** Software developers, computer science students, junior security engineers, dan pengguna yang ingin mempelajari web security secara praktis.

---

# 1. Product Overview

SecureLab adalah platform pembelajaran web security interaktif yang membantu pengguna memahami vulnerability web melalui kombinasi materi teori, vulnerable-code examples, simulated security labs, attack-flow visualization, remediation guidance, dan progress tracking.

SecureLab tidak hanya mengajarkan bagaimana vulnerability dapat dieksploitasi, tetapi juga menjelaskan:

- bagaimana vulnerability muncul,
- bagaimana data mengalir melalui aplikasi,
- apa root cause-nya,
- apa dampaknya,
- dan bagaimana vulnerability tersebut diperbaiki secara benar.

Learning loop utama SecureLab:

**Learn → Understand → Exploit → Analyze → Fix**

Versi pertama menggunakan controlled simulation sehingga tidak menjalankan arbitrary SQL, shell command, script, atau vulnerable Docker environment secara nyata.

Arsitektur akan dibuat agar pada versi berikutnya simulated lab dapat diganti atau dikombinasikan dengan isolated Docker lab.

---

# 2. Problem Statement

Materi web security sering kali memiliki salah satu dari dua masalah:

1. Terlalu teoritis sehingga pengguna memahami definisi vulnerability tetapi tidak memahami bagaimana vulnerability bekerja dalam aplikasi nyata.
2. Terlalu berorientasi exploitation sehingga pengguna belajar payload tanpa memahami root cause dan secure implementation.

Developer juga sering mengalami kesulitan menghubungkan konsep seperti:

- HTTP request,
- backend processing,
- database query,
- authorization decision,
- browser behavior,
- dan security vulnerability.

SecureLab mencoba menjembatani gap tersebut dengan membuat proses serangan dapat dipahami secara visual dan interaktif.

---

# 3. Product Vision

Membangun platform pembelajaran web security yang membantu developer memahami vulnerability dari perspektif attacker sekaligus defender.

SecureLab harus memberikan pengalaman:

**“Saya tidak hanya mengetahui payload-nya, tetapi saya memahami mengapa vulnerability tersebut terjadi dan bagaimana memperbaikinya.”**

---

# 4. Goals

## 4.1 Primary Goals

SecureLab v1 harus memungkinkan pengguna untuk:

- membuat akun dan login,
- memilih learning path,
- membaca lesson,
- melihat vulnerable-code example,
- menjalankan simulated lab,
- mengirim input ke lab,
- melihat simulated HTTP/application/database behavior,
- melihat attack flow,
- menyelesaikan challenge,
- memahami root cause vulnerability,
- melihat secure implementation,
- dan melacak progress belajar.

## 4.2 Engineering Goals

SecureLab harus:

- menggunakan modular monolith architecture,
- mempunyai separation of concerns yang jelas,
- menjaga lab simulation terpisah dari core platform,
- tidak menjalankan arbitrary untrusted code,
- memiliki abstraction untuk Lab Runtime,
- dapat berkembang ke Docker-based lab pada v2,
- menggunakan PostgreSQL sebagai persistent storage,
- dan memiliki automated testing untuk business logic utama.

---

# 5. Non-Goals — V1

Fitur berikut bukan bagian dari MVP:

- real vulnerable Docker containers,
- Kubernetes,
- microservices,
- multiplayer labs,
- AI tutor,
- public leaderboard,
- user-generated labs,
- marketplace,
- realtime collaboration,
- certificates,
- payment/subscription,
- mobile native application,
- community forum,
- arbitrary terminal execution,
- arbitrary SQL execution,
- dan arbitrary external HTTP requests.

Fitur tersebut dapat dipertimbangkan setelah core learning experience stabil.

---

# 6. Target Users

## Persona A — Developer Learning Security

Developer frontend/backend yang ingin memahami vulnerability web agar dapat menulis software yang lebih aman.

### Goals

- memahami OWASP-style vulnerabilities,
- belajar secure coding,
- memahami exploit dari perspektif developer.

---

## Persona B — Computer Science Student

Mahasiswa atau self-taught learner yang baru mempelajari application security.

### Goals

- mendapatkan structured learning path,
- memahami fundamental web security,
- mencoba challenge tanpa harus menyiapkan environment sendiri.

---

## Persona C — Junior Security Engineer

Pengguna yang sudah memahami dasar web development dan ingin memperkuat kemampuan application security.

### Goals

- memahami root cause vulnerability,
- mempraktikkan attack reasoning,
- mempelajari remediation.

---

# 7. Product Principles

## 7.1 Educational First

Setiap vulnerability harus menjawab:

- What happened?
- Why did it happen?
- How was it exploited?
- What was the impact?
- How should it be fixed?

## 7.2 Safe by Design

Lab v1 harus menggunakan controlled simulation.

Tidak boleh ada:

- `eval()` terhadap input pengguna,
- arbitrary shell execution,
- arbitrary SQL execution,
- arbitrary PHP execution,
- arbitrary browser script execution di origin platform,
- direct Docker socket access,
- atau unrestricted outbound network access.

## 7.3 Attacker + Defender Perspective

Setiap lab harus berakhir dengan remediation, bukan hanya exploitation.

## 7.4 Visual Explanation

Complex security behavior harus divisualisasikan bila memungkinkan.

Contoh:

**User Input → HTTP Request → Application → Database → Response**

## 7.5 Progressive Complexity

Konten harus berkembang dari fundamental menuju advanced concept.

---

# 8. MVP Scope

SecureLab v1 memiliki area utama:

1. Authentication
2. Dashboard
3. Learning Paths
4. Lessons
5. Labs Catalog
6. Simulated Lab Workspace
7. Attack Visualization
8. Solution / Remediation
9. Progress Tracking
10. Achievements
11. Profile

---

# 9. Information Architecture

Main navigation:

**Overview**

- Dashboard

**Learn**

- Learning Paths
- Labs

**You**

- Progress
- Achievements
- Profile

Secondary:

- Settings
- Logout

---

# 10. User Flow

## 10.1 New User Flow

Register  
↓  
Dashboard  
↓  
Select Learning Path  
↓  
Open Lesson  
↓  
Read Material  
↓  
Start Lab  
↓  
Experiment  
↓  
Complete Objective  
↓  
Analyze Attack Flow  
↓  
Read Secure Solution  
↓  
Progress Updated

---

# 11. Functional Requirements

# FR-01 Authentication

User harus dapat:

- register,
- login,
- logout,
- dan mempertahankan authenticated session.

### Registration fields

- name,
- email,
- password,
- password confirmation.

### Acceptance Criteria

- email harus unik,
- password disimpan menggunakan secure hashing Laravel,
- guest tidak dapat membuka authenticated application pages,
- authenticated user tidak diarahkan kembali ke login tanpa alasan.

---

# FR-02 Dashboard

Dashboard harus menampilkan:

- greeting,
- current learning progress,
- continue learning,
- lesson completed count,
- lab completed count,
- learning paths,
- recent activity,
- current streak jika tersedia.

### Acceptance Criteria

User dapat melanjutkan lesson terakhir langsung dari dashboard.

---

# FR-03 Learning Paths

User harus dapat melihat semua learning paths.

Kurikulum awal V1 dikonsolidasikan ke dalam 1 canonical learning path:

- **Web Security Fundamentals** (Easy) — mencakup 3 modul tematik: Injection, Cross-Site Scripting, dan Access & Account Security.

Skema database dan model tetap dirancang untuk mendukung penambahan learning path baru di masa mendatang.

Setiap path menampilkan:

- title,
- description,
- number of modules,
- number of lessons,
- difficulty,
- completion percentage,
- state (not_started, in_progress, completed),
- next lesson link.

---

# FR-04 Learning Path Detail

Learning path detail harus menampilkan modules dan pelajaran (lessons) secara berurutan.

Modul awal pada path Web Security Fundamentals:

1. **Injection** (SQL Injection Authentication Bypass)
2. **Cross-Site Scripting** (Reflected XSS, Stored XSS)
3. **Access & Account Security** (IDOR, Weak Password Reset)

Module dan Lesson memiliki state yang diderivasi secara terpusat:

- not_started,
- in_progress,
- completed.

---

# FR-05 Lessons

Lesson harus mendukung content blocks seperti:

- heading,
- paragraph,
- code example,
- warning,
- note,
- diagram,
- vulnerable example,
- secure example,
- remediation,
- link to lab.

Lesson harus mencatat:

- started state,
- completion state,
- completion timestamp.

---

# FR-06 Lab Catalog

User dapat membuka `/labs` dan melihat lab yang tersedia.

Setiap lab menampilkan:

- title,
- description,
- vulnerability category,
- difficulty,
- status,
- progress.

Filter minimal:

- All
- Easy
- Medium
- Hard

Category filtering dapat ditambahkan apabila tidak memperumit MVP.

---

# FR-07 Lab Progress & Ephemeral Simulation

V1 menerapkan pendekatan **stateless & ephemeral simulation** untuk menghindari beban I/O database dari log percobaan sementara:

- **Lab Progress**: Progres kelulusan dan aktivitas user dicatat secara eksplisit pada tabel `lab_progress` (`started_at`, `completed_at`) melalui aksi `POST /labs/{lab}/start` dan `POST /labs/{lab}/complete`.
- **Ephemeral Simulation**: Simulasi dijalankan secara instan tanpa menyimpan rekaman percobaan ke database. Hasil simulasi diteruskan ke workspace melalui flash session Inertia (`session('simulation_result')`).
- State persisten lab untuk setiap user:
    - `not_started`,
    - `in_progress`,
    - `completed`.

Abstraksi `LabRuntime` disiapkan agar pada V2 dapat diintegrasikan dengan runtime berbasis container tanpa mengubah arsitektur progress inti.

---

# FR-08 Simulated Lab Workspace

Lab workspace (`GET /labs/{lab}/workspace`) menyajikan lingkungan interaktif terpadu yang mencakup:

## Objective Panel

Menampilkan instruksi dan sasaran edukasi challenge.

## Target Application

Menampilkan antarmuka aplikasi simulasi (misal: formulir login, pencarian, profil pengguna, atau komentar).

## Simulation Form

Input field sintetis yang terikat pada aturan validasi ketat per skenario.

## Attack Flow (Simulation Trace)

Visualisasi terstruktur tahapan aliran data sintetis melalui sistem:

- **Input**: Data yang diserahkan learner.
- **Application**: Pemrosesan aplikasi terhadap input.
- **Decision**: Keputusan keamanan logis (apakah kondisi vulnerable tercapai).
- **Output**: Respons aplikasi simulasi.

## Analysis & Remediation (Integrated Guidance)

Panduan edukasi mendalam yang tampil di dalam workspace setelah simulasi dijalankan:

- Root cause vulnerability,
- Dampak keamanan,
- Rekomendasi remediasi,
- Perbandingan kode **Vulnerable vs Secure** secara berdampingan.

---

# FR-09 Lab Execution

User mengirim input melalui workspace:

Request masuk ke:

**LabSimulationController → LabRuntime (SimulationLabRuntime) → LabSimulator**

V1 menggunakan implementasi simulator:

- `SqlInjectionAuthenticationBypassSimulator`
- `ReflectedXssSimulator`
- `StoredXssSimulator`
- `IdorSimulator`
- `WeakPasswordResetSimulator`

Simulator tidak menjalankan payload secara nyata pada SQL engine, sistem shell, atau browser runtime. Simulator mengevaluasi kondisi input secara deterministik dan mengembalikan `SimulationResult` dengan outcome (`normal_behavior`, `vulnerable_behavior`, atau `rejected`).

---

# FR-10 Simulation Trace Steps

Setiap eksekusi simulasi menghasilkan normalized trace steps:

- `input`,
- `application`,
- `decision`,
- `output`.

Frontend menggunakan steps tersebut untuk visualisasi alur serangan yang informatif tanpa bergantung pada implementasi backend mentah.

---

# FR-11 Lab Completion

Penyelesaian lab dilakukan melalui aksi eksplisit:

- User menandai lab selesai melalui `POST /labs/{lab}/complete`,
- Database mencatat `completed_at` pada `lab_progress`,
- `AchievementService` secara otomatis mengevaluasi dan memberikan achievement yang memenuhi syarat,
- Halaman workspace tetap dapat diakses untuk ditinjau (_available for review_).

User tidak boleh mendapatkan arbitrary execution capability setelah menyelesaikan challenge.

---

# FR-12 Remediation Guidance

Panduan remediasi disajikan langsung di dalam workspace lab (`SimulationAnalysis`) setelah simulasi dijalankan:

- Nama vulnerability dan kategori kontrol keamanan,
- Ringkasan hasil simulasi,
- Root cause,
- Potensi dampak,
- Langkah remediasi bertahap,
- Perbandingan pola kode: **Vulnerable Implementation vs. Secure Implementation**,
- Penjelasan mengapa perbaikan tersebut efektif.

---

# FR-13 Progress

Progress page harus menunjukkan:

- overall completion,
- lessons completed,
- labs completed,
- challenge statistics,
- skill progress,
- recent learning activity.

Skill categories awal:

- HTTP & Protocols
- Authentication
- Injection
- Access Control
- Client-Side Security

---

# FR-14 Achievements

V1 menetapkan 5 initial achievements definitif yang dievaluasi secara server-side melalui `AchievementService`:

### First Lesson

Complete your first SecureLab lesson.

### First Blood

Complete your first SecureLab guided lab.

### Path Complete

Complete every lesson in a SecureLab learning path.

### Lab Series Complete

Complete every currently available SecureLab lab.

### Security Foundations Complete

Complete all available SecureLab lessons and labs.

Achievements bersifat educational/gamification ringan dan divalidasi langsung dari progres riil kurikulum.

---

# FR-15 Profile

Profile (`GET /profile`) menampilkan identitas pembelajar dan ringkasan pencapaian riil:

- name,
- email,
- joined date,
- total & completed lessons,
- total & completed labs,
- unlocked achievements summary,
- progress breakdown per learning path.

---

# 12. Initial Lab Content

SecureLab v1 harus memiliki minimal lima labs.

## LAB-01 SQL Injection Basics

**Difficulty:** Easy  
**Scenario:** Authentication bypass  
**Concepts:** SQL Injection, authentication, database queries

Learning outcome:

- memahami unsafe query construction,
- memahami SQL manipulation,
- memahami parameterized query.

---

## LAB-02 Reflected XSS

**Difficulty:** Easy  
**Scenario:** Search result reflection  
**Concepts:** browser rendering, untrusted input, output encoding

Learning outcome:

- memahami reflected input,
- memahami browser interpretation,
- memahami escaping/output encoding.

---

## LAB-03 Broken Access Control / IDOR

**Difficulty:** Easy  
**Scenario:** User profile access  
**Concepts:** authorization, object ownership

Learning outcome:

- memahami authentication vs authorization,
- memahami object-level access control.

---

## LAB-04 Weak Password Reset

**Difficulty:** Medium  
**Scenario:** Predictable or improperly validated reset process

Learning outcome:

- memahami insecure recovery workflows,
- memahami secure password-reset design.

---

## LAB-05 Stored XSS

**Difficulty:** Medium  
**Scenario:** Comment system

Learning outcome:

- memahami persistence of malicious input,
- memahami output encoding dan sanitization boundaries.

---

# 13. Technical Architecture

## V1 Architecture

Browser  
↓  
React 19 + Inertia v3  
↓  
Laravel 13  
↓  
PostgreSQL (Testing: SQLite in-memory)

Lab subsystem:

Laravel Controller  
↓  
LabRuntime Contract  
↓  
SimulationLabRuntime  
↓  
Scenario Simulators

---

# 14. Technology Stack

## Backend

- Laravel 13 (PHP 8.4)
- Laravel Fortify (authentication & email verification)
- Laravel Wayfinder (typed TypeScript route generation)
- Eloquent ORM

## Frontend

- Inertia.js v3
- React 19
- TypeScript 5.7
- Tailwind CSS v4 via `@tailwindcss/vite`
- Radix UI & Lucide React

## Database

- PostgreSQL (local development & production)
- SQLite in-memory (automated tests & CI)

## Testing & Quality

- Pest PHP (Feature and Unit tests)
- Larastan / PHPStan (Level 7)
- Laravel Pint
- TypeScript compiler (`tsc --noEmit`)

## V2 Candidates

- Redis
- Laravel Queue
- Docker
- dedicated Lab Orchestrator

---

# 15. Application Architecture

Laravel menggunakan modular monolith dengan pemisahan service layer yang bersih.

Struktur aplikasi:

```text
app/
├── Actions/
│   └── Fortify/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Providers/
└── Services/
    ├── AchievementService.php
    ├── LearningProgressService.php
    └── Labs/
        ├── Contracts/
        │   ├── LabRuntime.php
        │   └── LabSimulator.php
        ├── SimulationLabRuntime.php
        ├── SimulationResult.php
        ├── SimulationOutcome.php
        ├── LabGuidanceProvider.php
        └── Simulators/
            ├── SqlInjectionAuthenticationBypassSimulator.php
            ├── ReflectedXssSimulator.php
            ├── StoredXssSimulator.php
            ├── IdorSimulator.php
            └── WeakPasswordResetSimulator.php
```

---

# 16. Frontend Architecture

```text
resources/js/
├── components/
│   ├── ui/
│   ├── learning/
│   ├── labs/
│   └── progress/
├── layouts/
├── pages/
│   ├── Dashboard.tsx
│   ├── Learning/
│   ├── Labs/
│   ├── Progress/
│   └── Profile/
└── types/
```

---

# 17. Data Model

Core entities:

- User
- LearningPath
- LearningModule
- Lesson
- LessonProgress
- Lab
- LabProgress
- Achievement
- UserAchievement

Relationships:

```text
LearningPath
 └── LearningModules
      └── Lessons ──[optional]──► Lab

Lab
 └── Lessons

User
 ├── LessonProgress
 ├── LabProgress
 └── UserAchievements
```

---

# 18. Initial Database Tables

## users

- id
- name
- email
- email_verified_at
- password
- two_factor_secret
- two_factor_recovery_codes
- two_factor_confirmed_at
- remember_token
- created_at
- updated_at

## learning_paths

- id
- slug
- title
- description
- difficulty
- created_at
- updated_at

## learning_modules

- id
- learning_path_id
- title
- description
- position
- created_at
- updated_at

## lessons

- id
- learning_module_id
- lab_id (nullable)
- slug
- title
- summary
- content
- estimated_minutes
- position
- created_at
- updated_at

## lesson_progress

- id
- user_id
- lesson_id
- started_at
- completed_at
- created_at
- updated_at

## labs

- id
- slug
- title
- description
- vulnerability_type
- difficulty
- scenario
- created_at
- updated_at

## lab_progress

- id
- user_id
- lab_id
- started_at
- completed_at
- created_at
- updated_at

## achievements

- id
- slug
- title
- description
- category
- position
- created_at
- updated_at

## user_achievements

- id
- user_id
- achievement_id
- unlocked_at
- created_at
- updated_at

---

# 19. Routes

```text
# Authentication (Laravel Fortify)
GET   /login
POST  /login
POST  /logout
GET   /register
POST  /register
GET   /email/verify
...

# Learner Overview
GET   /dashboard

# Learning Experience
GET   /learn
GET   /learn/{learningPath:slug}
GET   /learn/{learningPath:slug}/{lesson:slug}
POST  /learn/{learningPath:slug}/{lesson:slug}/start
POST  /learn/{learningPath:slug}/{lesson:slug}/complete

# Labs Experience & Simulation
GET   /labs
GET   /labs/{lab:slug}
GET   /labs/{lab:slug}/workspace
POST  /labs/{lab:slug}/simulate
POST  /labs/{lab:slug}/start
POST  /labs/{lab:slug}/complete

# Progress, Achievements & Profile
GET   /progress
GET   /achievements
GET   /profile

# Settings
GET   /settings/profile
PATCH /settings/profile
DELETE /settings/profile
GET   /settings/security
PUT   /settings/password
GET   /settings/appearance
```

---

# 20. Security Requirements

## SEC-01

Platform database tidak boleh digunakan sebagai vulnerable lab database.

## SEC-02

Input lab tidak boleh dieksekusi sebagai arbitrary SQL.

## SEC-03

Input lab tidak boleh dieksekusi melalui shell.

## SEC-04

User input tidak boleh diberikan ke PHP `eval()`.

## SEC-05

Lab simulation tidak boleh melakukan arbitrary external network request.

## SEC-06

Authorization dan session ownership diterapkan agar progress dan data otentikasi user terisolasi dengan aman.

## SEC-07

All state-changing requests harus dilindungi menggunakan Laravel CSRF protection.

## SEC-08

Mass assignment harus dikontrol.

## SEC-09

Sensitive information tidak boleh masuk ke application logs.

## SEC-10

Lab execution harus mempunyai rate limiting.

## SEC-11

Content rendering harus aman terhadap XSS di platform SecureLab sendiri.

## SEC-12

Achievement, score, progress, dan lab completion tidak boleh dipercaya langsung dari frontend.

Server menjadi source of truth.

---

# 21. Scalability Requirements

V1 harus menyediakan `LabRuntime` abstraction.

```text
LabRuntime
├── definition(Lab $lab): ?array
├── validationRules(Lab $lab): array
└── simulate(Lab $lab, array $input): ?SimulationResult
```

V1 implementation:

`SimulationLabRuntime`

V2 implementation:

`DockerLabRuntime`

Business logic dan UI tidak boleh bergantung secara langsung pada Docker.

---

# 22. V2 Architecture Target

V2 dapat berkembang menjadi:

```text
Browser
   ↓
Laravel
   │
   ├── PostgreSQL
   │
   └── Queue
          ↓
      Lab Orchestrator
          ↓
        Docker
     ┌────┼────┐
     ↓    ↓    ↓
   SQLi  XSS  IDOR
```

Laravel tetap menjadi control plane.

Lab runtime menjadi execution plane.

---

# 23. V2 Potential Features

- real isolated vulnerable labs,
- Docker-based runtime,
- ephemeral lab environments,
- Redis,
- queues,
- resource limits,
- network isolation,
- lab expiration,
- health checks,
- multiple workers,
- advanced attack scenarios,
- more achievements,
- challenge mode.

---

# 24. UI / UX Direction

SecureLab menggunakan developer-tool aesthetic, bukan stereotypical hacker interface.

Visual direction:

- dark mode,
- subtle surface hierarchy,
- compact information density,
- clean typography,
- restrained accent colors,
- minimal shadow,
- no heavy gradients,
- no excessive glow,
- no Matrix-style decoration.

Primary inspiration:

- developer tools,
- security tooling,
- modern technical dashboards,
- interactive learning environments.

Code menggunakan monospace typography.

Learning content harus memiliki comfortable reading width.

Lab workspace boleh lebih dense daripada lesson page.

---

# 25. Difficulty System

Supported values:

- Easy
- Medium
- Hard

Color digunakan sebagai semantic status, bukan dekorasi.

Example:

- Easy → positive
- Medium → warning
- Hard → danger

---

# 26. Content Management — V1

V1 tidak membutuhkan admin CMS.

Initial learning content disediakan menggunakan database seeders.

```text
database/seeders/
├── DatabaseSeeder.php
├── LearningCurriculumSeeder.php
├── LabSeeder.php
└── AchievementSeeder.php
```

Environment development harus dapat direbuild menggunakan:

```text
php artisan migrate:fresh --seed
```

---

# 27. Testing Requirements

Minimum automated test coverage harus mencakup:

## Authentication

- registration,
- login,
- email verification enforcement,
- authorization.

## Learning

- learning path overview,
- learning path detail,
- lesson access,
- lesson start and completion actions,
- progress updates and curriculum queries.

## Labs

- catalog access,
- workspace access,
- evaluator behavior,
- normal behavior simulation,
- vulnerable behavior simulation,
- rejected input handling,
- lab start action,
- lab completion action.

## Security

- unauthenticated and unverified access blocked,
- arbitrary lab input tidak dieksekusi,
- manipulated frontend completion state tidak dipercaya,
- server-side authoritative progress and achievement award evaluation.

---

# 28. Observability — MVP

Application harus memiliki sufficient server-side logging untuk:

- authentication error,
- failed lab execution,
- evaluator failure,
- unexpected exception.

Log tidak boleh menyimpan:

- passwords,
- session tokens,
- sensitive authentication data.

---

# 29. Product Metrics

Initial product metrics:

- registered users,
- lesson completion rate,
- lab completion rate,
- attempts per lab,
- learning-path completion rate,
- average lab completion time.

Untuk portfolio deployment, analytics kompleks tidak wajib.

---

# 30. MVP Success Criteria

SecureLab v1 dianggap selesai ketika user baru dapat:

1. Register.
2. Login.
3. Membuka dashboard.
4. Memilih learning path.
5. Membaca lesson.
6. Menandai lesson selesai.
7. Membuka lab.
8. Membuka lab workspace.
9. Mengirim input simulasi.
10. Melihat simulated application behavior.
11. Melihat attack visualization (simulation trace).
12. Membaca root cause dan panduan remediasi.
13. Membandingkan vulnerable dan secure implementation.
14. Menyelesaikan challenge dan menandai lab selesai.
15. Melihat progress diperbarui.
16. Mendapat achievement yang sesuai.

Dan semua fungsi tersebut berjalan tanpa memberikan arbitrary execution capability kepada user.

---

# 31. Development Phases

## Phase 1 — Foundation

- Laravel setup
- PostgreSQL
- Inertia
- React
- Tailwind
- authentication
- base layout

## Phase 2 — Learning

- learning paths
- modules
- lessons
- lesson progress
- seeders

## Phase 3 — Labs Foundation

- labs catalog
- lab sessions
- LabRuntime contract
- SimulationLabRuntime
- LabSessionService

## Phase 4 — First Complete Lab

- SQL Injection Basics
- evaluator
- request inspector
- response inspector
- attack flow
- solution page

SQL Injection Basics menjadi reference implementation bagi lab berikutnya.

## Phase 5 — Additional Labs

- Reflected XSS
- IDOR
- Weak Password Reset
- Stored XSS

## Phase 6 — Progress & Gamification

- progress page
- achievements
- profile statistics

## Phase 7 — Quality

- feature tests
- security tests
- responsive improvements
- accessibility
- error handling
- polish

---

# 32. Future Evolution

SecureLab tidak dirancang sebagai throwaway MVP.

Expected architecture evolution:

```text
V1
Laravel
React / Inertia
PostgreSQL
Simulation Runtime

        ↓

V2
Laravel
PostgreSQL
Redis
Queue
Docker Lab Runtime
Lab Orchestrator

        ↓

V3
Multiple Lab Workers
Container Scheduling
Observability
Advanced Lab Isolation
Autoscaling
```

V1 tetap menggunakan modular monolith karena memberikan development speed dan architectural clarity tanpa kompleksitas premature microservices.

---

# 33. Definition of Done

SecureLab v1 selesai ketika:

> A user can register, follow a structured web-security learning path, learn why common vulnerabilities occur, experiment with them through safe simulated labs, visualize the attack flow, understand the secure implementation, and track their learning progress.

SecureLab v1 harus terasa sebagai produk pembelajaran yang utuh, bukan sekadar kumpulan halaman demo security.
