# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
