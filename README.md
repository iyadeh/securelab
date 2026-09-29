# SecureLab — Interactive Web Security Learning Platform

SecureLab is an interactive web security learning platform built to help software engineers, computer science students, and security practitioners understand common web vulnerabilities from both the **attacker** and **defender** perspectives.

Instead of only demonstrating payloads, SecureLab grounds every vulnerability in software engineering reality:
- How does the vulnerability arise in application code?
- How does untrusted data flow through the system?
- What is the underlying root cause and security impact?
- How is the vulnerability remediated using secure, production-grade patterns?

---

## 🎯 The Core Learning Loop

SecureLab structures each topic around a continuous 5-stage educational loop:

```text
Learn ────► Understand ────► Exploit ────► Analyze ────► Fix
(Foundations) (Mechanisms)   (Controlled)   (Root Cause)   (Secure Code)
```

1. **Learn**: Understand the foundational security concepts, protocol behaviors, and threat model.
2. **Understand**: Examine how vulnerable code accepts and handles untrusted data.
3. **Exploit**: Experiment with realistic inputs in a safe, controlled sandbox.
4. **Analyze**: Trace the attack flow step-by-step through inputs, application layers, security decisions, and outputs.
5. **Fix**: Review defensive remediation patterns comparing vulnerable versus secure implementations.

---

## 🛡️ Safe by Design

SecureLab V1 uses a **controlled, deterministic in-memory simulation runtime** ([`SimulationLabRuntime`](app/Services/Labs/SimulationLabRuntime.php)).

- **No untrusted execution**: Learner inputs are never passed to PHP `eval()`, system shells, live SQL query engines, or arbitrary browser execution contexts.
- **Isolated evaluation**: Each challenge evaluator checks bounded synthetic inputs against predefined vulnerability models.
- **No external risks**: The simulation engine operates without outbound network requests or live vulnerable containers, making it safe to run on local development machines, CI environments, and managed production hosts.

---

## ✨ Features

- **Structured Curriculum**: Progressive learning paths divided into modules and ordered lessons, with estimated completion times and reading outlines.
- **Interactive Lab Workspaces**: Dedicated lab workspace interfaces where learners test inputs against simulated web scenarios.
- **Attack Flow Visualization**: Step-by-step trace showing data propagation across input, application, security decision, and output boundaries.
- **Side-by-Side Remediation Guidance**: Clear, contextual explanations detailing root causes, impact levels, and syntax-highlighted *Vulnerable vs. Secure* implementation patterns.
- **Authoritative Progress Tracking**: Reliable, server-side progress calculation (`not_started`, `in_progress`, `completed`) with half-up completion percentage metrics.
- **Achievement System**: Server-validated achievement unlocks recognizing key milestones (first lesson, first lab, full path completion, and full curriculum mastery).
- **Developer-Tool Aesthetic**: Restrained dark-mode interface built with high information density, semantic status colors, and monospace code typography.

---

## 🔬 Initial Lab Scenarios (V1)

SecureLab includes five core security labs covering OWASP Top 10 concepts:

| Lab | Vulnerability Category | Difficulty | Focus Area |
| :--- | :--- | :--- | :--- |
| **SQL Injection Authentication Bypass** | Injection | Easy | Unsafe SQL query construction vs. parameterized statements |
| **Reflected XSS** | Cross-Site Scripting | Easy | Immediate input reflection and context-aware output encoding |
| **Stored XSS** | Cross-Site Scripting | Medium | Persisted untrusted content and output encoding boundaries |
| **Broken Access Control (IDOR)** | Access & Account Security | Easy | Missing server-side authorization checks on object identifiers |
| **Weak Password Reset** | Access & Account Security | Medium | Predictable reset token workflows and secure token validation |

---

## 🛠️ Tech Stack

### Backend
- **Framework**: [Laravel 13](https://laravel.com) running on PHP 8.4
- **Architecture**: Modular Monolith with dedicated domain service layers
- **Authentication**: [Laravel Fortify](https://laravel.com/docs/fortify) (with email verification enforced)
- **API & Routing**: [Laravel Wayfinder](https://github.com/laravel/wayfinder) (type-safe TypeScript route definitions)
- **Database**: PostgreSQL (production & local development) / SQLite `:memory:` (automated testing)

### Frontend
- **SPA Framework**: [Inertia.js v3](https://inertiajs.com) with [React 19](https://react.dev)
- **Language**: TypeScript 5.7
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com) via `@tailwindcss/vite`
- **Component Primitives**: [Radix UI](https://www.radix-ui.com) & [Lucide React](https://lucide.dev) icons
- **Build Tool**: [Vite 8](https://vitejs.dev)

### Quality & Testing
- **Test Framework**: [Pest PHP 5](https://pestphp.com) (Feature and Unit test suites)
- **Static Analysis**: [PHPStan](https://phpstan.org) / [Larastan](https://github.com/larastan/larastan) (Level 7)
- **Code Formatter**: [Laravel Pint](https://laravel.com/docs/pint)
- **Type Checking**: TypeScript compiler (`tsc --noEmit`)

---

## 📂 Project Structure

```text
securelab/
├── app/
│   ├── Actions/Fortify/             # User registration and authentication actions
│   ├── Http/
│   │   ├── Controllers/             # Thin HTTP controllers
│   │   └── Requests/                # Form requests with strict field validation
│   ├── Models/                      # Eloquent models with typed casts and #[Fillable]
│   └── Services/
│       ├── AchievementService.php   # Authoritative achievement award sync
│       ├── LearningProgressService.php # Single source of truth for curriculum progress
│       └── Labs/                    # Lab simulation engine
│           ├── Contracts/           # LabRuntime and LabSimulator interfaces
│           ├── SimulationLabRuntime.php # Dispatcher for registered simulators
│           ├── LabGuidanceProvider.php  # Vulnerability root causes & secure patterns
│           └── Simulators/          # Scenario-specific deterministic evaluators
├── database/
│   ├── migrations/                  # Schema migrations
│   └── seeders/                     # Canonical curriculum and achievement seeders
├── resources/js/
│   ├── components/
│   │   ├── dashboard/               # Learner stats, primary actions, progress cards
│   │   ├── labs/                    # Simulation trace and analysis components
│   │   ├── learning/                # Lesson roadmap, sections, and navigation
│   │   ├── security/                # Category icons and semantic security badges
│   │   └── ui/                      # Base UI primitives (buttons, dialogs, cards)
│   ├── layouts/                     # Application shell and sidebar layouts
│   ├── pages/                       # Inertia React page views
│   └── routes/                      # Wayfinder auto-generated TypeScript routes
└── tests/
    ├── Feature/                     # Feature integration tests
    └── Unit/                        # Domain service unit tests
```

---

## 🚀 Getting Started

### Prerequisites

Ensure the following dependencies are installed on your machine:
- **PHP** >= 8.3 (PHP 8.4 recommended) with `pdo_pgsql`, `pdo_sqlite`, `mbstring`, `openssl`, and `tokenizer` extensions
- **Composer** >= 2.x
- **Node.js** >= 20.x and **npm**
- **Docker** and **Docker Compose** (for running PostgreSQL locally)

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/iyadeh/securelab.git
   cd securelab
   ```

2. **Install PHP and Node dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure the environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Start the database**:
   Run the included PostgreSQL container via Docker Compose:
   ```bash
   docker compose up -d
   ```

5. **Run migrations and seed initial curriculum**:
   ```bash
   php artisan migrate --seed
   ```
   *(This populates the canonical learning paths, modules, lessons, labs, and achievements.)*

6. **Start the development servers**:
   Run both the Laravel application and the Vite dev server concurrently:
   ```bash
   composer run dev
   ```
   Or run them in separate terminals:
   ```bash
   # Terminal 1: Laravel backend
   php artisan serve

   # Terminal 2: Vite frontend asset compilation
   npm run dev
   ```

7. **Access the application**:
   Open [http://localhost:8000](http://localhost:8000) in your web browser. Register a new account to begin the learning journey.

---

## 🧪 Testing & Code Quality

SecureLab enforces strict code quality and automated testing standards:

```bash
# Run Pest test suite (in-memory SQLite)
php artisan test

# Run TypeScript static type check
npm run types:check

# Run PHPStan static analysis (Level 7)
php vendor/bin/phpstan analyse --memory-limit=512M

# Check PHP code formatting with Laravel Pint
vendor/bin/pint --test

# Automatically fix PHP code formatting
vendor/bin/pint
```

> **Note for Windows PowerShell users:** If running `npm` produces a script execution policy error, invoke `npm.cmd` directly (e.g. `npm.cmd run types:check`).

---
