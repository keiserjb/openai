## Quick context

- This repository is a Backdrop CMS module (PHP) that integrates OpenAI services. Top-level modules live in `modules/` (e.g., `openai_chatgpt`, `openai_embeddings`, `openai_dalle`). The core module scaffolding is in the repo root (e.g., `openai.module`, `openai.info`).
- Composer is used for PHP dependencies. See `composer.json` (requires PHP 8.2 and `openai-php/client`, Guzzle, Symfony components).
- Dependency scoping is performed with php-scoper to avoid conflicts; scoped output is placed in `build/` via `build.sh`.

## What to know (architecture & why)

- Central API surface: `includes/OpenAIApi.php`. This class wraps the OpenAI SDK and exposes methods for models, chat, completions, images, audio, embeddings, and moderation. Prefer adding or changing OpenAI integration here rather than scattering SDK calls.
- Prompt preparation: `includes/StringHelper.php::prepareText()` is used to clean and truncate content before sending to OpenAI — reuse it when constructing prompts.
- Module hooks & integration: `openai.module` contains Backdrop hooks (menu, settings, init). Configuration is loaded via `config('openai.settings')` and the Key module is used to store the actual API key.
- Submodules: features are split into submodules under `modules/openai_*`. Each submodule registers its own menu pages and settings under `admin/config/openai/`.

## Critical developer workflows

- Install dependencies (composer) and build scoped vendor files (run from module root):
  - Run `composer install` (ensures `vendor/bin/php-scoper` is present).
  - Run `./build.sh` to produce scoped dependencies under `build/`. The script runs php-scoper with the repo's configuration.
- Runtime configuration: the module expects keys to be managed by the Backdrop Key module. Configure `admin/config/openai/settings` (the module shows an admin form implemented in `openai.module`). Settings are persisted to `openai.settings` (see `config/openai.settings.json`).

## Project-specific conventions and patterns

- Single client entrypoint: SDK interactions should go through `OpenAIApi` (functions are grouped by capability: `chat`, `completions`, `images`, `textToSpeech`, `speechToText`, `embedding`, `moderation`). This keeps caching, error handling (watchdog), token floors, and model selection centralized.
- Model selection rules: `OpenAIApi::getModels()` filters and whitelists model IDs with regexes; `modelUsesResponsesApi()` determines whether to use Responses vs Chat API. Respect these helpers when adding new model logic.
- Token handling: `applyMaxTokens()` and `minCapForModel()` implement model-specific floors. Use these helpers to avoid token-cap issues.
- Error/logging: module uses Backdrop `watchdog()` for errors and `watchdog(..., WATCHDOG_DEBUG)` for debug payloads. Avoid printing raw API responses; use the existing logging approach.
- Streaming: streaming responses are returned as Symfony `StreamedResponse`. If adding streaming endpoints, follow the pattern in `OpenAIApi::chat()` and `OpenAIApi::completions()`.

## Integration points & examples (explicit)

- Where to add a new OpenAI call: add a method to `includes/OpenAIApi.php` and call it from a submodule or hook. Example: to add a new image preprocessing step, implement it in `OpenAIApi::images()` and call from `modules/openai_dalle/openai_dalle.module` (or similar).
- Example: Responses API vs Chat
  - If model name matches `^gpt-5|o[0-9]` then `OpenAIApi::chat()` routes to the Responses API (see `modelUsesResponsesApi()`). For these models, the module converts chat messages to Responses `input[]` + `instructions` via `toResponsesItemsAndInstructions()`.
- Example: prepare text before sending:
  - Use `StringHelper::prepareText($node->body['value'])` to clean HTML and strip large code blocks before sending prompts.

## Files to look at for concrete examples

- `includes/OpenAIApi.php` — central SDK wrapper and model handling rules.
- `includes/StringHelper.php` — prompt cleaning & truncation.
- `openai.module` — Backdrop hooks, admin settings form and menu entries.
- `config/openai.settings.json` — default config keys and debug flag.
- `build.sh` — how scoping is executed and where scoped files land (`build/`).
- `composer.json` — PHP version and dependencies (useful for updating or adding packages).

## Quick dos & don'ts for agent edits

- DO centralize SDK changes in `includes/OpenAIApi.php`.
- DO use `StringHelper::prepareText()` for any user-submitted content.
- DO respect Backdrop APIs: `watchdog()`, `cache('data')`, `system_settings_form()` and `menu` hooks.
- DO not hardcode API keys — use the Key module and `config('openai.settings')->get('api_key')`.
- DO not bypass the scoping/build flow: prefer modifying code and then run `composer install` + `./build.sh` to regenerate `build/` if vendor or scoping needs change.

## When you see an unfamiliar pattern

- If a method manipulates model payloads, look for helpers: `applyMaxTokens()`, `sanitizeResponsesPayload()`, `toResponsesItemsAndInstructions()`, and `collapseOutputParts()`.
- If you need to change how models are filtered, update `getModels()` and the `$modelWhitelist` guarded APIs.

## If you need more context

- Start with `README.md` (repo root) for high-level goals, then inspect `includes/OpenAIApi.php` and `openai.module` for concrete behavior. If anything is missing or ambiguous in this file, tell me which area you want expanded (examples, more file links, or workflow commands).

---
Please review these instructions and tell me if you want more examples (short code snippets) or additional guidance for running/debugging inside WSL or Backdrop environment.
