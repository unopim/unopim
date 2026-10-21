# Composer contract tests

Run with Node 18 or later, without an application server or database:

    node --test packages/Webkul/AiAgent/tests/JavaScript/composer-contract.test.cjs

The Unit/ComposerContractTest.php bridge also runs this suite from the existing
Pest AiAgent unit tests (the JavaScript bridge skips when Node is not on PATH).

The harness evaluates the committed widget's JavaScript, substituting only
server-rendered Blade values. Tests exercise actual component methods and
computed properties. Small template assertions protect prop/event and selector
bindings; this is not a substitute for authenticated browser acceptance.
The send test captures FormData at a stub transport and makes no network call.
