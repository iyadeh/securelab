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

Initial paths:

- Web Security Fundamentals
- Authentication Security
- Injection Attacks
- Client-Side Security
- Access Control

Setiap path menampilkan:

- title,
- description,
- number of modules,
- number of lessons,
- difficulty,
- completion percentage.

---

# FR-04 Learning Path Detail

Learning path detail harus menampilkan modules secara berurutan.

Contoh:

1. HTTP Fundamentals
2. Cookies & Sessions
3. SQL Injection
4. Authentication
5. Cross-Site Scripting
6. Access Control

Module dapat mempunyai state:

- locked,
- available,
- in progress,
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

# FR-07 Lab Session

Saat user menekan **Start Lab**, platform membuat `LabSession`.

LabSession minimal menyimpan:

- user,
- lab,
- status,
- state,
- start time,
- completion time,
- expiration time.

Possible statuses:

- created,
- starting,
- ready,
- running,
- completed,
- failed,
- expired.

Tidak semua state harus digunakan secara aktif pada v1, tetapi schema harus siap mendukung evolusi runtime pada v2.

---

# FR-08 Simulated Lab Workspace

Lab workspace harus memiliki:

## Objective Panel

Menampilkan objective challenge.

## Target Application

Menampilkan simulated application interface.

Contoh:

- login form,
- search form,
- profile page,
- comment form.

## Request Inspector

Menampilkan request yang dihasilkan user interaction.

## Response Inspector

Menampilkan simulated HTTP response.

## Application Inspector

Menampilkan bagaimana backend memproses input.

## Database Inspector

Menampilkan simulated query/database interaction jika relevan.

## Attack Flow

Menampilkan visualisasi perjalanan input melalui sistem.

---

# FR-09 Lab Execution

User mengirim input melalui workspace.

Request masuk ke:

**LabController → LabSessionService → LabRuntime**

V1 menggunakan:

`SimulationLabRuntime`

Simulation runtime meneruskan input ke vulnerability-specific evaluator.

Contoh:

- `SqlInjectionEvaluator`
- `ReflectedXssEvaluator`
- `IdorEvaluator`

Evaluator tidak menjalankan payload secara nyata.

Evaluator hanya menentukan apakah input memenuhi kondisi challenge yang telah didefinisikan.

---

# FR-10 Attack Events

Setiap lab execution dapat menghasilkan normalized events.

Contoh tipe event:

- request,
- input,
- application,
- query,
- authorization,
- database,
- browser,
- response,
- result.

Frontend menggunakan events tersebut untuk attack visualization.

Frontend tidak boleh bergantung pada raw simulation implementation.

---

# FR-11 Lab Completion

Jika evaluator menentukan challenge berhasil:

- session dapat ditandai completed,
- progress diperbarui,
- achievement dievaluasi,
- solution page dibuka.

User tidak boleh mendapatkan arbitrary execution capability setelah menyelesaikan challenge.

---

# FR-12 Solution / Remediation Page

Solution page harus menampilkan:

- vulnerability name,
- objective result,
- root cause,
- attack explanation,
- vulnerable implementation,
- secure implementation,
- why the fix works,
- relevant best practices.

Layout utama:

**Vulnerable vs Secure**

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

Initial achievements dapat berupa:

### First Blood

Complete first lab.

### Code Explorer

Complete a defined number of lessons.

### Quick Learner

Complete a beginner learning sequence.

### Persistent Learner

Maintain learning activity over multiple days.

Achievements harus bersifat educational/gamification ringan dan tidak mengganggu core experience.

---

# FR-15 Profile

Profile harus menampilkan:

- name,
- email,
- level,
- XP jika digunakan,
- completed lessons,
- completed labs,
- achievements,
- current streak.

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
React + Inertia  
↓  
Laravel  
↓  
PostgreSQL

Lab subsystem:

Laravel  
↓  
LabSessionService  
↓  
LabRuntime Contract  
↓  
SimulationLabRuntime  
↓  
Scenario Evaluator

---

# 14. Technology Stack

## Backend

- Laravel
- PHP
- Laravel authentication/session
- Eloquent ORM

## Frontend

- Inertia
- React
- TypeScript
- Tailwind CSS

## Database

- PostgreSQL

## Testing

- PHPUnit / Pest
- Laravel feature tests
- React component tests jika diperlukan

## V2 Candidates

- Redis
- Laravel Queue
- Docker
- dedicated Lab Orchestrator

---

# 15. Application Architecture

Laravel menggunakan modular monolith.

Suggested structure:

```text
app/
├── Domain/
│   ├── Learning/
│   ├── Labs/
│   ├── Progress/
│   └── Achievements/
│
├── Http/
│   ├── Controllers/
│   └── Requests/
│
├── Models/
├── Policies/
├── Services/
└── Providers/
```

Lab domain:

```text
app/Domain/Labs/
├── Contracts/
│   └── LabRuntime.php
├── Runtime/
│   └── SimulationLabRuntime.php
├── Evaluators/
│   ├── SqlInjectionEvaluator.php
│   ├── ReflectedXssEvaluator.php
│   └── IdorEvaluator.php
├── DTO/
│   ├── LabExecutionResult.php
│   └── LabEvent.php
└── Services/
    └── LabSessionService.php
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
- Module
- Lesson
- LessonProgress
- Lab
- LabSession
- LabAttempt
- Achievement
- UserAchievement

Relationships:

```text
LearningPath
 └── Modules
      ├── Lessons
      └── Labs

User
 ├── LessonProgress
 ├── LabSessions
 └── Achievements
```

---

# 18. Initial Database Tables

## users

- id
- name
- email
- password
- created_at
- updated_at

## learning_paths

- id
- slug
- title
- description
- difficulty
- position
- published

## modules

- id
- learning_path_id
- title
- description
- position

## lessons

- id
- module_id
- slug
- title
- summary
- content
- estimated_minutes
- position

## lesson_progress

- id
- user_id
- lesson_id
- status
- started_at
- completed_at

## labs

- id
- module_id
- slug
- title
- description
- vulnerability_type
- difficulty
- scenario
- position

## lab_sessions

- id
- user_id
- lab_id
- status
- state
- runtime
- started_at
- completed_at
- expires_at

## lab_attempts

- id
- lab_session_id
- input
- result
- successful
- created_at

## achievements

- id
- key
- name
- description
- icon

## user_achievements

- user_id
- achievement_id
- earned_at

---

# 19. Routes

```text
GET  /dashboard

GET  /learn
GET  /learn/{learningPath}

GET  /lessons/{lesson}
POST /lessons/{lesson}/complete

GET  /labs
GET  /labs/{lab}

POST /labs/{lab}/start

GET  /lab-sessions/{session}
POST /lab-sessions/{session}/execute
POST /lab-sessions/{session}/submit
POST /lab-sessions/{session}/reset

GET  /progress

GET  /profile
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

Authorization harus diterapkan agar user hanya dapat mengakses lab session miliknya.

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
├── start()
├── execute()
├── reset()
└── stop()
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
├── LearningPathSeeder.php
├── LessonSeeder.php
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
- authorization.

## Learning

- lesson access,
- lesson completion,
- progress updates.

## Labs

- session creation,
- session ownership,
- evaluator behavior,
- unsuccessful attempt,
- successful attempt,
- completion status,
- reset behavior.

## Security

- accessing another user's lab session fails,
- arbitrary lab input tidak dieksekusi,
- manipulated frontend completion state tidak dipercaya.

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
8. Memulai lab session.
9. Mengirim input.
10. Melihat simulated application behavior.
11. Melihat attack visualization.
12. Menyelesaikan challenge.
13. Membaca root cause.
14. Membandingkan vulnerable dan secure implementation.
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
