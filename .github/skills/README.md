# Skills Standard

## Canonical Source

All workspace skills are maintained in:

- `.github/skills/<skill-name>/SKILL.md`

This is the single source of truth for every agent runtime in this repository.

## Agent Runtime Links

The following paths must be symlinks to `.github/skills`:

- `.ai/skills`
- `.claude/skills`
- `.codex/skills`
- `.cursor/skills`
- `.kilocode/skills-code`

## Authoring Rules

- Add or update skills only in `.github/skills`.
- Keep `name` and `description` in YAML frontmatter.
- Use explicit "Use when..." trigger language in descriptions.
- Do not maintain duplicate `SKILL.md` files in agent-specific folders.

## Validation

Run locally:

```bash
bash bin/validate-skills.sh
```

CI workflow: `.github/workflows/skills-consistency.yml`
