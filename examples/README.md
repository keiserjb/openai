OpenAI Example Provider (openai_example_provider)

This folder contains an example provider module packaged inside the `openai` module at `openai/examples/openai_example_provider/`.

Important: Backdrop will not enable modules located inside another module's directory. To test and enable this example as a real module, copy the entire `openai_example_provider` folder to `modules/contrib/openai_example_provider/` (parallel to `openai/`).

Files included:
- openai_example_provider.info - module info
- openai_example_provider.module - hooks to register provider and add settings
- includes/OpenExampleAdapter.php - minimal adapter skeleton

Usage:
1. Copy the example folder to a top-level module folder, e.g.:

```cmd
cp -r modules/contrib/openai/examples/openai_example_provider modules/contrib/openai_example_provider
```

2. Enable the module via the Backdrop UI (Admin → Modules) or via Bee:

```cmd
# from Windows cmd.exe
ddev exec "bee en openai_example_provider -y"
ddev exec "bee cc all"
```

3. Configure the provider at Admin → Configuration → OpenAI → Settings and enable the provider.

Replace the simulated adapter calls inside `includes/OpenExampleAdapter.php` with real HTTP calls to your provider and adapt return shapes to match the expectations of the `openai` core.

