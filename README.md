# Laravel DDD Maker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imran-ahmed-optilius/laravel-ddd-maker.svg?style=flat-square)](https://packagist.org/packages/imran-ahmed-optilius/laravel-ddd-maker)
[![Total Downloads](https://img.shields.io/packagist/dt/imran-ahmed-optilius/laravel-ddd-maker.svg?style=flat-square)](https://packagist.org/packages/imran-ahmed-optilius/laravel-ddd-maker)
[![CI](https://github.com/imran-ahmed-optilius/laravel-ddd-maker/actions/workflows/ci.yml/badge.svg)](https://github.com/imran-ahmed-optilius/laravel-ddd-maker/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A single Artisan command that scaffolds a **complete Clean Architecture + DDD feature** in seconds — Request, Action, UseCase, Service, Repository, Output DTO, and Response — all with proper namespaces, interfaces, and PSR-12 compliant code.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.1    |
| Laravel    | ^10.0, ^11.0, or ^12.0 |

---

## Installation

```bash
composer require imran-ahmed-optilius/laravel-ddd-maker --dev
```

Laravel's auto-discovery will register the service provider automatically.

---

## Usage

### Interactive wizard

```bash
php artisan make:ddd
```

The command walks you through everything step by step:

```
╔═══════════════════════════════════════════════════╗
║  Laravel DDD Maker  by imran-ahmed-optilius       ║
║  Clean Architecture + Domain-Driven Design        ║
╚═══════════════════════════════════════════════════╝

  Enter the file prefix (e.g. ForHomePageTeacherGet):
  > ForHomePageTeacherGet

  ── Complexity & Naming Questions ─────────────────────────────

  Will this feature require Value Objects (VO) for IDs, status, or types? (yes/no) [no]:
  > no

  Will the Repository require a dedicated Input DTO for its arguments? (yes/no) [no]:
  > no

  Will the Service require a dedicated Input DTO for its arguments? (yes/no) [no]:
  > no

  Will the Response have a different or multiple names? (yes/no) [no]:
  > no

  Will the Output (DTO) have a different or multiple names? (yes/no) [no]:
  > no

  Will the Repository have a different or multiple names? (yes/no) [no]:
  > no

  Is there a need for a domain Entity class? (yes/no) [no]:
  > no

  Should an Eloquent Model be generated? (yes/no) [no]:
  > no

  Will this feature need a Request (Form Request) class? (yes/no) [yes]:
  > yes
  Request class name [default: ForHomePageTeacherGetRequest]:
  >

  Enter the feature folder name (e.g. HomePage or MembershipStatus/History):
  > HomePage

  ── Files to be generated ─────────────────────────────────────

    Request:
      • app/Http/Requests/Api/V1/HomePage/ForHomePageTeacherGetRequest.php
    Action:
      • app/Http/Controllers/Api/V1/HomePage/ForHomePageTeacherGetAction.php
    Use Case:
      • app/UseCases/HomePage/IForHomePageTeacherGetUseCase.php
      • app/UseCases/HomePage/ForHomePageTeacherGetUseCase.php
    Domain Service:
      • app/Domain/HomePage/Services/IForHomePageTeacherGetService.php
      • app/Infra/HomePage/Services/ForHomePageTeacherGetService.php
    Repository:
      • app/Domain/HomePage/Repositories/IForHomePageTeacherGetRepository.php
      • app/Infra/HomePage/Repositories/ForHomePageTeacherGetRepository.php
    Output DTO:
      • app/Domain/HomePage/Services/Output/ForHomePageTeacherGetOutput.php
    Response:
      • app/Http/Responses/Api/V1/HomePage/IForHomePageTeacherGetResponse.php
      • app/Http/Responses/Api/V1/HomePage/ForHomePageTeacherGetResponse.php

  Generate all files now? (yes/no) [yes]:
  > yes

  ── Generating ────────────────────────────────────────────────
  ✔ CREATED  app/Http/Requests/Api/V1/HomePage/ForHomePageTeacherGetRequest.php
  ✔ CREATED  app/Http/Controllers/Api/V1/HomePage/ForHomePageTeacherGetAction.php
  ...

  ── Add to AppServiceProvider::register() ─────────────────────
  ...

  ── Add to routes/api.php ─────────────────────────────────────
  Route::get('/for-home-page-teacher-get', \App\Http\Controllers\Api\V1\HomePage\ForHomePageTeacherGetAction::class);

  Done! All files generated successfully.
```

### With inline options

```bash
php artisan make:ddd --prefix=ForHomePageTeacherGet --folder=HomePage
```

### Multiple repositories, outputs, or responses

When the wizard asks *"use a custom or multiple names?"*, answer `yes` and enter each name one by one. Leave the input blank to finish.

---

## Generated File Structure

```
app/
├── Http/
│   ├── Requests/Api/V1/{Folder}/
│   │   └── {Prefix}Request.php               ← FormRequest (Optional)
│   ├── Controllers/Api/V1/{Folder}/
│   │   └── {Prefix}Action.php                ← Invokable controller
│   └── Responses/Api/V1/{Folder}/
│       ├── I{Response}.php                   ← Response interface
│       └── {Response}.php                    ← Response implementation
├── Models/
│   ├── {Model}.php                           ← Eloquent Model (Optional)
│   └── Entities/
│       └── {Entity}.php                      ← Domain Entity (Optional)
├── UseCases/{Folder}/
│   ├── I{Prefix}UseCase.php                  ← UseCase interface
│   └── {Prefix}UseCase.php                   ← UseCase implementation
├── Domain/{Folder}/
│   ├── Vo/
│   │   └── {Vo}.php                          ← Value Object (Optional)
│   ├── Services/
│   │   ├── I{Prefix}Service.php              ← Domain Service interface
│   │   ├── Input/
│   │   │   └── {Prefix}ServInput.php         ← Service Input DTO (Optional)
│   │   └── Output/
│   │       └── {Output}.php                  ← Output DTO
│   └── Repositories/
│       ├── I{Repo}.php                       ← Repository interface
│       └── Input/
│           └── {Repo}Input.php               ← Repository Input DTO (Optional)
└── Infra/{Folder}/
    ├── Services/
    │   └── {Prefix}Service.php               ← Service implementation
    └── Repositories/
        └── {Repo}.php                        ← Eloquent repository
```

---

## After Generation

### 1. Register bindings in `AppServiceProvider`

The command prints the exact snippet. Add it to the `register()` method:

```php
use App\UseCases\HomePage\IForHomePageTeacherGetUseCase;
use App\UseCases\HomePage\ForHomePageTeacherGetUseCase;
// ... other imports

public function register(): void
{
    // Use Cases
    $this->app->bind(IForHomePageTeacherGetUseCase::class, ForHomePageTeacherGetUseCase::class);

    // Domain Services
    $this->app->bind(IForHomePageTeacherGetService::class, ForHomePageTeacherGetService::class);

    // Repositories
    $this->app->bind(IForHomePageTeacherGetRepository::class, ForHomePageTeacherGetRepository::class);

    // Responses
    $this->app->bind(IForHomePageTeacherGetResponse::class, ForHomePageTeacherGetResponse::class);
}
```

### 2. Register the route in `routes/api.php`

The command prints this too:

```php
Route::get('/for-home-page-teacher-get', \App\Http\Controllers\Api\V1\HomePage\ForHomePageTeacherGetAction::class);
```

### 3. Fill in the `TODO` placeholders

Each generated file has clearly marked `TODO` comments:

- `rules()` in the Request class — add your validation rules
- Constructor properties + getters in the Output DTO
- `toArrayResponse()` mapping in the Response class
- Eloquent queries in the Repository implementation
- Field mapping in the Service implementation

---

## Customising Stubs

All stubs live in `vendor/imran-ahmed-optilius/laravel-ddd-maker/src/Stubs/*.stub`.

If you want to permanently customise them for your project, copy the `src/Stubs` folder into your project and update `StubRenderer::$stubPath` to point to your copy, or publish them once support for `php artisan vendor:publish` is added in a future version.

---

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT — see [LICENSE](LICENSE).
