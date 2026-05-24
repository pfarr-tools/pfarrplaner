# Pfarrplaner — Coding Guide

## Project Overview

Laravel 11 / PHP 8.4 application for collaborative church service planning. Frontend uses Inertia.js (Vue). GPL-3.0-or-later license.

## Autoloading (PSR-4)

| Namespace | Path |
|---|---|
| `App\` | `app/` |
| `Database\Factories\` | `database/factories/` |
| `Database\Seeders\` | `database/seeds/` |
| `Tests\` | `tests/` |

## Naming Conventions

- **Models**: singular PascalCase — `Service`, `User`, `Funeral`
- **Controllers**: `{Model}Controller` — `ServiceController`
- **Traits**: `{Descriptive}Trait` suffix — `TracksChangesTrait`, `HasAttachmentsTrait`
- **Scopes** (global): `{Name}Scope` — `ServicesOnlyScope`
- **Actions**: `{Verb}{Model}` extending `AbstractAction` — `CreateService`
- **Form Requests**: `Store{Model}Request` / `Update{Model}Request`
- **Tests**: `{Model}FeatureTest`

## Directory Organization

Models are grouped by domain under `app/Models/`:
- `Models/People/` — `User`, persons
- `Models/Rites/` — `Funeral`, other rites
- `Models/Seating/` — `Booking`, seating-related

Controllers have subdirectories for `Api/`, `Auth/`, `Extranet/`.

## Class Architecture

### Models
- Extend `AbstractModel` (not `Illuminate\Database\Eloquent\Model` directly)
- Use `HasFactory` trait plus domain traits (`HasCommentsTrait`, `TracksChangesTrait`, `HasAttachmentsTrait`, etc.)
- Declare `$fillable`, `$casts`, `$with`, `$appends`, `$attributes` in that order
- Use legacy accessor/mutator style: `get{Name}Attribute()` / `set{Name}Attribute()`
- Return types on relationship methods: `public function city(): BelongsTo`
- BelongsToMany: always chain `->withTimestamps()` and `->withPivot()` where applicable
- Query scopes prefixed `scope`: `scopeAtDate()`, `scopeInCities()`
- Global scopes applied in `boot()`: `static::addGlobalScope(new ServicesOnlyScope())`
- Static helper methods for common query patterns: `Service::mix(...)`, `User::createIfNotExists(...)`

### Controllers
- Extend `AbstractCRUDController` (web) or `AbstractApiCRUDController` (API)
- Authorize via `Gate::authorize('index', $this->modelClass)`
- Authentication middleware in constructor: `$this->middleware('auth')->except([...])`
- Return Inertia responses: `Inertia::render('View/Name', $data)`
- Validation via dedicated Form Request classes, not inline `$request->validate()`

### Actions
- Business logic lives in action classes under `app/Actions/`
- Extend `AbstractAction` or `AbstractCreateAction`

## Routes

- Models self-register routes via `static registerRoutes()` and `static registerApiRoutes()` methods on `AbstractModel`
- Auto-discovered in `routes/web.php`
- Additional routes in `routes/web/admin/*.php`
- Resource route naming: `{singular}.{verb}` (e.g. `service.index`, `service.edit`)

## Migrations

- Column ordering: primary key → foreign keys → data columns → booleans → timestamps
- IDs: `$table->increments('id')`
- Foreign keys: `$table->foreignId('city_id')->references('id')->on('cities')`
- Timestamps: always `$table->timestamps()`
- Both `up()` and `down()` must be implemented

## Blade / Frontend

- Layout: `@extends('layouts.app', [...])`
- Component slot pattern: `@component('components.ui.card') @slot('cardHeader') ... @endslot @endcomponent`
- Blade includes registered as aliases in `AppServiceProvider` via `Blade::include()`
- Inertia.js used for all interactive views; traditional Blade for emails and print layouts

## File Headers

Every PHP file carries this block comment immediately after `<?php`:

```php
/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */
```

## Docblocks

- All public methods get a docblock with `@param` and `@return`
- Single-line property annotations: `/** @var string[] cached user settings */`
- Scopes document the filter logic, not just the signature

## Tests

- Framework: PHPUnit 11
- Location: `tests/Feature/{Model}FeatureTest.php`
- Method naming: `public function test{Description}()` (camelCase, `test` prefix)
- Factories: `Model::factory()->create([...])`
- Auth: `$this->actingAs($this->user)->patch(route(...), $data)`

## Key Packages

- `inertiajs/inertia-laravel` — frontend bridge
- `spatie/laravel-permission` — roles & permissions (`HasRoles`)
- `laravel/sanctum` — API tokens (`HasApiTokens`)
- `nesbot/carbon` — dates
- `barryvdh/laravel-ide-helper` — IDE support

## Commit Messages

Conventional Commits format, **description always in German**:

```
<type>(<scope>): Kurze Beschreibung auf Deutsch.
```

Common types observed: `feat`, `fix`, `chore`, `build`. Scope is optional (used for `chore(release)`). Description is a capitalized German sentence, ending with a period for `fix` commits, no period for `feat`/`chore`.

Examples:
```
feat: KI-Prompt für Gottesdienste
fix: Falsche Einrückungen beim Zitaten in Word-Dokumenten.
chore(release): 2026.10.2
build: Mix toolchain auf v6 aktualisiert
```

## Dev Instructions
- Never commit or push unless I specifically ask you to.
- When changing models, migrations, fillable/cast fields, personal data fields, attachments, calendar integrations, seating bookings, rites, user/profile data, or demo-login behavior, check whether `app/Console/Commands/DevBuilder/DemoBuilder.php` must be updated so the online demo remains buildable and safely anonymized.
- If a change introduces new user-facing or personally identifying data, either extend the DemoBuilder anonymization/deletion logic in the same change or explicitly document why no DemoBuilder update is needed.

## Releases
- None of Claude Code will be done in the main branch. Usually in claude-testing, occasionally in a dedicated feature branch.
- Releases are done by merging the claude-testing branch into the main branch.
- After merging, checkout the main branch and run either `npm run release:patch` or `npm run release:minor`, depending on whether there are new features in the release.
- After the release is done, push the main branch to origin.
- Go back to the claude-testing branch and continue working there.

## End-user documentation
- Every change to the code must be reflected in the full end-user manual for Pfarrplaner, covering everything. 
- When essential new user-visible features or major workflow improvements are added, also review `/was-ist-der-pfarrplaner` and update the page if the public-facing product description, screenshots, feature overview, or handbook link placement should change.
- The manual should exist in a series of .md files in a suitable folder. 
- The app layout needs to include a help button on every page (somewhere  
  on the right side of the Top Nav), opening the appropriate manual page in a separate tab. 
- There need to be links to an index and a table of contents. 
- There needs to be a build step compiling the manual into a downloadable PDF  
  with TOC and index.
- Documentation needs to explain each screen to the fullest, covering every UI element relevant to the user.
- The documentation also needs to be structured into chapters arranged by useful topic, guiding a novice user into the app. 
- Installation, maintenance, technical docs are not part of this documentation at this point.
- Where it seems useful to include screenshots in the documentation, provide a separate set of dusk tests setting   
  up the desired situation and saving a screenshot to the docs folder.
- Documentation must be entirely in German, using simple, non-technical language an average user in a church office
  can understand.                     
