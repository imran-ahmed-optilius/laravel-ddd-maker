# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2025-04-17

### Added
- **`make:ddd` Command** — Introduced `make:ddd` as the primary command to replace/alias `make:feature`.
- **Component Reuse** — `make:ddd` now allows you to reuse existing Responses, Output DTOs, and Repositories instead of generating new ones.

### Changed
- Updated documentation to use `php artisan make:ddd`.

## [1.1.0] - 2025-04-12

### Added
- **Request is now optional** — wizard asks if you need a FormRequest; if yes, a custom name can also be provided
- **Service Input DTO** — optional `{Prefix}ServInput` at `app/Domain/{Folder}/Services/Input/`
- **Repository Input DTO** — optional `{RepoName}Input` at `app/Domain/{Folder}/Repositories/Input/`
- **Value Objects (VO)** — optional, supports multiple VO classes at `app/Domain/{Folder}/Vo/`
- **Domain Entity** — optional entity class at `app/Models/Entities/`
- **Eloquent Model** — optional model extending `BaseModel` at `app/Models/`
- **Action without Request** — when Request is skipped, Action uses `Illuminate\Http\Request` directly
- Feature folder is now asked last, matching the updated spec question order
- File summary is now grouped by category for easier review

## [1.0.0] - 2025-04-12

### Added
- Initial release
- `make:feature` Artisan command with interactive wizard
- Scaffolds Request, Action, UseCase, Service, Repository, Output DTO, Response
- Support for custom and multiple Responses, Output DTOs, and Repositories
- Prints AppServiceProvider bindings and route snippet after generation
- Skips existing files safely (no overwrites)
- PSR-12 compliant generated code with `declare(strict_types=1)`
- Compatible with Laravel 10, 11, and 12
